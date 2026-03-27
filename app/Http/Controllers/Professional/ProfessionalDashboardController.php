<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Stats
        $stats = [
            'available_balance' => 0,  // placeholder - no payment model yet
            'this_month_earnings' => 0,  // placeholder
            'total_booked' => Booking::where('supplier_id', $user->id)->count(),
            'avg_rating' => 0,  // placeholder
        ];

        // Recent bookings
        $recentBookings = Booking::where('supplier_id', $user->id)
            ->with(['event:id,title,starts_at', 'client:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Active subscription
        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return view('professional.dashboard', compact('stats', 'recentBookings', 'subscription'));
    }
}
