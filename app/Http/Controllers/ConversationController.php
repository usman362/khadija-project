<?php

namespace App\Http\Controllers;

use App\Domain\Messaging\Events\MessageInserted;
use App\Domain\Messaging\Events\MessageReadBroadcast;
use App\Domain\Messaging\Events\MessageSent;
use App\Domain\Messaging\Events\TypingStarted;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Conversation::class, 'conversation');
    }

    /**
     * List the authenticated user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::forUser($user)
            ->with([
                'participants:id,name,email',
                'booking:id,event_id,status',
                'event:id,title',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $user->id));
            }])
            ->addSelect(['last_message_body' => Message::select('body')
                ->whereColumn('conversation_id', 'conversations.id')
                ->latest('created_at')
                ->limit(1),
            ])
            ->addSelect(['last_message_at' => Message::select('created_at')
                ->whereColumn('conversation_id', 'conversations.id')
                ->latest('created_at')
                ->limit(1),
            ]);

        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('participants', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $conversations = $query->orderByDesc('last_message_at')->paginate(30);

        return response()->json($conversations);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:direct,booking,event',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'booking_id' => 'nullable|required_if:type,booking|exists:bookings,id',
            'event_id' => 'nullable|required_if:type,event|exists:events,id',
        ]);

        $user = $request->user();

        // For direct chats, check if conversation already exists between these users
        if ($validated['type'] === 'direct' && count($validated['participant_ids']) === 1) {
            $otherUserId = $validated['participant_ids'][0];
            $existing = Conversation::ofType('direct')
                ->forUser($user)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
                ->first();

            if ($existing) {
                $existing->load('participants:id,name,email');
                return response()->json($existing);
            }
        }

        // For booking/event chats, check existing
        if ($validated['type'] === 'booking' && ! empty($validated['booking_id'])) {
            $existing = Conversation::ofType('booking')
                ->where('booking_id', $validated['booking_id'])
                ->first();

            if ($existing) {
                $existing->addParticipant($user);
                $existing->load('participants:id,name,email');
                return response()->json($existing);
            }
        }

        $conversation = Conversation::create([
            'type' => $validated['type'],
            'booking_id' => $validated['booking_id'] ?? null,
            'event_id' => $validated['event_id'] ?? null,
            'created_by' => $user->id,
        ]);

        // Add creator + participants
        $conversation->addParticipant($user);
        foreach ($validated['participant_ids'] as $participantId) {
            $conversation->addParticipant(\App\Models\User::find($participantId));
        }

        $conversation->load('participants:id,name,email');

        return response()->json($conversation, 201);
    }

    /**
     * Get a conversation with its messages.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $conversation->load(['participants:id,name,email', 'booking:id,event_id,status', 'event:id,title']);

        $messages = $conversation->messages()
            ->with(['sender:id,name,email', 'attachments', 'reads:id,message_id,user_id,read_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'attachment_ids' => 'nullable|array',
            'attachment_ids.*' => 'exists:message_attachments,id',
        ]);

        $user = $request->user();

        // Determine recipient (the other participant in direct chat)
        $recipientId = null;
        if ($conversation->type === 'direct') {
            $recipientId = $conversation->participants()
                ->where('user_id', '!=', $user->id)
                ->value('user_id');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'event_id' => $conversation->event_id,
            'booking_id' => $conversation->booking_id,
            'sender_id' => $user->id,
            'recipient_id' => $recipientId,
            'body' => $validated['body'],
            'source' => 'user',
        ]);

        // Link attachments to this message
        if (! empty($validated['attachment_ids'])) {
            MessageAttachment::whereIn('id', $validated['attachment_ids'])
                ->whereNull('message_id')
                ->update(['message_id' => $message->id]);
        }

        // Auto-mark as read by sender
        $message->reads()->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $message->load(['sender:id,name,email', 'attachments']);

        // Broadcast real-time + fire domain event for audit
        broadcast(new MessageSent($message))->toOthers();
        event(new MessageInserted($message));

        return response()->json($message, 201);
    }

    /**
     * Mark all messages in a conversation as read.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        $unreadIds = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();

        if (! empty($unreadIds)) {
            $conversation->markAsReadFor($user);
            broadcast(new MessageReadBroadcast($conversation, $user, $unreadIds))->toOthers();
        }

        return response()->json(['read_count' => count($unreadIds)]);
    }

    /**
     * Broadcast typing indicator.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        broadcast(new TypingStarted($conversation, $request->user()))->toOthers();

        return response()->json(['ok' => true]);
    }
}
