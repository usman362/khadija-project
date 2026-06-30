<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Real stats from bookings + reviews (no more hardcoded placeholders).
        $earnedQuery = Booking::where('supplier_id', $user->id)->whereIn('status', ['confirmed', 'completed']);

        $stats = [
            'available_balance'   => (float) (clone $earnedQuery)->where('status', 'completed')->sum('price'),
            'this_month_earnings' => (float) (clone $earnedQuery)
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('price'),
            'total_booked' => Booking::where('supplier_id', $user->id)->count(),
            'avg_rating'   => round((float) Review::where('reviewee_id', $user->id)->where('is_hidden', false)->avg('rating'), 1),
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
