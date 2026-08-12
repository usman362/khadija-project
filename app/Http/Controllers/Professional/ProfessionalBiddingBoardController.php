<?php

namespace App\Http\Controllers\Professional;

use App\Domain\Requests\RequestLifecycle;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Event;
use App\Support\Commission;
use App\Support\StateMatching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Main Bidding Board.
 *
 * Every OPEN client gig in one place to bid on. Gigs are REAL published Events
 * (not completed/cancelled). ER is read from the event's source — a rush
 * request can be single-service, so counting services would mislabel it;
 * SSR vs MSR is then the service count. Match-score and images are
 * representative fields until the scoring model lands.
 */
class ProfessionalBiddingBoardController extends Controller
{
    /** Non-Elite tiers unlock ER/MSR this many minutes after posting. */
    private const TIER_DELAY_MINUTES = 60;

    /**
     * Board tabs = the request TYPE. Peter's model (2026-07-27): BR is
     * broadcast bidding, ER is the same mechanism with urgency on top, DR is
     * targeted at one professional and never bid on. SSR and MSR are NOT types
     * here — they are the scope (single vs multi service) inside each, and are
     * filtered separately. Packages and Invite Only are in the mockups but have
     * no model yet — see the note in index().
     */
    public const TABS = ['all', 'BR', 'ER', 'DR', 'saved'];

    /** Scope filter — the service count, which is what single vs multi means. */
    public const SCOPES = ['', 'single', 'multi'];

    public function index(Request $request): View
    {
        $user = $request->user();

        $tab    = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'all';
        $scope  = in_array($request->query('scope'), self::SCOPES, true) ? (string) $request->query('scope') : '';
        $q      = trim((string) $request->query('q', ''));
        $catId  = (int) $request->query('category', 0);
        $city   = trim((string) $request->query('city', ''));
        $window = (string) $request->query('closing', '');      // 48h | week | ''
        $sort   = (string) $request->query('sort', 'deadline');
        $view   = $request->query('view') === 'card' ? 'card' : 'list';

        $savedIds = $user ? $user->savedEvents()->pluck('events.id') : collect();

        // Direct Offers used to be excluded outright — the query only took
        // published events, and an offer is unpublished by design. But an offer
        // IS this pro's opportunity: it names them in supplier_id. They now
        // appear alongside broadcast gigs, scoped to the recipient.
        $base = Event::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($outer) use ($user) {
                // A broadcast gig leaves the board once it is awarded. Only
                // `completed` and `cancelled` were excluded before, so an event
                // that already had a supplier — awarded to someone else — sat
                // there taking bids nobody could win.
                $outer->where(fn ($q1) => $q1->where('is_published', true)
                                              ->whereNull('supplier_id'))
                      ->orWhere(fn ($q2) => $q2->where('source', 'direct_offer')
                                                ->where('supplier_id', $user?->id));
            })
            ->with('categories:id,name');

        // Rule R38 — the board is a search surface, and search HIDES what the
        // viewer cannot act on. A gig in another state is not a shorter list;
        // it is work this professional is not permitted to take, and showing
        // it only produces bids that have to be refused later.
        StateMatching::scopeForViewer($base, $user);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $base->where(fn ($w) => $w->where('title', 'like', $like)
                                      ->orWhere('location', 'like', $like)
                                      ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', $like)));
        }
        if ($catId > 0) {
            $base->whereHas('categories', fn ($c) => $c->where('categories.id', $catId));
        }
        if ($city !== '') {
            $base->where('location', 'like', $city . '%');
        }
        if ($window === '48h') {
            $base->whereBetween('starts_at', [now(), now()->addHours(48)]);
        } elseif ($window === 'week') {
            $base->whereBetween('starts_at', [now(), now()->addWeek()]);
        }
        if ($tab === 'saved') {
            $base->whereIn('id', $savedIds->all() ?: [0]);
        } elseif ($tab === 'DR') {
            $base->where('source', 'direct_offer');
        } elseif ($tab === 'ER') {
            $base->where('source', 'esr');
        } elseif ($tab === 'BR') {
            // Bidding, but not the emergency flavour — ER has its own tab so a
            // pro can spot the time-critical ones without scanning everything.
            $base->where('source', '!=', 'direct_offer')->where(fn ($w) => $w->whereNull('source')->orWhere('source', '!=', 'esr'));
        }

