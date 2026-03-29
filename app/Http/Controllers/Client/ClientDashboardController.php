<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Stats — client only
        $stats = [
            'total_events' => Event::where('client_id', $user->id)->count(),
            'open_events' => Event::where('client_id', $user->id)->whereIn('status', ['pending', 'published', 'confirmed'])->count(),
            'upcoming_events' => Event::where('client_id', $user->id)->where('starts_at', '>', now())->count(),
            'total_bookings' => Booking::where('client_id', $user->id)->count(),
            'active_bookings' => Booking::where('client_id', $user->id)->whereIn('status', ['requested', 'confirmed'])->count(),
            'completed_bookings' => Booking::where('client_id', $user->id)->where('status', 'completed')->count(),
        ];

        // Recent events
        $recentEvents = Event::where('client_id', $user->id)
            ->with(['categories:id,name', 'supplier:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Recent bookings
        $recentBookings = Booking::where('client_id', $user->id)
            ->with(['event:id,title', 'supplier:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Active subscription
        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return view('client.dashboard', compact('stats', 'recentEvents', 'recentBookings', 'subscription'));
    }
}
