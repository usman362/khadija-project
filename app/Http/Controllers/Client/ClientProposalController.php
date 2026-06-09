<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client-side Proposals view. A "proposal" here is a supplier's response
 * to a client's event (modelled today on the Booking record). This page
 * gives the client one place to review, compare, and act on every
 * proposal received across their events.
 *
 * NOTE: this reuses the Booking model as the proposal source. When a
 * dedicated Proposal model ships, swap the query source here — the view
 * contract (status, amount, health score) stays the same.
 *
 * Route: GET /client/proposals
 */
class ClientProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $base = Booking::where('client_id', $user->id);

        // Map booking statuses → proposal-pipeline buckets used by the tabs.
        $stats = [
            'submitted'   => (clone $base)->count(),
            'pending'     => (clone $base)->where('status', 'requested')->count(),
            'accepted'    => (clone $base)->where('status', 'confirmed')->count(),
            'in_progress' => (clone $base)->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))->count(),
            'completed'   => (clone $base)->where('status', 'completed')->count(),
            'declined'    => (clone $base)->where('status', 'cancelled')->count(),
            'drafts'      => 0,
        ];

        $tab = $request->string('tab')->toString() ?: 'all';
        $query = (clone $base)
            ->with(['event:id,title,starts_at,location', 'event.categories:id,name', 'supplier:id,name'])
            ->latest();

        match ($tab) {
            'pending'     => $query->where('status', 'requested'),
            'accepted'    => $query->where('status', 'confirmed'),
            'completed'   => $query->where('status', 'completed'),
            'declined'    => $query->where('status', 'cancelled'),
            'in_progress' => $query->where('status', 'confirmed'),
            default       => null,
        };

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->whereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$s}%"))
                ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$s}%")));
        }

        $proposals = $query->paginate(10)->withQueryString();

        // Revenue pipeline — pending vs accepted value.
        $priceCol = \Illuminate\Support\Facades\Schema::hasColumn('bookings', 'total_amount')
            ? 'total_amount'
            : (\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'agreed_price') ? 'agreed_price' : null);
        $pendingValue  = $priceCol ? (float) (clone $base)->where('status', 'requested')->sum($priceCol) : 0;
        $acceptedValue = $priceCol ? (float) (clone $base)->where('status', 'confirmed')->sum($priceCol) : 0;

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
