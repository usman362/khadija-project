<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional "Multi-Service Requests" — browse & bid on event postings
 * that need MORE THAN ONE service (an Event with 2+ categories). This is the
 * professional/bidding side of the same feature the client uses to POST a
 * multi-service event brief.
 *
 * REAL data: multi-service events = published, unassigned Events with >= 2
 * categories. Each category is a "service" the pro can bid on.
 * Derived/illustrative: per-service budget split and per-service bid counts
 * (no service-line / per-service-bid table yet — the event has one budget).
 *
 * Route: GET /professional/multi-service
 */
class ProfessionalMultiServiceController extends Controller
{
    public function index(Request $request): View
    {
        // Open multi-service postings: published, unassigned, 2+ services.
        $base = fn () => Event::where('is_published', true)
            ->whereNull('supplier_id')
            ->whereIn('status', ['pending', 'published'])
            ->has('categories', '>=', 2);

        // Featured example (newest open multi-service event).
        $featured = $base()
            ->with(['categories:id,name', 'client:id,name'])
            ->withCount('bookings')
            ->latest()
            ->first();

        // Recent multi-service requests (for the table).
        $recent = $base()
            ->with(['categories:id,name', 'client:id,name'])
            ->withCount(['categories', 'bookings'])
            ->latest()
            ->take(6)
            ->get();

        $liveCount = $base()->count();

        return view('professional.multi-service.index', compact('featured', 'recent', 'liveCount'));
    }
}