        match ($sort) {
            'newest' => $base->latest('id'),
            'budget' => $base->orderByRaw('budget IS NULL, budget DESC'),
            default  => $base->orderByRaw('starts_at IS NULL, starts_at ASC'),
        };

        $events = $base->get();

        /*
         * Rule R33 §7 — an expired listing takes no new proposals, so it does
         * not belong on a board whose entire purpose is finding work to bid
         * on. Same reasoning as the R38 filter above: search hides what the
         * viewer cannot act on, and a gig that would refuse the bid is worse
         * than a shorter list.
         *
         * Filtered here rather than in SQL because expiry is derived — a
         * stored flag would need a scheduled job and would be wrong for
         * everyone who loaded the page before it ran.
         *
         * Direct offers are exempt: they are unpublished by design and reach
         * this pro by name, not by deadline.
         */
        $events = $events->filter(
            fn ($e) => $e->source === 'direct_offer' || RequestLifecycle::acceptsProposals($e),
        )->values();

        /*
         * §2's ranking — Published Today, then Extended, then older active.
         *
         * Applied on top of whatever sort the professional chose, so a
         * reopened listing is never boosted to the very top. Without it,
         * paying to extend repeatedly would buy permanent first place, which
         * is the thing the rule names.
         */
        $events = $events
            ->sortBy(fn ($e) => RequestLifecycle::rankBucket($e), SORT_REGULAR, false)
            ->values();

        // Scope is a service COUNT, which SQL can't filter on before the
        // categories are loaded — so it is applied here.
        if ($scope !== '') {
            $events = $events->filter(fn ($e) => $this->scopeOf($e) === $scope)->values();
        }

        // Tiered early access — ER + MSR only. Elite sees them on post; Pro and
        // Starter unlock 60 minutes later. SSR is open to every tier. Locked
        // gigs are withheld, and the count is stated as "unlocked to you" in the
        // view rather than claiming none exist.
        $lockedCount = 0;
        $events = $events->reject(function ($e) use ($user, &$lockedCount) {
            if (! $this->isLockedFor($e, $user)) {
                return false;
            }
            $lockedCount++;

            return true;
        })->values();

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total   = $events->count();
        $events  = $events->forPage($page, $perPage)->values();

        // Real sealed-bid data: per-gig bid count + this pro's own bid (if any).
        $ids = $events->pluck('id');
        $bidCounts = Bid::whereIn('event_id', $ids)
            ->selectRaw('event_id, COUNT(*) as c')->groupBy('event_id')->pluck('c', 'event_id');
        $myBids = Bid::where('supplier_id', $user?->id)
            ->whereIn('event_id', $ids)->get()->keyBy('event_id');

        /*
         * Checklist row 162 (R12) — an MSR renders as one gig PER SERVICE.
         *
         * "DJ + Lighting + MC" was one card. It is three jobs: three separate
         * contracts, three separate bids, three different professionals in
         * the usual case. A lighting company scrolling the board had to open
         * a card titled after somebody else's trade to find out whether their
         * own service was in it.
         *
         * Bids already carry a category_id, so the data supported this; only
         * the board did not.
         *
         * What is deliberately NOT split: the budget. The row's example shows
         * a figure per service, but a request has ONE budget covering all of
         * them — there is no per-service budget anywhere in the data. Dividing
         * it up would invent three numbers the client never gave. Each card
         * shows the request's budget, labelled as covering the whole request,
         * until a client can enter one per service.
         */
        $perService = collect();

        foreach ($events as $e) {
            $services = $e->categories->unique('id')->values();

            if ($this->scopeOf($e) !== 'multi' || $services->count() < 2) {
                $perService->push([$e, null]);
                continue;
            }

            foreach ($services as $service) {
                $perService->push([$e, $service]);
            }
        }

