<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Shift;
use App\Support\Earnings;
use App\Support\GigStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gig Operations Hub — the one workspace a professional manages a gig from,
 * start to finish.
 *
 * It used to be three pages: Contracts, My Gigs and this one, all showing the
 * same jobs in different layouts. Sir Peter approved merging them on
 * 2026-08-05 (R42, scope amended), with the condition that this is "a UI and
 * workflow consolidation only — it does not change the ownership of data or
 * calculations."
 *
 * So the three views moved in as tabs and nothing about how the numbers are
 * worked out changed, with one exception that follows the same condition:
 * money now comes from App\Support\Earnings, the Payments & Payouts source,
 * rather than this page adding up booking prices on its own.
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
    /** The three former pages, now tabs. */
    private const TABS = ['overview', 'gigs', 'contracts'];

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true)
            ? $request->query('tab')
            : 'overview';

        $user = $request->user();
        $now  = now();
        $base = fn () => Booking::where('supplier_id', $user->id);

        // Counted in App\Support\GigStats, so "active" and "in progress"
        // mean the same thing on every tab of this page. They did not: this
        // page called a gig in progress when its event was running, and the
        // Contracts tab called a gig active when it was confirmed or merely
        // requested. Both words now have one definition.
        $counted = GigStats::forProfessional($user);

        $stats = [
            'active'      => $counted['active'],
            'in_progress' => $counted['inProgress'],
            'completed'   => $counted['completed'],
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
                // Single quotes: "open" is a string literal in MySQL but an
                // identifier in standard SQL, so the double-quoted form only
                // worked by accident.
                ->selectRaw("event_id, count(*) as total, sum(case when status = 'open' then 0 else 1 end) as filled")
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

        // Money comes from the Payments & Payouts source, not from this page
        // adding up booking prices — Sir Peter's condition on the merge, and
        // the reason Earnings and Transactions used to disagree.
        $money = Earnings::forProfessional($user);

        /*
         * Checklist row 130 — one "today" for the whole page.
         *
         * Every countdown on this screen used to take its own now(), and the
         * sample cards carried dates typed in by hand that bore no relation to
         * the clock at all. Two figures a professional reads side by side must
         * be measured from the same instant, or the smaller disagreements look
         * like bugs and the larger ones are.
         */
        $today = $now;

        return view('professional.gig-hub.index', array_merge(
            compact('tab', 'stats', 'gigs', 'crew', 'msg', 'money', 'today'),
            $tab === 'gigs'      ? $this->gigsTab($request) : [],
            $tab === 'contracts' ? $this->contractsTab($request, $money) : [],
        ));
    }

    /** What the old My Gigs page needed. */
    private function gigsTab(Request $request): array
    {
        return app(ProfessionalGigController::class)
            ->indexData($request);
    }

    /** What the old Contracts page needed. */
    private function contractsTab(Request $request, array $money): array
    {
        return app(ProfessionalContractController::class)
            ->indexData($request, $money);
    }
}
