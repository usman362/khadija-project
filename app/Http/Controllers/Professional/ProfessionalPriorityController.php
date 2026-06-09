<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Priority Actions — the pro's "smart assistant" surfacing the urgent items
 * they must act on right now. No dedicated table: this AGGREGATES real urgent
 * items from existing data:
 *   - contracts awaiting the pro's signature (Agreements not yet supplier-accepted)
 *   - new proposals / bids to respond to (requested Bookings)
 *   - staffing shortages (open Shifts)
 *   - escrow released (completed Bookings value)
 *
 * The explainer content + the "What you can do" buttons are educational/UI.
 *
 * Route: GET /professional/priority-actions
 */
class ProfessionalPriorityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $contractsAwaiting = Agreement::whereHas('booking', fn ($q) => $q->where('supplier_id', $user->id))
            ->whereIn('status', ['pending_review', 'client_accepted'])
            ->count();

        $newProposals = Booking::where('supplier_id', $user->id)->where('status', 'requested')->count();

        $openShifts = Shift::where('supplier_id', $user->id)->where('status', 'open')->count();

        $escrowReleased = (float) Booking::where('supplier_id', $user->id)
            ->where('status', 'completed')
            ->sum('price');

        $priorityCount = $contractsAwaiting + $newProposals + $openShifts;

        $cards = [
            'contracts' => $contractsAwaiting,
            'staffing'  => $openShifts,
            'bids'      => $newProposals,
            'escrow'    => $escrowReleased,
        ];

        // Spotlight: the newest requested booking — drives the "Live Example".
        $spotlight = Booking::where('supplier_id', $user->id)
            ->where('status', 'requested')
            ->with(['event:id,title,starts_at,ends_at,location,budget', 'client:id,name'])
            ->latest()
            ->first();

        return view('professional.priority.index', compact('priorityCount', 'cards', 'spotlight'));
    }
}
