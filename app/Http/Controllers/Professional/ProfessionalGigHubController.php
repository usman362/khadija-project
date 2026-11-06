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

        /*
         * Khadijah's three cards at the top: what is coming, what paperwork
         * is outstanding, and who is waiting to hear from you. Each one is a
         * count of real rows, and each carries the one line underneath that
         * says which of them matters most.
         */
        $upcoming = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', $now))
            ->with('event:id,title,starts_at')
            ->get()
            ->sortBy(fn (Booking $b) => $b->event?->starts_at);

        $nextEvent = $upcoming->first()?->event?->starts_at;

        // Paperwork that is not finished: anything neither side has rejected
        // and this professional has not accepted.
        $contracts = \App\Models\Agreement::whereHas('booking',
                fn ($q) => $q->where('supplier_id', $user->id))
            ->whereNull('rejected_at')
            ->get();

        $needsMySignature = $contracts
            ->filter(fn ($a) => $a->supplier_accepted_at === null)
            ->count();

        /*
         * Threads waiting on a reply.
         *
         * Nothing on a message records whether it was read, so "unread" is
         * not a question this platform can answer. What it can answer is
         * whether the last word in a thread was the client's, which is the
         * thing that actually needs the professional.
         */
        $threads = Conversation::whereHas('booking',
                fn ($q) => $q->where('supplier_id', $user->id))
            ->with(['messages' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->get();

        $waiting = $threads->filter(function ($c) use ($user) {
            $last = $c->messages->first();

            return $last && $last->sender_id !== $user->id;
        });

        $stats = [
            'active'      => $counted['active'],
            'in_progress' => $counted['inProgress'],
            'completed'   => $counted['completed'],
            'upcoming'    => $upcoming->count(),
            'next_event'  => $nextEvent,
            'contracts'   => $contracts->count(),
            'to_sign'     => $needsMySignature,
            'waiting'        => $waiting->count(),
            'waiting_threads' => $threads->count(),
        ];

        // in_progress belongs here: a gig being worked on right now was the
        // one state missing from "Recent Gigs & Contracts".
        $gigs = $base()->whereIn('status', ['confirmed', 'in_progress', 'requested', 'completed'])
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

        /*
         * Khadijah's 28 October screen carries two more columns beside the
         * gigs: what is due, and what has just happened. Both are read off
         * records that already exist rather than a new activity log.
         */
        $deadlines = \App\Support\PriorityActions::forProfessional($user)
            ->filter(fn ($i) => $i['due'] !== null)
            ->take(5)
            ->values();

        $activity = $this->activity($user);

        return view('professional.gig-hub.index', array_merge(
            compact('tab', 'stats', 'gigs', 'crew', 'msg', 'money', 'today', 'deadlines', 'activity'),
            $tab === 'gigs'      ? $this->gigsTab($request) : [],
            $tab === 'contracts' ? $this->contractsTab($request, $money) : [],
        ));
    }

    /**
     * What has just happened on this professional's own jobs.
     *
     * There is no activity table, and inventing one would mean a second
     * record of things the bookings and agreements already know. This reads
     * the most recent change on each and puts them in one order.
     *
     * @return array<int, array{at:\Illuminate\Support\Carbon, what:string, subject:?string}>
     */
    private function activity($user): array
    {
        $rows = collect();

        Booking::where('supplier_id', $user->id)
            ->with('event:id,title')
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->each(function (Booking $b) use ($rows) {
                $rows->push([
                    'at'      => $b->updated_at,
                    'what'    => match ($b->status) {
                        'requested'   => 'A client asked you for this',
                        'confirmed'   => 'Booking confirmed',
                        'in_progress' => 'Work started',
                        'completed'   => 'Marked completed',
                        'cancelled'   => 'Booking cancelled',
                        default       => 'Booking updated',
                    },
                    'subject' => $b->event?->title,
                ]);
            });

        \App\Models\Agreement::whereHas('booking', fn ($q) => $q->where('supplier_id', $user->id))
            ->with('booking.event:id,title')
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->each(function ($a) use ($rows) {
                $rows->push([
                    'at'      => $a->updated_at,
                    'what'    => $a->supplier_accepted_at
                        ? 'You signed the agreement'
                        : ($a->client_accepted_at ? 'The client signed, waiting on you' : 'Agreement updated'),
                    'subject' => $a->booking?->event?->title,
                ]);
            });

        return $rows
            ->filter(fn ($r) => $r['at'] !== null)
            ->sortByDesc(fn ($r) => $r['at']->timestamp)
            ->take(6)
            ->values()
            ->all();
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
