<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client-side Proposals view. A "proposal" here is a professional's bid on
 * one of the client's published events (Fix Spec: after a posting is
 * published, bids arrive asynchronously here). Sourced from the Bid model
 * so the loop is real: MSR/SSR/ESR publish → pro bids on the Bidding Board
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
            ->with(['event:id,title,starts_at,location,status', 'event.categories:id,name', 'supplier:id,name'])
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
}
