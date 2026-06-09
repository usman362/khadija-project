<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gig Operations Hub — the professional's centralized gig control centre
 * (explainer + a live snapshot of their gigs). "View All Gigs" links to the
 * full My Gigs page.
 *
 * REAL data: gigs = the pro's bookings (with event/client/service); crew
 * counters come from the Shift subsystem (shifts linked to the gig's event);
 * message counts from the booking conversation. Stats are derived booking
 * status counts. The "click a gig" deep-dive cards are explainer/UI.
 *
 * Route: GET /professional/gig-hub
 */
class ProfessionalGigHubController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $now  = now();
        $base = fn () => Booking::where('supplier_id', $user->id);

        $counts = $base()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $cReq  = (int) ($counts['requested'] ?? 0);
        $cConf = (int) ($counts['confirmed'] ?? 0);
        $cComp = (int) ($counts['completed'] ?? 0);

        $inProgress = $base()->where('status', 'confirmed')
            ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', $now)->where('ends_at', '>=', $now))
            ->count();

        $stats = [
            'active'      => $cConf + $cReq,
            'in_progress' => $inProgress,
            'completed'   => $cComp,
        ];

        $gigs = $base()->whereIn('status', ['confirmed', 'requested', 'completed'])
            ->with(['event:id,title,starts_at,ends_at,location,budget', 'event.categories:id,name', 'client:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Crew counters from the staffing subsystem (shifts per event).
        $eventIds = $gigs->pluck('event_id')->filter()->unique()->all();
        $crew = collect();
        if (! empty($eventIds)) {
            $crew = Shift::whereIn('event_id', $eventIds)
                ->selectRaw('event_id, count(*) as total, sum(case when status = "open" then 0 else 1 end) as filled')
                ->groupBy('event_id')
                ->get()
                ->keyBy('event_id');
        }

        // Unread / message counts per gig conversation.
        $msg = collect();
        $bookingIds = $gigs->pluck('id')->all();
        if (! empty($bookingIds)) {
            $msg = Conversation::whereIn('booking_id', $bookingIds)
                ->withCount('messages')
                ->get()
                ->keyBy('booking_id');
        }

        return view('professional.gig-hub.index', compact('stats', 'gigs', 'crew', 'msg'));
    }
}