        $gigs = $perService->map(function ($pair) use ($myBids, $user, $savedIds) {
            [$e, $service] = $pair;

            // Counted for THIS service line, not for the whole request — the
            // shared count was the other half of the same problem: "12 bids"
            // on a card when eleven of them were for a different trade.
            $count = Bid::where('event_id', $e->id)
                ->when($service, fn ($q) => $q->where('category_id', $service->id))
                ->count();

            $mine = $service
                ? Bid::where('event_id', $e->id)->where('supplier_id', $user?->id)
                      ->where('category_id', $service->id)->first()
                : $myBids->get($e->id);

            $g = $this->mapEvent($e, $count, $mine, $user, $service);
            $g['saved'] = $savedIds->contains($e->id);

            return $g;
        })->all();

        return view('professional.bidding-board.index', [
            'gigs'          => $gigs,
            'counts'        => $this->tabCounts($user, $savedIds),
            'filters'       => compact('tab', 'scope', 'q', 'catId', 'city', 'window', 'sort', 'view'),
            'categories'    => \App\Models\Category::active()->bookableServices()
                                ->orderBy('name')->get(['id', 'name'])->unique('name')->take(60),
            'page'          => $page,
            'perPage'       => $perPage,
            'total'         => $total,
            'commissionPct' => Commission::rateFor($user),
            'lockedCount'   => $lockedCount,
            'isElite'       => $this->isElite($user),
            'myActivity'    => $this->myActivity($user),
            'insights'      => $this->insights(),
            // Packages and Invite Only appear as tabs in Peter's mockups but have
            // no model yet — no package_requests, no event_invites table. Left off
            // rather than rendered as tabs that can only ever show nothing.
        ]);
    }

    /** Counts for the tab strip — over the whole board, not the current page. */
    /**
     * Row 111 — "All Opportunities: 54" against tabs summing to 47.
     *
     * The header total and the tabs were computed from different queries:
     * this one skipped the R38 state filter and the R33 expiry filter the
     * LIST applies, so it counted gigs the professional could never see.
     *
     * One pipeline now, and `all` is the SUM of the type tabs rather than a
     * separately-counted set — so they cannot drift apart whatever the
     * filters do. Saved is deliberately outside the sum: a saved gig is also
     * a BR or an ER, and adding it would double-count it.
     */
    private function tabCounts(?\App\Models\User $user, \Illuminate\Support\Collection $savedIds): array
    {
        $query = Event::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($outer) use ($user) {
                $outer->where(fn ($q1) => $q1->where('is_published', true)
                                              ->whereNull('supplier_id'))
                      ->orWhere(fn ($q2) => $q2->where('source', 'direct_offer')
                                                ->where('supplier_id', $user?->id));
            })
            ->with('categories:id');

        // The same two filters the list applies, in the same order.
        StateMatching::scopeForViewer($query, $user);

        $open = $query->get()
            ->filter(fn ($e) => $e->source === 'direct_offer' || RequestLifecycle::acceptsProposals($e))
            ->reject(fn ($e) => $this->isLockedFor($e, $user));

        $br = $open->filter(fn ($e) => $this->typeOf($e) === 'BR')->count();
        $er = $open->where('source', 'esr')->count();
        $dr = $open->where('source', 'direct_offer')->count();

        return [
            'all'   => $br + $er + $dr,
            'BR'    => $br,
            'ER'    => $er,
            'DR'    => $dr,
            'saved' => $savedIds->count(),
        ];
    }


    /** This pro's own bid pipeline, for the right rail. */
    private function myActivity(?\App\Models\User $user): array
    {
        if (! $user) {
            return ['drafts' => 0, 'submitted' => 0, 'negotiating' => 0, 'won' => 0];
        }

        $byStatus = Bid::where('supplier_id', $user->id)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'drafts'      => (int) ($byStatus['draft'] ?? 0),
            'submitted'   => (int) ($byStatus['submitted'] ?? 0),
            'negotiating' => (int) ($byStatus['negotiating'] ?? 0),
            'won'         => (int) ($byStatus['accepted'] ?? 0),
        ];
    }

    /** Demand/volume insights. Never anything derived from bid AMOUNTS —
     *  bids are sealed, and aggregating them for competitors is a disclosure. */
    private function insights(): array
    {
        $open = Event::where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('categories:id,name')->get();

        $topCat = $open->flatMap(fn ($e) => $e->categories->pluck('name'))
            ->countBy()->sortDesc()->keys()->first();
        $avgBudget = $open->filter(fn ($e) => $e->budget)->avg('budget');
        $closingSoon = $open->filter(fn ($e) => $e->starts_at && $e->starts_at->isBetween(now(), now()->addWeek()))->count();

        return [
            ['Highest Demand', $topCat ?: 'No open requests', '🔥'],
            ['Open Requests', (string) $open->count(), '📋'],
            ['Closing This Week', (string) $closingSoon, '⏳'],
            ['Typical Client Budget', $avgBudget ? '$' . number_format((float) $avgBudget) : 'Varies', '💰'],
        ];
    }

    /** The request TYPE — how it reaches professionals. */
    private function typeOf(Event $e): string
    {
        return match ($e->source) {
            'esr'          => 'ER',
            'direct_offer' => 'DR',
            default        => 'BR',
        };
    }

    /** The request SCOPE — single or multi service, which is the service count. */
    private function scopeOf(Event $e): string
    {
        return $e->categories->count() >= 2 ? 'multi' : 'single';
    }

    /** Bookmark / un-bookmark an opportunity. */
    public function toggleSaved(Request $request): RedirectResponse
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id']]);
        $user = $request->user();

        $saved = $user->savedEvents();
        if ($saved->where('events.id', $data['event_id'])->exists()) {
            $saved->detach($data['event_id']);
            $msg = 'Removed from saved opportunities.';
        } else {
            $saved->syncWithoutDetaching([$data['event_id']]);
            $msg = 'Saved. Find it under the Saved tab.';
        }

        return back()->with('status', $msg);
    }

    /** Elite is the tier with immediate ER/MSR access. */
    private function isElite(?\App\Models\User $user): bool
    {
        return $user?->activeSubscription()?->plan?->slug === 'enterprise';
    }

    /**
     * Tiered early access, ER + MSR only: Elite immediately, Pro and Starter
     * 60 minutes after posting. SSR is open to every tier.
     */
    private function isLockedFor(Event $e, ?\App\Models\User $user): bool
    {
        $tiered = $e->source === 'esr' || $e->categories->count() >= 2;   // ER or MSR
        if (! $tiered || $this->isElite($user)) {
            return false;
        }

        $posted = $e->published_at ?? $e->created_at;

        return $posted && $posted->gt(now()->subMinutes(self::TIER_DELAY_MINUTES));
    }

    /** Place (or update) a sealed bid on an open gig. */
    public function placeBid(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id'    => ['required', 'exists:events,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'amount'      => ['required', 'integer', 'min:1', 'max:10000000'],
            'note'        => ['nullable', 'string', 'max:1000'],
            'is_public'   => ['nullable', 'boolean'],
        ]);

        $event = Event::where('id', $data['event_id'])
            ->where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->firstOrFail();

        // Per-service (MSR) bid: the chosen service must be one of the event's
        // gigs. null category = a whole-event / single-service bid.
        $categoryId = $data['category_id'] ?? null;
        if ($categoryId && ! $event->categories()->whereKey($categoryId)->exists()) {
            return back()->withErrors(['category_id' => 'That service is not part of this event.']);
        }

        Bid::updateOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $request->user()->id, 'category_id' => $categoryId],
            [
                'amount'    => $data['amount'],
                'note'      => $data['note'] ?? null,
                'is_public' => $request->boolean('is_public'),   // sealed unless the pro opts in
                'status'    => 'submitted',
            ],
        );

        return back()->with('status', 'Your sealed bid was submitted. Only you and the client can see the amount.');
    }

    /** Toggle a bid between sealed and public (the bidder's opt-in). */
    public function toggleBidVisibility(Request $request, Bid $bid): RedirectResponse
    {
        abort_unless($bid->supplier_id === $request->user()->id, 403);
        $bid->update(['is_public' => ! $bid->is_public]);

        return back()->with('status', $bid->is_public
            ? 'Your bid amount is now public.'
            : 'Your bid amount is sealed again.');
    }

    /** Post a reply / counter-offer back to the client (negotiation loop). */
    public function reply(Request $request, Bid $bid): RedirectResponse
    {
        abort_unless($bid->supplier_id === $request->user()->id, 403);

        $data = $request->validate([
            'note'           => ['nullable', 'required_without:counter_amount', 'string', 'max:1000'],
            'counter_amount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
        ]);

        $bid->replies()->create([
            'user_id'        => $request->user()->id,
            'counter_amount' => $data['counter_amount'] ?? null,
            'note'           => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Reply sent to the client.');
    }

    /** The pro's own bids across all gigs, with seal/reveal control. */
    /**
     * Bid states shown on My Bids.
     *
     * Only 'submitted' and 'withdrawn' are stored — everything else is DERIVED
     * from facts that already exist, so the page can't drift from reality:
     * an award on the event decides won vs not-selected, a reply on the thread
     * means a negotiation is live, and a past event date means the chance is
     * gone. Peter's mockup also lists Drafts and Declined; there is no
     * draft-save flow and no client-decline record, so those aren't offered as
     * tabs that could only ever read zero.
     */
    public const BID_STATES = ['all', 'submitted', 'negotiating', 'won', 'not_selected', 'withdrawn', 'expired'];

    public function myBids(Request $request): View
    {
        $user = $request->user();

        $state = in_array($request->query('state'), self::BID_STATES, true) ? $request->query('state') : 'all';
        $type  = in_array($request->query('type'), ['BR', 'ER', 'DR'], true) ? $request->query('type') : '';
        $scope = in_array($request->query('scope'), self::SCOPES, true) ? (string) $request->query('scope') : '';
        $q     = trim((string) $request->query('q', ''));

        $all = Bid::where('supplier_id', $user->id)
            ->with(['event.categories:id,name', 'event.client:id,name', 'category:id,name', 'replies.user:id,name'])
            ->latest()
            ->get();

        // One query for every award on the events this pro bid on, instead of
        // asking per row.
        $awards = \App\Models\Booking::whereIn('event_id', $all->pluck('event_id')->filter())
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->keyBy('event_id');

        $rows = $all->map(function (Bid $b) use ($awards, $user) {
            $e     = $b->event;
            $award = $e ? $awards->get($e->id) : null;

            return [
                'bid'       => $b,
                'event'     => $e,
                'state'     => $this->bidState($b, $award, $user),
                'type'      => $e ? $this->typeOf($e) : 'BR',
                'scope'     => $e ? $this->scopeOf($e) : 'single',
                'lastReply' => $b->replies->last(),
                'net'       => Commission::netOf($b->amount, $user),
            ];
        });

        if ($state !== 'all') {
            $rows = $rows->where('state', $state);
        }
        if ($type !== '') {
            $rows = $rows->where('type', $type);
        }
        if ($scope !== '') {
            $rows = $rows->where('scope', $scope);
        }
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower(($r['event']->title ?? '') . ' ' . ($r['event']->client->name ?? '') . ' ' . ($r['bid']->category->name ?? '')), $needle));
        }
        $rows = $rows->values();

        // Counts are over ALL of this pro's bids, not the filtered view — a tab
        // that changed its own count when you clicked it would be useless.
        $everyRow = $all->map(fn (Bid $b) => [
            'state' => $this->bidState($b, $b->event ? $awards->get($b->event->id) : null, $user),
            'type'  => $b->event ? $this->typeOf($b->event) : 'BR',
        ]);

        return view('professional.bidding-board.my-bids', [
            'rows'    => $rows,
            'filters' => compact('state', 'type', 'scope', 'q'),
            'counts'  => [
                'all'          => $everyRow->count(),
                'submitted'    => $everyRow->where('state', 'submitted')->count(),
                'negotiating'  => $everyRow->where('state', 'negotiating')->count(),
                'won'          => $everyRow->where('state', 'won')->count(),
                'not_selected' => $everyRow->where('state', 'not_selected')->count(),
                'withdrawn'    => $everyRow->where('state', 'withdrawn')->count(),
                'expired'      => $everyRow->where('state', 'expired')->count(),
            ],
            'typeCounts' => [
                'BR' => $everyRow->where('type', 'BR')->count(),
                'ER' => $everyRow->where('type', 'ER')->count(),
                'DR' => $everyRow->where('type', 'DR')->count(),
            ],
            // Net of commission, because that is what the pro actually receives.
            'payout' => [
                'pct'         => Commission::rateFor($user),
                'won'         => $rows->where('state', 'won')->sum('net'),
                'negotiating' => $rows->where('state', 'negotiating')->sum('net'),
                'submitted'   => $rows->where('state', 'submitted')->sum('net'),
            ],
        ]);
    }

    /** Derive a bid's state — see BID_STATES for why almost none of it is stored. */
    private function bidState(Bid $bid, ?\App\Models\Booking $award, \App\Models\User $user): string
    {
        if ($bid->status === 'withdrawn') {
            return 'withdrawn';
        }
        if ($award) {
            return (int) $award->supplier_id === (int) $user->id ? 'won' : 'not_selected';
        }
        if ($bid->event?->starts_at && $bid->event->starts_at->isPast()) {
            return 'expired';
        }

        return $bid->replies->isNotEmpty() ? 'negotiating' : 'submitted';
    }

    /** Withdraw an open bid. Only the bidder, and only while nothing is awarded. */
    public function withdrawBid(Request $request, Bid $bid): RedirectResponse
    {
        abort_unless((int) $bid->supplier_id === (int) $request->user()->id, 403);

        $awarded = \App\Models\Booking::where('event_id', $bid->event_id)
            ->whereNotIn('status', ['cancelled'])->exists();
        if ($awarded) {
            return back()->withErrors(['bid' => 'This request has already been awarded — the bid can no longer be withdrawn.']);
        }

        $bid->update(['status' => 'withdrawn']);

        return back()->with('status', 'Bid withdrawn.');
    }

    /** Map a real Event to the bidding-board gig card shape. */
    private function mapEvent(Event $e, int $bidCount = 0, ?Bid $myBid = null, ?\App\Models\User $viewer = null, ?\App\Models\Category $service = null): array
    {
        // On an MSR this card is ONE service line of the request (row 162).
        $cats = $service ? [$service->name] : $e->categories->pluck('name')->all();

        // This used to read ER / MSR / SSR — mixing the type with the scope on
        // the one badge, so a card could say "MSR" while the tab above it said
        // BR. They are different questions and both get answered: typeOf() is
        // how the request reaches professionals, scopeOf() is how many services
        // are in it.
        $type  = $this->typeOf($e);
        $scope = $this->scopeOf($e) === 'multi' ? 'MSR' : 'SSR';
        $days  = $e->starts_at ? (int) round(now()->diffInDays($e->starts_at, false)) : null;
        $stock = ['photo-1519741497674-611481863552', 'photo-1511795409834-ef04bbd61622', 'photo-1530103862676-de8c9debad1d', 'photo-1492684223066-81342ee5ff30'];

        // A past-dated request can't be bid on — it reads Expired and loses
        // Place Bid, instead of sitting on the board looking open.
        $expired = $e->starts_at && $e->starts_at->isPast();
        $fit     = $this->fitScore($e, $viewer);

        return [
            'type'   => $type,
            'scope'  => $scope,
            // A rush request is urgent by definition — don't let a needed-by
            // date further out quietly drop the flag that's the whole point.
            'urgent' => ! $expired && ($type === 'ER' || ($days !== null && $days >= 0 && $days <= 3)),
            'expired' => $expired,
            'title'  => $service ? $service->name . ' — ' . $e->title : $e->title,
            'service_id'   => $service?->id,
            'service_name' => $service?->name,
            'desc'   => Str::limit($e->description ?: 'Open gig — full details available on request.', 140),
            'loc'    => $e->location ?: 'Location flexible',
            'date'   => $e->starts_at ? $e->starts_at->format('M j, Y') : 'Flexible',
            /*
             * Checklist row 110 — this read `50 + ($e->id % 250)`.
             *
             * A guest count invented from the primary key, sitting beside a
             * description that stated the real number: "catering for 200"
             * with a 114 Guests icon. Two independent cards were reported
             * with the same fault because every card had it.
             *
             * The real figure, or nothing. A professional prices a job on
             * head count.
             */
            'guests' => $e->guest_count ?: null,
            'tags'   => $cats ?: ['General'],
            // ER budget is a single fixed figure; SSR/MSR quote a range.
            // One budget covers the whole request. See the note above the
            // per-service split: dividing it would invent a figure per line.
            'budget' => $e->budget
                ? ($type === 'ER'
                    ? '$' . number_format($e->budget)
                    : '$' . number_format($e->budget * 0.85) . ' – $' . number_format($e->budget))
                : 'Open budget',
            'budget_is_whole_request' => $service !== null,
            /*
             * Rows 106, 139, 141 and 151 — one countdown, one format, and
             * computed from THIS listing's own deadline.
             *
             * The urgent cards all rendered `data-countdown="6300"` — the
             * same hardcoded hour and three quarters — which is why two
             * events five days apart showed the same time to the second.
             * Nothing was being computed at all.
             *
             * It counts to the PROPOSAL DEADLINE, not the event date: the
             * deadline is what a professional is racing, and a three-day-out
             * deadline read as "Tomorrow" because the card was measuring the
             * wrong thing and rounding it.
             */
            'time'    => self::timeLeft($e),
            'seconds' => self::secondsLeft($e),
            'match'  => $fit,
            // Stars must track the percentage — 80/93/96% can't all be 5 stars.
            'rating' => max(1, (int) ceil($fit / 20)),
            'bids'   => $bidCount,                    // real sealed-bid count
            'img'    => $stock[$e->id % count($stock)],
            'event_id' => $e->id,
            'my_bid' => $myBid ? ['amount' => $myBid->amount, 'is_public' => $myBid->is_public] : null,
            // Per-service bidding: the event's services the pro can bid on
            // individually (MSR = each service is its own gig).
            'services' => $e->categories->unique('name')->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
        ];
    }

    /**
     * "Xd Yh left" — the one format, everywhere (row 151).
     *
     * Floors rather than rounds. Rounding is what turned three days into
     * "Tomorrow": a deadline 3 days and 2 hours out is 3 days left, not 4,
     * and never 1.
     */
    private static function timeLeft(Event $e): string
    {
        $seconds = self::secondsLeft($e);

        if ($seconds === null) {
            return 'Open';
        }

        if ($seconds <= 0) {
            return 'Closed';
        }

        $days  = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);

        return $days > 0
            ? "{$days}d {$hours}h left"
            : ($hours > 0 ? "{$hours}h left" : 'Under an hour left');
    }

    /** Seconds to this listing's own proposal deadline. Null when it has none. */
    private static function secondsLeft(Event $e): ?int
    {
        if ($e->proposal_deadline === null) {
            return null;
        }

        return (int) max(0, now()->diffInSeconds($e->proposal_deadline, false));
    }

    /**
     * Fit Score — now App\Support\FitScore.
     *
     * Moved out when the Opportunity Feed needed the same number. Two copies
     * would be two chances to disagree, and one gig showing 90% here and 70%
     * in the feed is how a professional stops believing the figure anywhere.
     */
    private function fitScore(Event $e, ?\App\Models\User $viewer): int
    {
        return \App\Support\FitScore::for($e, $viewer);
    }
}
