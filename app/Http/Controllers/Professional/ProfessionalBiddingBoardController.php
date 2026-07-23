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

    public function index(Request $request): View
    {
        $user = $request->user();

        // Real open gigs: published & still biddable.
        $events = Event::query()
            ->where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('categories:id,name')
            ->orderByRaw('starts_at IS NULL, starts_at ASC')
            ->limit(15)
            ->get();

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

        // Real sealed-bid data: per-gig bid count + this pro's own bid (if any).
        $ids = $events->pluck('id');
        $bidCounts = Bid::whereIn('event_id', $ids)
            ->selectRaw('event_id, COUNT(*) as c')->groupBy('event_id')->pluck('c', 'event_id');
        $myBids = Bid::where('supplier_id', $user?->id)
            ->whereIn('event_id', $ids)->get()->keyBy('event_id');

        $gigs = $events->map(fn ($e) => $this->mapEvent($e, (int) ($bidCounts[$e->id] ?? 0), $myBids->get($e->id), $user))->all();

        $counts = [
            'all' => count($gigs),
            'ESR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'ESR')),
            'SSR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'SSR')),
            'MSR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'MSR')),
        ];

        // Insights computed from the real open gigs.
        $topCat = $events->flatMap(fn ($e) => $e->categories->pluck('name'))
            ->countBy()->sortDesc()->keys()->first() ?: 'Photography';
        $avgBudget = $events->filter(fn ($e) => $e->budget)->avg('budget');
        $closingSoon = $events->filter(fn ($e) => $e->starts_at && $e->starts_at->isBetween(now(), now()->addDays(7)))->count();

        // Commission the pro absorbs at payout, by membership tier — shown on
        // the bid form so they bid knowing their net (MSR review #17). Shared
        // so this preview and the payout screens can't drift apart.
        $commissionPct = Commission::rateFor($request->user());

        return view('professional.bidding-board.index', [
            'gigs'     => $gigs,
            'counts'   => $counts,
            'commissionPct' => $commissionPct,
            'lockedCount'   => $lockedCount,
            'isElite'       => $this->isElite($user),
            // Demand/volume only. "Avg. Winning Bid" and "Win Rate" were here and
            // are gone on purpose: bids are sealed, and aggregating sealed amounts
            // into a stat shown to competitors is still a disclosure. Metrics that
            // count REQUESTS are fine; metrics derived from AMOUNTS are not.
            'insights' => [
                ['Highest Demand', $topCat, '🔥'],
                ['Open Requests', (string) count($gigs), '📋'],
                ['Closing This Week', (string) $closingSoon, '⏳'],
                ['Typical Client Budget', $avgBudget ? '$' . number_format((float) $avgBudget) : 'Varies', '💰'],
            ],
        ]);
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
    public function myBids(Request $request): View
    {
        $bids = Bid::where('supplier_id', $request->user()->id)
            ->with(['event:id,title,starts_at,status', 'category:id,name', 'replies.user:id,name'])
            ->latest()
            ->paginate(15);

        return view('professional.bidding-board.my-bids', compact('bids'));
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
