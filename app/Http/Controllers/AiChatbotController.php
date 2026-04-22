<?php

namespace App\Http\Controllers;

use App\Domain\AiChatbot\Services\AiChatbotService;
use App\Models\AiChatConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiChatbotController extends Controller
{
    public function __construct(
        private AiChatbotService $service,
    ) {}

    /**
     * Send a message; create conversation if conversation_id is omitted.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer', 'exists:ai_chat_conversations,id'],
        ]);

        try {
            $result = $this->service->chat(
                $request->user(),
                $validated['conversation_id'] ?? null,
                $validated['message'],
            );
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * List the current user's conversations.
     */
    public function conversations(Request $request): JsonResponse
    {
        $items = AiChatConversation::where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->take(30)
            ->get(['id', 'title', 'last_message_at', 'total_tokens']);

        return response()->json([
            'conversations'   => $items,
            'remaining_today' => $this->service->remainingToday($request->user()),
            'daily_limit'     => $this->service->dailyLimit(),
        ]);
    }

    /**
     * Load messages for a specific conversation.
     */
    public function show(Request $request, AiChatConversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        return response()->json([
            'id'       => $conversation->id,
            'title'    => $conversation->title,
            'messages' => $conversation->messages()->get(['role', 'content', 'created_at']),
        ]);
    }

    /**
     * Delete a conversation (and all its messages).
     */
    public function destroy(Request $request, AiChatConversation $conversation): JsonResponse
    {
        abort_if($conversation->user_id !== $request->user()->id, 403);

        $conversation->delete();

        return response()->json(['success' => true]);
    }
}
