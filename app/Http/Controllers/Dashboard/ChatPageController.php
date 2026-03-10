<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatPageController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.chat.index', $this->viewData($request));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('dashboard.chat.index', array_merge(
            $this->viewData($request),
            ['initialConversationId' => $conversation->id],
        ));
    }

    private function viewData(Request $request): array
    {
        $user = $request->user();

        $users = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Bookings the user is part of (client or supplier)
        $bookings = Booking::with('event:id,title')
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id))
            ->whereIn('status', ['requested', 'confirmed'])
            ->latest()
            ->get(['id', 'event_id', 'client_id', 'supplier_id', 'status']);

        // Events the user is part of
        $events = Event::where(fn ($q) => $q->where('client_id', $user->id)
                ->orWhere('supplier_id', $user->id)
                ->orWhere('created_by', $user->id))
            ->where('is_published', true)
            ->latest()
            ->get(['id', 'title']);

        return [
            'currentUser' => $user,
            'users' => $users,
            'bookings' => $bookings,
            'events' => $events,
            'initialConversationId' => null,
        ];
    }
}
