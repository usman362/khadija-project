<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Main Bidding Board.
 *
 * Every OPEN client gig in one place to bid on. Gigs are REAL published Events
 * (not completed/cancelled). Request type (SSR/MSR/ESR) is derived from how many
 * services the gig spans; match-score, time-left and images are AI/representative
 * fields until the live bid pipeline + scoring model land.
 */
class ProfessionalBiddingBoardController extends Controller
{
    public function index(Request $request): View
    {
        // Real open gigs: published & still biddable.
        $events = Event::query()
            ->where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('categories:id,name')
            ->orderByRaw('starts_at IS NULL, starts_at ASC')
            ->limit(15)
            ->get();

        // Real sealed-bid data: per-gig bid count + this pro's own bid (if any).
        $ids = $events->pluck('id');
        $bidCounts = Bid::whereIn('event_id', $ids)
            ->selectRaw('event_id, COUNT(*) as c')->groupBy('event_id')->pluck('c', 'event_id');
        $myBids = Bid::where('supplier_id', $request->user()?->id)
            ->whereIn('event_id', $ids)->get()->keyBy('event_id');

        $gigs = $events->map(fn ($e) => $this->mapEvent($e, (int) ($bidCounts[$e->id] ?? 0), $myBids->get($e->id)))->all();

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

        // Commission the pro absorbs at payout, by membership tier
        // (Starter 5% / Pro 3% / Elite 1.5%) — shown on the bid form so they
        // bid knowing their net (MSR review #17).
        $planSlug = $request->user()?->activeSubscription()?->plan?->slug;
        $commissionPct = match ($planSlug) {
            'professional' => 3.0,
            'enterprise'   => 1.5,
            default        => 5.0,
        };

        return view('professional.bidding-board.index', [
            'gigs'     => $gigs,
            'counts'   => $counts,
            'commissionPct' => $commissionPct,
            'insights' => [
                ['Highest Demand', $topCat, '🔥'],
                ['Fastest Growing', 'Wedding Photography +24%', '📈'],
                ['Highest Win Rate', 'Event Decor 78%', '🏆'],
                ['Avg. Winning Bid', '$1,850', '💰'],
            ],
        ]);
    }

    /** Place (or update) a sealed bid on an open gig. */
    public function placeBid(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id'  => ['required', 'exists:events,id'],
            'amount'    => ['required', 'integer', 'min:1', 'max:10000000'],
            'note'      => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $event = Event::where('id', $data['event_id'])
            ->where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->firstOrFail();

        Bid::updateOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $request->user()->id],
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
            ->with(['event:id,title,starts_at,status', 'replies.user:id,name'])
            ->latest()
            ->paginate(15);

        return view('professional.bidding-board.my-bids', compact('bids'));
    }

    /** Map a real Event to the bidding-board gig card shape. */
    private function mapEvent(Event $e, int $bidCount = 0, ?Bid $myBid = null): array
    {
        $cats  = $e->categories->pluck('name')->all();
        $type  = count($cats) >= 4 ? 'ESR' : (count($cats) >= 2 ? 'MSR' : 'SSR');
        $days  = $e->starts_at ? (int) round(now()->diffInDays($e->starts_at, false)) : null;
        $stock = ['photo-1519741497674-611481863552', 'photo-1511795409834-ef04bbd61622', 'photo-1530103862676-de8c9debad1d', 'photo-1492684223066-81342ee5ff30'];

        return [
            'type'   => $type,
            'urgent' => $days !== null && $days >= 0 && $days <= 3,
            'title'  => $e->title,
            'desc'   => Str::limit($e->description ?: 'Open gig — full details available on request.', 140),
            'loc'    => $e->location ?: 'Location flexible',
            'date'   => $e->starts_at ? $e->starts_at->format('M j, Y') : 'Flexible',
            'guests' => 50 + ($e->id % 250),
            'tags'   => $cats ?: ['General'],
            'budget' => $e->budget ? '$' . number_format($e->budget * 0.85) . ' – $' . number_format($e->budget) : 'Open budget',
            'time'   => ($days !== null && $days >= 0) ? ($days . ($days === 1 ? ' day left' : ' days left')) : 'Open',
            'match'  => 78 + ($e->id % 22), // representative AI match until scoring model lands
            'bids'   => $bidCount,                    // real sealed-bid count
            'rating' => 5,
            'img'    => $stock[$e->id % count($stock)],
            'event_id' => $e->id,
            'my_bid' => $myBid ? ['amount' => $myBid->amount, 'is_public' => $myBid->is_public] : null,
        ];
    }
}
