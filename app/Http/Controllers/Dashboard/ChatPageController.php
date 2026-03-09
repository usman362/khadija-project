<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatPageController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('dashboard.chat.index', [
            'currentUser' => $request->user(),
            'users' => $users,
            'initialConversationId' => null,
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $users = User::where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('dashboard.chat.index', [
            'currentUser' => $request->user(),
            'users' => $users,
            'initialConversationId' => $conversation->id,
        ]);
    }
}
