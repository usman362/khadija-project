<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional "Contracts" hub — the professional's contracts, proposals,
 * earnings, gig opportunities and bids pipeline in one place.
 *
 * Wires to REAL data:
 *   - Bookings (supplier_id = current pro) are the professional's contracts /
 *     proposals. status: requested → confirmed → completed | cancelled.
 *   - Events (published, unassigned) are open gig opportunities.
 *   - Reviews drive the rating.
 *
 * Derived (no dedicated table yet): earnings split (paid/escrow/pending) is
 * derived from booking prices per status; response-time + the AI Smart Bid
 * Assistant are illustrative (no time-tracking / AI backend yet).
 *
 * Route: GET /professional/contracts
 */
class ProfessionalContractController extends Controller
{
    public function index(Request $request): View
    {
        $user       = $request->user();
        $now        = now();
        $monthStart = $now->copy()->startOfMonth();

        // Fresh base query for this pro's bookings each call.
        $base = fn () => Booking::where('supplier_id', $user->id);

        // ── Status counts ─────────────────────────────────────────
        $counts     = $base()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $cRequested = (int) ($counts['requested'] ?? 0);
        $cConfirmed = (int) ($counts['confirmed'] ?? 0);
        $cCompleted = (int) ($counts['completed'] ?? 0);
        $cCancelled = (int) ($counts['cancelled'] ?? 0);

        // ── Earnings (derived from booking prices) ────────────────
        $sumPaid    = (float) $base()->where('status', 'completed')->sum('price'); // realized
        $sumEscrow  = (float) $base()->where('status', 'confirmed')->sum('price');  // committed / in escrow
        $sumPending = (float) $base()->where('status', 'requested')->sum('price');  // awaiting acceptance
        $totalRevenue = $sumPaid + $sumEscrow + $sumPending;
        $earningsMtd  = (float) $base()->where('status', 'completed')
            ->where('updated_at', '>=', $monthStart)->sum('price');

        // Win rate = won (confirmed+completed) / decided (won + cancelled).
        $won     = $cConfirmed + $cCompleted;
        $decided = $won + $cCancelled;
        $winRate = $decided > 0 ? (int) round($won / $decided * 100) : 0;

        $reviewStats = $user->reviewStats();

        $stats = [
            'earnings_mtd'     => $earningsMtd,
            'active_proposals' => $cRequested,
            'leads'            => Event::where('is_published', true)->whereNull('supplier_id')
                                    ->where('created_at', '>=', $monthStart)->count(),
            'contracts_active' => $cConfirmed,
            'win_rate'         => $winRate,
            'avg_rating'       => round((float) ($reviewStats['average'] ?? 0), 1),
        ];

        // ── Active contracts table ────────────────────────────────
        $contracts = $base()->whereIn('status', ['confirmed', 'requested'])
            ->with(['event:id,title,starts_at,ends_at,location,budget', 'event.categories:id,name', 'client:id,name'])
            ->latest()
            ->take(8)
            ->get();

        $tabCounts = [
            'active'    => $cConfirmed + $cRequested,
            'upcoming'  => $base()->where('status', 'confirmed')
                                ->whereHas('event', fn ($q) => $q->where('starts_at', '>', $now))->count(),
            'completed' => $cCompleted,
            'cancelled' => $cCancelled,
        ];

        // ── Bids pipeline (real status counts where modelled) ─────
        $pipeline = [
            'submitted'       => $cRequested,
            'hired'           => $cConfirmed,
            'won_month'       => $base()->where('status', 'completed')
                                    ->where('updated_at', '>=', $monthStart)->count(),
            'submitted_value' => $sumPending,
            'hired_value'     => $sumEscrow,
            'won_value'       => $earningsMtd,
        ];

        // ── Live gig opportunities (open published events) ────────
        $opportunities = Event::where('is_published', true)
            ->whereNull('supplier_id')
            ->whereIn('status', ['pending', 'published'])
            ->with(['categories:id,name'])
            ->latest()
            ->take(4)
            ->get();

        // ── Upcoming gig schedule (confirmed, future) ─────────────
        $upcoming = $base()->where('status', 'confirmed')
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', $now))
            ->with(['event:id,title,starts_at,location', 'client:id,name'])
            ->get()
            ->sortBy(fn ($b) => optional($b->event)->starts_at)
            ->take(4)
            ->values();

        return view('professional.contracts.index', compact(
            'stats', 'contracts', 'tabCounts', 'pipeline',
            'sumPaid', 'sumEscrow', 'sumPending', 'totalRevenue',
            'opportunities', 'upcoming'
        ));
    }
}
