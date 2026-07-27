<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Event;
use App\Support\Commission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Main Bidding Board.
 *
 * Every OPEN client gig in one place to bid on. Gigs are REAL published Events
 * (not completed/cancelled). ESR is read from the event's source — a rush
 * request can be single-service, so counting services would mislabel it;
 * SSR vs MSR is then the service count. Match-score and images are
 * representative fields until the scoring model lands.
 */
class ProfessionalBiddingBoardController extends Controller
{
    /** Non-Elite tiers unlock ESR/MSR this many minutes after posting. */
    private const TIER_DELAY_MINUTES = 60;

    /**
     * Board tabs = the request TYPE. Peter's model (2026-07-27): BSR is
     * broadcast bidding, ESR is the same mechanism with urgency on top, DSR is
     * targeted at one professional and never bid on. SSR and MSR are NOT types
     * here — they are the scope (single vs multi service) inside each, and are
     * filtered separately. Packages and Invite Only are in the mockups but have
     * no model yet — see the note in index().
     */
    public const TABS = ['all', 'BSR', 'ESR', 'DSR', 'saved'];

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
                $outer->where('is_published', true)
                      ->orWhere(fn ($q2) => $q2->where('source', 'direct_offer')
                                                ->where('supplier_id', $user?->id));
            })
            ->with('categories:id,name');

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
        } elseif ($tab === 'DSR') {
            $base->where('source', 'direct_offer');
        } elseif ($tab === 'ESR') {
            $base->where('source', 'esr');
        } elseif ($tab === 'BSR') {
            // Bidding, but not the emergency flavour — ESR has its own tab so a
            // pro can spot the time-critical ones without scanning everything.
            $base->where('source', '!=', 'direct_offer')->where(fn ($w) => $w->whereNull('source')->orWhere('source', '!=', 'esr'));
        }

        match ($sort) {
            'newest' => $base->latest('id'),
            'budget' => $base->orderByRaw('budget IS NULL, budget DESC'),
            default  => $base->orderByRaw('starts_at IS NULL, starts_at ASC'),
        };

        $events = $base->get();

        // Scope is a service COUNT, which SQL can't filter on before the
        // categories are loaded — so it is applied here.
        if ($scope !== '') {
            $events = $events->filter(fn ($e) => $this->scopeOf($e) === $scope)->values();
        }

        // Tiered early access — ESR + MSR only. Elite sees them on post; Pro and
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

        $gigs = $events->map(function ($e) use ($bidCounts, $myBids, $user, $savedIds) {
            $g = $this->mapEvent($e, (int) ($bidCounts[$e->id] ?? 0), $myBids->get($e->id), $user);
            $g['saved'] = $savedIds->contains($e->id);

            return $g;
        })->all();

        return view('professional.bidding-board.index', [
            'gigs'          => $gigs,
            'counts'        => $this->tabCounts($user, $savedIds),
            'filters'       => compact('tab', 'scope', 'q', 'catId', 'city', 'window', 'sort', 'view'),
            'categories'    => \App\Models\Category::active()->whereNotNull('parent_id')
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
    private function tabCounts(?\App\Models\User $user, \Illuminate\Support\Collection $savedIds): array
    {
        $open = Event::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($outer) use ($user) {
                $outer->where('is_published', true)
                      ->orWhere(fn ($q2) => $q2->where('source', 'direct_offer')
                                                ->where('supplier_id', $user?->id));
            })
            ->with('categories:id')
            ->get()
            ->reject(fn ($e) => $this->isLockedFor($e, $user));

        return [
            'all'   => $open->count(),
            'BSR'   => $open->filter(fn ($e) => $this->typeOf($e) === 'BSR')->count(),
            'ESR'   => $open->where('source', 'esr')->count(),
            'DSR'   => $open->where('source', 'direct_offer')->count(),
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
            'esr'          => 'ESR',
            'direct_offer' => 'DSR',
            default        => 'BSR',
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

    /** Elite is the tier with immediate ESR/MSR access. */
    private function isElite(?\App\Models\User $user): bool
    {
        return $user?->activeSubscription()?->plan?->slug === 'enterprise';
    }

    /**
     * Tiered early access, ESR + MSR only: Elite immediately, Pro and Starter
     * 60 minutes after posting. SSR is open to every tier.
     */
    private function isLockedFor(Event $e, ?\App\Models\User $user): bool
    {
        $tiered = $e->source === 'esr' || $e->categories->count() >= 2;   // ESR or MSR
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
        $type  = in_array($request->query('type'), ['BSR', 'ESR', 'DSR'], true) ? $request->query('type') : '';
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
                'type'      => $e ? $this->typeOf($e) : 'BSR',
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
            'type'  => $b->event ? $this->typeOf($b->event) : 'BSR',
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
                'BSR' => $everyRow->where('type', 'BSR')->count(),
                'ESR' => $everyRow->where('type', 'ESR')->count(),
                'DSR' => $everyRow->where('type', 'DSR')->count(),
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
    private function mapEvent(Event $e, int $bidCount = 0, ?Bid $myBid = null, ?\App\Models\User $viewer = null): array
    {
        $cats  = $e->categories->pluck('name')->all();
        // ESR is explicit (source), not guessed from service count.
        $type  = $e->source === 'esr' ? 'ESR' : (count($cats) >= 2 ? 'MSR' : 'SSR');
        $days  = $e->starts_at ? (int) round(now()->diffInDays($e->starts_at, false)) : null;
        $stock = ['photo-1519741497674-611481863552', 'photo-1511795409834-ef04bbd61622', 'photo-1530103862676-de8c9debad1d', 'photo-1492684223066-81342ee5ff30'];

        // A past-dated request can't be bid on — it reads Expired and loses
        // Place Bid, instead of sitting on the board looking open.
        $expired = $e->starts_at && $e->starts_at->isPast();
        $fit     = $this->fitScore($e, $viewer);

        return [
            'type'   => $type,
            // A rush request is urgent by definition — don't let a needed-by
            // date further out quietly drop the flag that's the whole point.
            'urgent' => ! $expired && ($type === 'ESR' || ($days !== null && $days >= 0 && $days <= 3)),
            'expired' => $expired,
            'title'  => $e->title,
            'desc'   => Str::limit($e->description ?: 'Open gig — full details available on request.', 140),
            'loc'    => $e->location ?: 'Location flexible',
            'date'   => $e->starts_at ? $e->starts_at->format('M j, Y') : 'Flexible',
            'guests' => 50 + ($e->id % 250),
            'tags'   => $cats ?: ['General'],
            // ESR budget is a single fixed figure; SSR/MSR quote a range.
            'budget' => $e->budget
                ? ($type === 'ESR'
                    ? '$' . number_format($e->budget)
                    : '$' . number_format($e->budget * 0.85) . ' – $' . number_format($e->budget))
                : 'Open budget',
            'time'   => $expired ? 'Expired' : (($days !== null && $days >= 0) ? ($days . ($days === 1 ? ' day left' : ' days left')) : 'Open'),
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
     * Fit Score — one rules-based 0–100 value, no AI ranking:
     * category 40 · in-area 20 · availability 20 · rating/history 20.
     * Replaces a placeholder derived from the event id, which moved the number
     * around without ever meaning anything.
     */
    private function fitScore(Event $e, ?\App\Models\User $viewer): int
    {
        if (! $viewer) {
            return 0;
        }

        $score = 0;

        // Category (40) — does the pro work in any service this request needs?
        // There's no user→category link, so a pro's categories are the ones on
        // the packages they've published.
        $mine = \App\Models\Package::where('user_id', $viewer->id)
            ->whereNotNull('category_id')->distinct()->pluck('category_id')->all();
        if ($mine && $e->categories->pluck('id')->intersect($mine)->isNotEmpty()) {
            $score += 40;
        }

        // In-area (20) — the request's location names the pro's city or state.
        $city  = $viewer->profile?->city;
        $state = $viewer->profile?->state;
        if ($e->location) {
            $loc = Str::lower($e->location);
            if (($city && Str::contains($loc, Str::lower($city))) || ($state && Str::contains($loc, Str::lower($state)))) {
                $score += 20;
            }
        }

        // Availability (20) — nothing else already booked on that date.
        if ($e->starts_at) {
            $clash = \App\Models\Booking::where('supplier_id', $viewer->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereHas('event', fn ($q) => $q->whereDate('starts_at', $e->starts_at->toDateString()))
                ->exists();
            if (! $clash) {
                $score += 20;
            }
        } else {
            $score += 20;   // undated request can't clash
        }

        // Rating / history (20) — scaled from the pro's average review.
        $avg = (float) $viewer->reviewsReceived()->where('is_hidden', false)->avg('rating');
        $score += $avg > 0 ? (int) round(($avg / 5) * 20) : 10;   // unrated sits mid

        return min(100, $score);
    }
}
