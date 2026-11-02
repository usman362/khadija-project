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

        /*
         * Last month on the same basis, so the figure beside this month's
         * revenue is a comparison rather than a decoration. The cards used to
         * read "$12,840" and "up 22% vs last month", typed into the page, for
         * every professional including one who had never been paid.
         */
        $lastMonth      = now()->subMonthNoOverflow();
        $lastMonthGross = (float) Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('price');

        $thisMonthNet = Commission::netOf($thisMonthGross, $user);
        $lastMonthNet = Commission::netOf($lastMonthGross, $user);

        $reviewCount = Review::where('reviewee_id', $user->id)->where('is_hidden', false)->count();

        $stats = [
            'available_balance'   => $money['available'],
            'pending_payout'      => $money['pending'],
            'pending_count'       => $money['pendingCount'],
            'this_month_earnings' => $thisMonthNet,
            'last_month_earnings' => $lastMonthNet,
            'total_booked' => $money['total'],
            'avg_rating'   => round((float) Review::where('reviewee_id', $user->id)->where('is_hidden', false)->avg('rating'), 1),
            'review_count' => $reviewCount,

            // Requests sitting with this professional, unanswered.
            'awaiting_you' => Booking::where('supplier_id', $user->id)
                ->where('status', 'requested')->count(),

            // Jobs confirmed and still ahead of them.
            'upcoming_gigs' => Booking::where('supplier_id', $user->id)
                ->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', now()))
                ->count(),

            // Everything that has ever entered the funnel, counted the same
            // way the Leads page counts it, so the two pages agree.
            'leads_total' => Booking::where('supplier_id', $user->id)
                ->whereIn('status', ['requested', 'pending', 'confirmed', 'completed'])
                ->count(),
        ];

        /*
         * Today's agenda, from the bookings themselves. The right rail used
         * to list "Drive to Venue 1:00 PM", "Setup Time", a Corporate Gala
         * Dinner and "Breakdown", the same four lines for every professional
         * on every day of the year.
         */
        $todaySchedule = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q->whereDate('starts_at', now()->toDateString()))
            ->with(['event:id,title,starts_at,ends_at,location'])
            ->get()
            ->sortBy(fn ($b) => $b->event?->starts_at)
            ->values();

        /*
         * The professional's own bids by status. The card used to draw a
         * donut reading 25 total with 8 invited, 6 submitted, 4 viewed, 3 in
         * negotiation, 3 won and 1 lost, the same for everybody, under a
         * "Recommendation" that wedding packages were winning 21% more than
         * DJ-only ones. Nothing measured that.
         *
         * Only the statuses the application actually writes are counted:
         * submitted, won and withdrawn.
         */
        $bidCounts = \App\Models\Bid::where('supplier_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bidBreakdown = collect([
            'submitted' => ['label' => 'Open', 'color' => '#2563eb'],
            'won'       => ['label' => 'Won', 'color' => '#10b981'],
            'withdrawn' => ['label' => 'Withdrawn', 'color' => '#ef4444'],
        ])->map(fn ($row, $key) => $row + ['count' => (int) ($bidCounts[$key] ?? 0)])
          ->filter(fn ($row) => $row['count'] > 0)
          ->values();

        $bidTotal = (int) $bidCounts->sum();

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
            'stats', 'recentBookings', 'todaySchedule', 'bidBreakdown', 'bidTotal',
            'subscription', 'feed', 'myServicesOnly'
        ));
    }
}
