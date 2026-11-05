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
            /*
             * Earnings' 'total' is a COUNT of bookings, not money. Printed
             * through number_format with a dollar sign in front it turned
             * eight completed jobs worth $3,330 into "$8.00". 'earned' is the
             * money, net of commission, which is the basis the Earnings and
             * Transactions pages use.
             */
            'total_booked' => $money['earned'],
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

        /*
         * What actually wants this professional's attention, counted the same
         * way the Priority Actions page counts it so the two never disagree.
         *
         * The card used to list six fixed rows under a pill reading 7: a
         * contract awaiting signature on a Corporate Gala Dinner, a staffing
         * shortage, two bids expiring in two hours, a payment released, a
         * calendar conflict on May 24 and a late invoice numbered
         * INV-2026-1048. None of it belonged to anybody.
         */
        $attention = collect([
            [
                'title' => 'Agreements awaiting your signature',
                'count' => \App\Models\Finalization::where('supplier_id', $user->id)
                    ->whereNotNull('client_signed_at')
                    ->whereNull('supplier_signed_at')
                    ->count(),
                'tone'  => 'c-blue',
                'to'    => route('professional.agreements.index'),
            ],
            [
                'title' => 'Requests waiting for your answer',
                'count' => $stats['awaiting_you'],
                'tone'  => 'c-purple',
                'to'    => route('professional.proposals.index'),
            ],
            [
                'title' => 'Shifts still open on your crew',
                'count' => \App\Models\Shift::where('supplier_id', $user->id)
                    ->where('status', 'open')->count(),
                'tone'  => 'c-orange',
                'to'    => route('professional.team.index'),
            ],
            [
                'title' => 'Reviews you have not replied to',
                'count' => \App\Models\Review::where('reviewee_id', $user->id)
                    ->where('is_hidden', false)
                    ->whereNull('response')->count(),
                'tone'  => 'c-green',
                'to'    => route('professional.reviews.index'),
            ],
        ])->filter(fn ($row) => $row['count'] > 0)->values();

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

        /*
         * Khadijah's 28 October dashboard carries three more panels.
         *
         * Her Visibility Score is not among them. Nothing on the platform
         * measures how visible a professional is, so a score out of 100 would
         * be a figure about their own business that nobody worked out. The
         * setup bar below is the honest version of the same idea: it lists
         * what is actually missing, and every line can be checked.
         */
        $setup   = $this->setup($user);
        $goal    = $this->goal($user, $thisMonthNet);
        $busiest = $this->busiest($user);

        return view('professional.dashboard', compact('setup', 'goal', 'busiest', 
            'stats', 'recentBookings', 'todaySchedule', 'bidBreakdown', 'bidTotal', 'attention',
            'subscription', 'feed', 'myServicesOnly'
        ));
    }

    /**
     * What a professional still has to do before the marketplace works for
     * them, each line a thing they can go and finish.
     *
     * @return array{done:int, total:int, steps: array<int, array{label:string, done:bool, to:string}>}
     */
    private function setup(\App\Models\User $user): array
    {
        $services = $user->serviceCategories()
            ->where('categories.kind', \App\Models\Category::SERVICE)
            ->get();

        $rules   = \App\Models\PricingRule::where('user_id', $user->id)->first();
        $profile = $user->profile;

        $steps = [
            [
                'label' => 'List the services you offer',
                'done'  => $services->isNotEmpty(),
                'to'    => route('professional.services.index'),
            ],
            [
                'label' => 'Put a price on at least one',
                'done'  => $services->contains(fn ($s) => $s->pivot->price !== null),
                'to'    => route('professional.services.index'),
            ],
            [
                'label' => 'Say how far you travel',
                'done'  => $rules && ($rules->travel_free_miles !== null || $rules->travel_rate_per_mile !== null),
                'to'    => route('professional.services.index', ['tab' => 'travel']),
            ],
            [
                'label' => 'Publish a package',
                'done'  => \App\Models\Package::where('user_id', $user->id)->where('status', 'active')->exists(),
                'to'    => route('professional.packages.index'),
            ],
            [
                'label' => 'Verify who you are',
                'done'  => (bool) ($profile?->identity_verified_at),
                'to'    => route('professional.profile.index'),
            ],
        ];

        return [
            'steps' => $steps,
            'done'  => collect($steps)->where('done', true)->count(),
            'total' => count($steps),
        ];
    }

    /**
     * This month against what they said they were aiming for.
     *
     * Null until they name a figure. A dashboard that invents a target and
     * then reports somebody behind it is making up the thing it is measuring
     * them against.
     */
    private function goal(\App\Models\User $user, float $thisMonth): ?array
    {
        $target = \App\Models\PricingRule::where('user_id', $user->id)->value('monthly_revenue_goal');

        if (! $target || (float) $target <= 0) {
            return null;
        }

        $target = (float) $target;

        return [
            'target'  => $target,
            'earned'  => $thisMonth,
            'percent' => (int) min(100, round($thisMonth / $target * 100)),
        ];
    }

    /**
     * The months their own work actually falls in.
     *
     * Needs a year or so of their own history to say anything, and returns
     * null rather than a chart of ones and zeroes when it has less.
     *
     * @return array<int, array{label:string, count:int}>|null
     */
    private function busiest(\App\Models\User $user): ?array
    {
        $dates = \App\Models\Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->with('event:id,starts_at')
            ->get()
            ->map(fn ($b) => $b->event?->starts_at)
            ->filter();

        if ($dates->count() < 6) {
            return null;
        }

        $counts = $dates->groupBy(fn ($d) => $d->format('n'))->map->count();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'label' => \Illuminate\Support\Carbon::create(null, $m, 1)->format('M'),
                'count' => (int) ($counts->get((string) $m) ?? 0),
            ];
        }

        return $months;
    }
}
