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
                // Photo, last seen and service, for the dock's rows.
                'participants:id,name,email,avatar,last_active_at,primary_role',
                'participants.serviceCategories:id,name',
                'booking:id,event_id,status',
                'event:id,title,source',
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
            // The dock leaves muted conversations out of its unread total.
            ->addSelect(['muted_at' => \Illuminate\Support\Facades\DB::table('conversation_participants')
                ->select('muted_at')
                ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                ->where('conversation_participants.user_id', $user->id)
                ->limit(1),
            ])
            ->addSelect(['last_message_at' => Message::select('created_at')
                ->whereColumn('conversation_id', 'conversations.id')
                ->latest('created_at')
                ->limit(1),
            ])
            // Starred by this person (the dock's Favorites tab).
            ->addSelect(['favorited_at' => \Illuminate\Support\Facades\DB::table('conversation_participants')
                ->select('favorited_at')
                ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                ->where('conversation_participants.user_id', $user->id)
                ->limit(1),
            ]);

        // The dock's Unread and Favorites tabs.
        if ($request->input('filter') === 'unread') {
            $query->whereHas('messages', fn ($q) => $q->where('sender_id', '!=', $user->id)
                ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $user->id)));
        } elseif ($request->input('filter') === 'favorites') {
            $query->whereHas('participants', fn ($q) => $q->where('users.id', $user->id)
                ->whereNotNull('conversation_participants.favorited_at'));
        }

        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('participants', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $conversations = $query->orderByDesc('last_message_at')->paginate(30);

        // What a row in the dock shows about the other person, worked out
        // once here rather than in the browser.
        $conversations->through(function (Conversation $c) use ($user) {
            $peer = $c->participants->firstWhere('id', '!=', $user->id);

            $c->setAttribute('peer', $peer ? [
                'id'       => $peer->id,
                'name'     => $peer->name,
                'avatar'   => $peer->avatar_url,
                'online'   => (bool) ($peer->last_active_at && \Illuminate\Support\Carbon::parse($peer->last_active_at)->gt(now()->subMinutes(5))),
                'subtitle' => $peer->primary_role === 'professional'
                    ? ($peer->serviceCategories->first()?->name ?? 'Professional')
                    : ucfirst((string) ($peer->primary_role ?: 'member')),
            ] : null);

            // With its timezone: a bare "2026-09-11 11:00:00" is read by the
            // browser as local time, so a message sent a minute ago in
            // Pakistan showed as "5h".
            if ($c->last_message_at) {
                $c->setAttribute('last_message_at', \Illuminate\Support\Carbon::parse($c->last_message_at)->toIso8601String());
            }

            $c->setAttribute('request_type', match (true) {
                ! $c->event                                   => null,
                $c->event->source === 'esr'                   => 'Emergency Request',
                str_contains((string) $c->event->source, 'direct') => 'Direct Request',
                default                                       => 'Bidding Request',
            });

            return $c;
        });

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

        // A block stops new conversations too, either way round.
        foreach ($validated['participant_ids'] as $participantId) {
            if (\App\Domain\Messaging\Blocking::between($user->id, (int) $participantId)) {
                return response()->json(['message' => \App\Domain\Messaging\Blocking::MESSAGE], 403);
            }
        }

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

        /*
         * Two windows, both from Khadijah's sheet: 10 an hour stops a burst,
         * 25 a day stops a slow drip. Counted here — after validation — so a
         * message rejected for being empty has not cost anybody one of theirs.
         */
        \App\Support\UserLimit::hit('messages-hour', $user, null, 'body');
        \App\Support\UserLimit::hit('messages-day', $user, null, 'body');

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
    /**
     * Archive a conversation, or bring it back, for the person asking.
     *
     * The other participant keeps it where it was. Archiving tidies your own
     * inbox; it does not end the conversation, and nobody is told.
     */
    public function archive(Request $request, Conversation $conversation): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $was  = $conversation->participants()->where('users.id', $user->id)->first()?->pivot?->archived_at;

        $conversation->participants()->updateExistingPivot($user->id, ['archived_at' => $was ? null : now()]);
        $archived = ! $was;

        if ($request->expectsJson()) {
            return response()->json(['archived' => $archived]);
        }

        return back()->with('status', $archived
            ? 'Conversation archived. You will find it under Archived.'
            : 'Conversation moved back to your inbox.');
    }

    /**
     * Mute or unmute a conversation, for the person asking.
     *
     * Its messages still arrive and still show as unread in the conversation;
     * they stop adding to the unread counts on the menu and the messages
     * button. Nobody is told.
     */
    public function mute(Request $request, Conversation $conversation): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $was  = $conversation->participants()->where('users.id', $user->id)->first()?->pivot?->muted_at;
        $conversation->participants()->updateExistingPivot($user->id, ['muted_at' => $was ? null : now()]);
        $muted = ! $was;

        if ($request->expectsJson()) {
            return response()->json(['muted' => $muted]);
        }

        return back()->with('status', $muted
            ? 'Muted. New messages here will not add to your unread count.'
            : 'Unmuted.');
    }

    /** Star or unstar this conversation, for this person only. */
    public function favorite(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $was  = $conversation->participants()->where('users.id', $user->id)->first()?->pivot?->favorited_at;
        $conversation->participants()->updateExistingPivot($user->id, ['favorited_at' => $was ? null : now()]);

        return response()->json(['favorited' => ! $was]);
    }

    /**
     * Block or unblock the other person in this conversation.
     *
     * While blocked, neither side can send messages to the other, here or in
     * any other conversation. Bookings, agreements and payments are not
     * touched. Only the person who blocked can lift it.
     */
    public function block(Request $request, Conversation $conversation): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $this->authorize('view', $conversation);

        $user   = $request->user();
        $others = $conversation->participants()->where('users.id', '!=', $user->id)->pluck('users.id');
        abort_if($others->isEmpty(), 422, 'There is nobody else in this conversation.');

        $mine = \App\Models\UserBlock::where('blocker_id', $user->id)->whereIn('blocked_id', $others);
        $wasBlocked = (clone $mine)->exists();

        if ($wasBlocked) {
            $mine->delete();
        } else {
            foreach ($others as $id) {
                \App\Models\UserBlock::firstOrCreate(['blocker_id' => $user->id, 'blocked_id' => $id]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['blocked' => ! $wasBlocked]);
        }

        return back()->with('status', $wasBlocked
            ? 'Unblocked. You can message each other again.'
            : 'Blocked. Neither of you can send messages until you unblock. Bookings and payments are not affected.');
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        broadcast(new TypingStarted($conversation, $request->user()))->toOthers();

        return response()->json(['ok' => true]);
    }
}
