<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\UserSubscription;
use App\Support\Commission;
use App\Support\Earnings;
use App\Support\OpportunityFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Money comes from App\Support\Earnings, the same source the Earnings
        // and Transactions pages read. This used to sum booking prices gross,
        // so the dashboard promised a balance larger than the professional
        // could actually withdraw — commission had not been taken off.
        $money = Earnings::forProfessional($user);

        $thisMonthGross = (float) Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('price');

        $stats = [
            'available_balance'   => $money['available'],
            'pending_payout'      => $money['pending'],
            'pending_count'       => $money['pendingCount'],
            'this_month_earnings' => Commission::netOf($thisMonthGross, $user),
            'total_booked' => $money['total'],
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

        // Rule R61 — the Opportunity Feed, Option B. Replaces the Emergency
        // Gigs card, which was a hardcoded "DJ Needed Tonight" with a
        // hardcoded countdown and an Accept button wired to nothing.
        //
        // The toggle defaults OFF: services first, then related work below.
        // Peter's own concern was an empty feed for a professional who has
        // not listed much yet, and narrowing by default is the thing that
        // would cause it.
        $myServicesOnly = $request->boolean('my_services');
        $feed = OpportunityFeed::for($user, $myServicesOnly);

        return view('professional.dashboard', compact(
            'stats', 'recentBookings', 'subscription', 'feed', 'myServicesOnly'
        ));
    }
}
