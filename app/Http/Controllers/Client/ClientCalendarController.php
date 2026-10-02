<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ClientCalendar;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sir Peter, 24 September: "add a left column webtitle as Calendar &
 * Availability for a full webpage instead of search around my events webpage
 * to find it, this way its a simple more direct way."
 *
 * The client's calendar already existed, as a tab inside My Events and a card
 * on the dashboard. What it did not have was an address of its own, so there
 * was no way to arrive at it except through another page.
 *
 * It is the same calendar: App\Support\ClientCalendar lays it out, and the
 * colour an entry wears means the same thing here as on the other two. Three
 * screens drawing three calendars is how they come to disagree.
 *
 * The three tabs this screen does not yet answer — the client's own
 * availability, and the availability of the professionals and influencers
 * they have hired — say what they are waiting for rather than showing a month
 * of invented free days. Nobody records availability anywhere on this
 * platform today: a professional's calendar is built from their bookings and
 * shifts, which say when they are busy, never when they are free.
 */
class ClientCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $calendar = ClientCalendar::build(
            $user,
            $request->query('calview'),
            $request->query('anchor'),
        );

        $grid = in_array($calendar['view'], ['day', 'week'], true)
            ? ClientCalendar::timeGrid($calendar)
            : null;

        // The rail: what is coming, soonest first. Drafts are left out
        // because a draft has not been sent and is not an appointment.
        $upcoming = Event::where('client_id', $user->id)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('status', '!=', 'draft')
            ->orderBy('starts_at')
            ->take(6)
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status', 'is_published', 'location', 'city', 'state']);

        /*
         * The three figures under the calendar, each counted from the same
         * events the grid is drawn from, over the range on screen. A figure
         * with its own private source is how a calendar comes to disagree
         * with the page it sits on.
         */
        $inRange = Event::where('client_id', $user->id)
            ->whereBetween('starts_at', [
                $calendar['first']->copy()->startOfDay(),
                $calendar['last']->copy()->endOfDay(),
            ])
            ->get(['id', 'status', 'is_published', 'starts_at']);

        $counts = [
            'in_range'  => $inRange->count(),
            'confirmed' => $inRange->filter(fn ($e) => $e->stage() === 'confirmed')->count(),
            'needs_you' => $inRange->filter(fn ($e) => $e->stage() === 'open')->count(),
        ];

        return view('client.calendar.index', compact('calendar', 'grid', 'upcoming', 'counts'));
    }
}
