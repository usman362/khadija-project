<?php

namespace App\Domain\AiChatbot\Services;

use App\Domain\Settings\Services\SettingsService;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiChatbotService
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    // ── Settings proxies ──────────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->settings->getChatbotSettings()['enabled']
            && $this->settings->isOpenAIConfigured();
    }

    public function dailyLimit(): int
    {
        return $this->settings->getChatbotSettings()['daily_limit'];
    }

    public function remainingToday(User $user): int
    {
        $limit = $this->dailyLimit();
        if ($limit <= 0) {
            return PHP_INT_MAX; // 0 = unlimited
        }

        $sent = AiChatMessage::where('role', 'user')
            ->whereHas('conversation', fn($q) => $q->where('user_id', $user->id))
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return max(0, $limit - $sent);
    }

    // ── Conversation management ──────────────────────────────

    public function startConversation(User $user, string $firstMessage): AiChatConversation
    {
        $conv = AiChatConversation::create([
            'user_id'         => $user->id,
            'title'           => 'New Conversation',
            'last_message_at' => now(),
        ]);

        $conv->title = $conv->autoTitle($firstMessage);
        $conv->save();

        return $conv;
    }

    // ── Chat completion ───────────────────────────────────────

    /**
     * Send a user message to the LLM and persist both user and assistant messages.
     * Returns the assistant's reply text.
     */
    public function chat(User $user, ?int $conversationId, string $userMessage): array
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('AI assistant is currently disabled. Please contact support.');
        }

        if ($this->remainingToday($user) <= 0) {
            throw new RuntimeException("You've reached today's message limit. Try again tomorrow.");
        }

        $conversation = $conversationId
            ? AiChatConversation::where('id', $conversationId)->where('user_id', $user->id)->firstOrFail()
            : $this->startConversation($user, $userMessage);

        // Persist user message
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userMessage,
            'created_at'      => now(),
        ]);

        // Build message history for LLM (last 20 exchanges = 40 messages max)
        $history = AiChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->take(40)
            ->get(['role', 'content'])
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        // Prepend system prompt
        $config = $this->settings->getChatbotSettings();
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($user, $config['system_prompt'])]],
            $history,
        );

        // Call OpenAI
        $reply = $this->callOpenAI($messages, $config);

        // Persist assistant message
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $reply['content'],
            'tokens_used'     => $reply['tokens'],
            'created_at'      => now(),
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'total_tokens'    => $conversation->total_tokens + $reply['tokens'],
        ]);

        return [
            'conversation_id' => $conversation->id,
            'title'           => $conversation->title,
            'reply'           => $reply['content'],
            'remaining_today' => $this->remainingToday($user),
        ];
    }

    private function buildSystemPrompt(User $user, string $basePrompt): string
    {
        $context = "\n\n--- Current User Context ---\n";
        $context .= "Name: {$user->name}\n";
        $context .= "Email: {$user->email}\n";
        $context .= "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
        $context .= "Current Date: " . now()->format('F j, Y') . "\n";

        return $basePrompt . $context;
    }

    private function callOpenAI(array $messages, array $config): array
    {
        $apiKey = $this->settings->getOpenAIKey();
        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $config['model'],
                'messages'    => $messages,
                'temperature' => $config['temperature'],
                'max_tokens'  => $config['max_tokens'],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI chat error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new RuntimeException('The AI service is temporarily unavailable. Please try again in a moment.');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $tokens  = $data['usage']['total_tokens'] ?? 0;

            if (!$content) {
                throw new RuntimeException('Empty response from AI. Please try again.');
            }

            return ['content' => trim($content), 'tokens' => $tokens];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('OpenAI chat exception', ['error' => $e->getMessage()]);
            throw new RuntimeException('Unable to reach AI service. Please try again.');
        }
    }
}
