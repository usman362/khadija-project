<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client-side Proposals view. A "proposal" here is a professional's bid on
 * one of the client's published events (Fix Spec: after a posting is
 * published, bids arrive asynchronously here). Sourced from the Bid model
 * so the loop is real: MSR/SSR/ER publish → pro bids on the Bidding Board
 * → the bid appears on this page for the client to review and act on.
 *
 * Route: GET /client/proposals
 */
class ClientProposalController extends Controller
{
    /** Bid statuses grouped into the proposal-pipeline buckets. */
    private const PENDING  = ['submitted', 'shortlisted'];
    private const DECLINED = ['declined', 'withdrawn'];

    public function index(Request $request): View
    {
        $user = $request->user();

        // Every bid placed on an event this client owns.
        $base = Bid::whereHas('event', fn ($q) => $q->where('client_id', $user->id));

        $stats = [
            'submitted'   => (clone $base)->count(),
            'pending'     => (clone $base)->whereIn('status', self::PENDING)->count(),
            'accepted'    => (clone $base)->where('status', 'won')->count(),
            'in_progress' => (clone $base)->where('status', 'won')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now()))->count(),
            'completed'   => (clone $base)->where('status', 'won')
                ->whereHas('event', fn ($q) => $q->where('status', 'completed'))->count(),
            'declined'    => (clone $base)->whereIn('status', self::DECLINED)->count(),
            'drafts'      => 0,
        ];

        $tab = $request->string('tab')->toString() ?: 'all';
        $query = (clone $base)
            ->with(['event:id,title,starts_at,location,status', 'event.categories:id,name',
                'category:id,name', 'supplier:id,name', 'replies.user:id,name'])
            ->latest();

        match ($tab) {
            'pending'     => $query->whereIn('status', self::PENDING),
            'accepted'    => $query->where('status', 'won'),
            'completed'   => $query->where('status', 'won')
                ->whereHas('event', fn ($q) => $q->where('status', 'completed')),
            'declined'    => $query->whereIn('status', self::DECLINED),
            'in_progress' => $query->where('status', 'won'),
            default       => null,
        };

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->whereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$s}%"))
                ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$s}%")));
        }

        $proposals = $query->paginate(10)->withQueryString();

        // Revenue pipeline — pending vs accepted bid value.
        $pendingValue  = (float) (clone $base)->whereIn('status', self::PENDING)->sum('amount');
        $acceptedValue = (float) (clone $base)->where('status', 'won')->sum('amount');

        $pipeline = [
            'pending_value'  => $pendingValue,
            'pending_count'  => $stats['pending'],
            'accepted_value' => $acceptedValue,
            'accepted_count' => $stats['accepted'],
            'total'          => $pendingValue + $acceptedValue,
        ];

        return view('client.proposals.index', compact('stats', 'proposals', 'tab', 'pipeline'));
    }

    /**
     * Award a bid: mark it won and open a confirmed Booking (the contract).
     * Only the event's owner may act.
     */
    /**
     * Compare the proposals on ONE request side by side.
     *
     * The cross-request list at index() answers "what's happening across all my
     * events". This answers the different question the client actually has when
     * a deadline is near: of the people who bid on THIS request, who do I pick.
     */
    public function compare(Request $request, Event $event): View
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        $sort   = (string) $request->query('sort', 'amount');
        $q      = trim((string) $request->query('q', ''));
        $only   = (string) $request->query('only', '');     // verified | insured | ''

        $bids = Bid::where('event_id', $event->id)
            ->with(['supplier.profile', 'category:id,name', 'replies.user:id,name'])
            ->get();

        // Rating and review count per professional, in one query rather than per row.
        $stats = \App\Models\Review::selectRaw('reviewee_id, AVG(rating) as avg_rating, COUNT(*) as total')
            ->whereIn('reviewee_id', $bids->pluck('supplier_id')->filter())
            ->where('is_hidden', false)
            ->groupBy('reviewee_id')
            ->get()
            ->keyBy('reviewee_id');

        $awardedTo = Booking::where('event_id', $event->id)
            ->whereNotIn('status', ['cancelled'])
            ->value('supplier_id');

        $rows = $bids->map(function (Bid $b) use ($stats, $event, $awardedTo) {
            $p  = $b->supplier?->profile;
            $st = $stats->get($b->supplier_id);

            return [
                'bid'        => $b,
                'pro'        => $b->supplier,
                'profile'    => $p,
                'rating'     => $st ? round((float) $st->avg_rating, 1) : null,
                'reviews'    => $st ? (int) $st->total : 0,
                'years'      => $p?->experience_years,
                'insured'    => \App\Support\InsuranceRequirement::isCovered($p),
                'verified'   => (bool) ($p?->trade_license_verified_at && $p?->workers_comp_verified_at)
                    && \App\Support\InsuranceRequirement::isCovered($p),
                'city'       => $p?->city,
                'overBudget' => $event->budget && $b->amount > $event->budget,
                'state'      => match (true) {
                    $awardedTo && (int) $awardedTo === (int) $b->supplier_id => 'accepted',
                    (bool) $awardedTo                                        => 'not_selected',
                    in_array($b->status, self::DECLINED, true)               => 'declined',
                    $b->replies->isNotEmpty()                                => 'negotiating',
                    default                                                  => 'responded',
                },
            ];
        });

        if ($only === 'verified') {
            $rows = $rows->where('verified', true);
        } elseif ($only === 'insured') {
            $rows = $rows->where('insured', true);
        }
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower(($r['pro']->name ?? '') . ' ' . ($r['city'] ?? '')), $needle));
        }

        $rows = match ($sort) {
            'rating'  => $rows->sortByDesc(fn ($r) => $r['rating'] ?? -1),
            'newest'  => $rows->sortByDesc(fn ($r) => $r['bid']->created_at),
            'years'   => $rows->sortByDesc(fn ($r) => $r['years'] ?? -1),
            default   => $rows->sortBy(fn ($r) => $r['bid']->amount),
        };

        return view('client.proposals.compare', [
            'event'     => $event,
            'rows'      => $rows->values(),
            'total'     => $bids->count(),
            'awardedTo' => $awardedTo,
            'filters'   => compact('sort', 'q', 'only'),
            // Deliberately NOT offered as columns: distance (no coordinates are
            // stored on a profile) and response time (nothing records it). The
            // mockup lists both; inventing them would be worse than omitting.
        ]);
    }

    public function accept(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizeOwner($request, $bid);
        $bid->update(['status' => 'won']);

        // Turn the award into a real booking/contract. Keyed on the SERVICE
        // too (B6): a pro who wins two services on one event gets two bookings,
        // not one that silently swallows the second. category_id is null for a
        // whole-event (SSR) bid, and firstOrCreate matches that null.
        Booking::firstOrCreate(
            ['event_id' => $bid->event_id, 'supplier_id' => $bid->supplier_id, 'category_id' => $bid->category_id],
            [
                'client_id'  => $bid->event->client_id,
                'created_by' => $request->user()->id,
                'status'     => 'confirmed',
                'price'      => $bid->amount,
                'currency'   => 'USD',
                'booked_at'  => now(),
                'source'     => 'bid',
                'notes'      => 'Awarded from bid #' . $bid->id,
            ]
        );

        return back()->with('status', 'Bid accepted. '
            . ($bid->supplier?->name ?? 'The professional')
            . ' is awarded — a confirmed booking has been created under Bookings.');
    }

    /** Decline a bid. Only the event's owner may act. */
    public function decline(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizeOwner($request, $bid);
        $bid->update(['status' => 'declined']);

        return back()->with('status', 'Bid declined.');
    }

    /** Post a reply / counter-offer to the professional (negotiation loop). */
    public function reply(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizeOwner($request, $bid);

        $data = $request->validate([
            'note'           => ['nullable', 'required_without:counter_amount', 'string', 'max:1000'],
            'counter_amount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
        ]);

        $bid->replies()->create([
            'user_id'        => $request->user()->id,
            'counter_amount' => $data['counter_amount'] ?? null,
            'note'           => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Reply sent to '
            . ($bid->supplier?->name ?? 'the professional') . '.');
    }

    /** Guard: the bid must sit on an event this client owns. */
    private function authorizeOwner(Request $request, Bid $bid): void
    {
        abort_unless(
            $bid->event && $bid->event->client_id === $request->user()->id,
            Response::HTTP_FORBIDDEN
        );
    }
}
