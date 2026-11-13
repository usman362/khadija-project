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

        /*
         * Money is earned when the work is done, so the month is the month
         * the JOB falls in, not the month its booking record was created.
         *
         * This read whereMonth('created_at'). A wedding booked in September
         * and performed in November counted as September revenue, so a
         * professional who finished three jobs this month could open the page
         * and be told they had earned nothing this month, with the revenue
         * goal reporting them behind on work they had already delivered.
         *
         * Only completed work counts, which is the basis App\Support\Earnings
         * uses for its 'earned' figure. Confirmed work still ahead of them is
         * money on the way, and it is reported separately as exactly that.
         *
         * A booking whose event carries no date cannot be placed in a month
         * at all, and is left out rather than filed under the month somebody
         * happened to create the record.
         */
        $lastMonth = now()->subMonthNoOverflow();

        $byMonth = Booking::where('supplier_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('event', fn ($q) => $q->whereNotNull('starts_at'))
            ->with('event:id,starts_at')
            ->get()
            ->groupBy(fn (Booking $b) => $b->event?->starts_at?->format('Y-n'));

        $sumFor = fn (\Illuminate\Support\Carbon $month) => (float) ($byMonth
            ->get($month->format('Y-n'))?->sum('price') ?? 0);

        $thisMonthGross = $sumFor(now());
        $lastMonthGross = $sumFor($lastMonth);

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
         *
         * Bounded by the UTC instants either side of today on the platform's
         * clock, not by whereDate. starts_at is stored in UTC, so a nine
         * o'clock evening gig is written as two in the morning the next day
         * and whereDate put it on tomorrow's schedule. Most of this
         * marketplace's work is evening work.
         */
        [$dayFrom, $dayTo] = \App\Support\DisplayTimezone::dayWindow(null, $user);

        $todaySchedule = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', $dayFrom)->where('starts_at', '<', $dayTo))
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
        /*
         * Four summary cards, each with the parts it is made of, as Khadijah's
         * screen has them. A single "6" tells somebody six things are waiting
         * without saying what any of them are, which is the same fault the
         * Priority Actions page had.
         */
        $summary = $this->summary($user);

        /*
         * What is waiting, and which one to do first.
         *
         * The same list the Priority Actions page builds, so the dashboard and
         * that page can never disagree about what is outstanding.
         */
        $waiting = \App\Support\PriorityActions::forProfessional($user);
        $nextBest = $waiting->first();

        // Read BEFORE the visit is recorded, or the answer is always nothing.
        $sinceLast = $this->sinceLastVisit($user);
        $user->forceFill(['dashboard_seen_at' => now()])->saveQuietly();

        $gap     = $this->serviceGap($user);
        $setup   = $this->setup($user);
        $goal    = $this->goal($user, $thisMonthNet);
        $busiest = $this->busiest($user);
        $cash    = [
            'available' => (float) ($money['available'] ?? 0),
            'pending'   => (float) ($money['pending'] ?? 0),
            'month'     => $thisMonthNet,
            // What is theirs once everything already agreed has settled.
            'forecast'  => (float) ($money['available'] ?? 0) + (float) ($money['pending'] ?? 0),
        ];

        return view('professional.dashboard', compact('setup', 'goal', 'busiest', 'summary', 'waiting', 'nextBest', 'sinceLast', 'gap', 'cash', 
            'stats', 'recentBookings', 'todaySchedule', 'bidBreakdown', 'bidTotal', 'attention',
            'subscription', 'feed', 'myServicesOnly'
        ));
    }

    /**
     * The four cards across the top, each carrying its own parts.
     *
     * @return array<string, array{count:int, parts: array<int, string>, note:?string, to:string}>
     */
    private function summary(\App\Models\User $user): array
    {
        $zone  = config('app.display_timezone', config('app.timezone'));
        $today = \Illuminate\Support\Carbon::today($zone);

        $proposals = Booking::where('supplier_id', $user->id)->where('status', 'requested')->count();

        $unread = \App\Models\Message::whereHas(
            'conversation.participants',
            fn ($q) => $q->where('users.id', $user->id)
        )->where('sender_id', '!=', $user->id)->count();

        $leads = \App\Models\Bid::where('supplier_id', $user->id)
            ->whereIn('status', ['submitted', 'pending'])
            ->count();

        $soon = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereHas('event', fn ($q) => $q
                ->whereBetween('starts_at', [now(), now()->addDays(30)]))
            ->with('event:id,starts_at')
            ->get();

        // A gig "needs attention" when its crew is not filled. Nothing else
        // on a confirmed booking is outstanding by definition.
        $shortStaffed = \App\Models\Shift::where('supplier_id', $user->id)
            ->where('status', 'open')
            ->whereIn('event_id', $soon->pluck('event_id')->filter())
            ->distinct('event_id')
            ->count('event_id');

        $payouts = \App\Models\Payout::where('user_id', $user->id)
            ->whereIn('status', ['requested', 'pending'])
            ->count();

        // The same window as the schedule above, for the same reason.
        [$todayFrom, $todayTo] = \App\Support\DisplayTimezone::dayWindow($today, $user);

        $todayGigs = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', $todayFrom)->where('starts_at', '<', $todayTo))
            ->with('event:id,starts_at,title')
            ->get();

        $nextToday = $todayGigs
            ->map(fn (Booking $b) => $b->event?->starts_at)
            ->filter(fn ($at) => $at && $at->isFuture())
            ->sort()
            ->first();

        $part = fn (int $n, string $word) => $n > 0
            ? $n . ' ' . \Illuminate\Support\Str::plural($word, $n)
            : null;

        return [
            'response' => [
                'count' => $proposals + $leads + $unread,
                'parts' => array_values(array_filter([
                    $part($proposals, 'request'),
                    $part($leads, 'open bid'),
                    $part($unread, 'message'),
                ])),
                'note'  => null,
                'to'    => route('professional.proposals.index'),
            ],
            'upcoming' => [
                'count' => $soon->count(),
                'parts' => ['Next 30 days'],
                'note'  => $shortStaffed > 0
                    ? $shortStaffed . ' ' . \Illuminate\Support\Str::plural('needs', $shortStaffed) . ' staff'
                    : null,
                'to'    => route('professional.gig-hub.index'),
            ],
            'payments' => [
                'count' => $payouts,
                'parts' => array_values(array_filter([$part($payouts, 'payout waiting')])),
                'note'  => null,
                'to'    => route('professional.earnings.index'),
            ],
            'today' => [
                'count' => $todayGigs->count(),
                'parts' => ['Booked for today'],
                'note'  => $nextToday ? 'Next in ' . $nextToday->diffForHumans(null, true) : null,
                'to'    => route('professional.calendar.index'),
            ],
        ];
    }

    /**
     * What has happened since they last opened this page.
     *
     * Null on a first visit, because "new since your last visit" has no answer
     * when there has not been one, and showing every record they have ever had
     * as new would be worse than showing nothing.
     *
     * @return array<int, array{label:string, count:int}>|null
     */
    private function sinceLastVisit(\App\Models\User $user): ?array
    {
        $since = $user->dashboard_seen_at;

        if (! $since) {
            return null;
        }

        $rows = [
            ['label' => 'New requests', 'count' => Booking::where('supplier_id', $user->id)
                ->where('created_at', '>=', $since)->count()],
            ['label' => 'Bids you placed', 'count' => \App\Models\Bid::where('supplier_id', $user->id)
                ->where('created_at', '>=', $since)->count()],
            ['label' => 'Messages', 'count' => \App\Models\Message::whereHas(
                'conversation.participants', fn ($q) => $q->where('users.id', $user->id)
            )->where('sender_id', '!=', $user->id)->where('created_at', '>=', $since)->count()],
            ['label' => 'Reviews', 'count' => Review::where('reviewee_id', $user->id)
                ->where('created_at', '>=', $since)->count()],
        ];

        return collect($rows)->sum('count') > 0 ? $rows : null;
    }

    /**
     * A service being asked for nearby that this professional does not offer.
     *
     * Khadijah's screen has a recommendation here: "Add Lighting Services to
     * match 5 new open bids". This is that, counted rather than suggested. It
     * says which service and how many open requests name it, and both can be
     * checked against the bidding board.
     *
     * @return array{name:string, count:int}|null
     */
    private function serviceGap(\App\Models\User $user): ?array
    {
        $mine = $user->serviceCategories()
            ->where('categories.kind', \App\Models\Category::SERVICE)
            ->pluck('categories.id');

        $state = $user->profile?->state;

        $wanted = \Illuminate\Support\Facades\DB::table('category_event')
            ->join('events', 'events.id', '=', 'category_event.event_id')
            ->join('categories', 'categories.id', '=', 'category_event.category_id')
            ->where('events.is_published', true)
            ->whereNull('events.supplier_id')
            ->whereIn('events.status', ['pending', 'published'])
            // Work they could actually take.
            ->when($state, fn ($q) => $q->where('events.state', $state))
            ->whereNotIn('categories.id', $mine)
            ->where('categories.kind', \App\Models\Category::SERVICE)
            ->groupBy('categories.id', 'categories.name')
            ->orderByRaw('count(*) desc')
            ->selectRaw('categories.name as name, count(*) as total')
            ->first();

        // One open request is not a gap worth telling somebody to fill.
        return $wanted && $wanted->total >= 2
            ? ['name' => $wanted->name, 'count' => (int) $wanted->total]
            : null;
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

        /*
         * Where the month should be by now, and how far off that is.
         *
         * The reference screen reads "$6,515 behind pace" beside "97% of
         * month complete". Both halves are arithmetic on the month itself:
         * a target spread evenly across its days, compared against what has
         * actually been earned so far. Nothing is predicted, and the figure
         * is as checkable as the calendar.
         */
        $zone  = \App\Support\DisplayTimezone::current();
        $now   = \Illuminate\Support\Carbon::now($zone);
        $days  = $now->daysInMonth;
        $gone  = $now->day;
        $pace  = $target * ($gone / $days);

        return [
            'target'   => $target,
            'earned'   => $thisMonth,
            'percent'  => (int) min(100, round($thisMonth / $target * 100)),
            'pace'     => round($pace, 2),
            'off'      => round($thisMonth - $pace, 2),
            'elapsed'  => (int) round($gone / $days * 100),
            'day'      => $gone,
            'days'     => $days,
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
