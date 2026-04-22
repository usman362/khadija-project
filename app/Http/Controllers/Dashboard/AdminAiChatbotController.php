<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAiChatbotController extends Controller
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    public function settings(): View
    {
        return view('dashboard.settings.chatbot', [
            'settings' => $this->settings->getChatbotSettings(),
            'default_prompt' => $this->settings->defaultChatbotSystemPrompt(),
            'openai_configured' => $this->settings->isOpenAIConfigured(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'       => ['required', 'in:0,1'],
            'model'         => ['required', 'string', 'max:60'],
            'max_tokens'    => ['required', 'integer', 'min:100', 'max:4000'],
            'temperature'   => ['required', 'numeric', 'min:0', 'max:2'],
            'daily_limit'   => ['required', 'integer', 'min:0', 'max:1000'],
            'system_prompt' => ['required', 'string', 'max:8000'],
        ]);

        $this->settings->saveChatbotSettings([
            'enabled'       => (bool) $validated['enabled'],
            'model'         => $validated['model'],
            'max_tokens'    => (int) $validated['max_tokens'],
            'temperature'   => (float) $validated['temperature'],
            'daily_limit'   => (int) $validated['daily_limit'],
            'system_prompt' => $validated['system_prompt'],
        ]);

        return back()->with('status', 'Chatbot settings updated successfully.');
    }

    public function logs(Request $request): View
    {
        $query = AiChatConversation::with('user:id,name,email,deleted_at')
            ->withCount('messages')
            ->orderByDesc('last_message_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($u) use ($s) {
                      $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $conversations = $query->paginate(25)->withQueryString();

        $stats = [
            'total_conversations' => AiChatConversation::count(),
            'total_messages'      => AiChatMessage::count(),
            'total_tokens'        => (int) AiChatConversation::sum('total_tokens'),
            'active_today'        => AiChatConversation::whereDate('last_message_at', now()->toDateString())->count(),
        ];

        return view('dashboard.admin.chatbot-logs.index', compact('conversations', 'stats'));
    }

    public function showConversation(AiChatConversation $conversation): View
    {
        $conversation->load('user:id,name,email,deleted_at', 'messages');
        return view('dashboard.admin.chatbot-logs.show', compact('conversation'));
    }
}
