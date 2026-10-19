<?php

namespace App\Http\Controllers\Client;

use App\Domain\Calendar\Availability;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ClientCalendar;
use Illuminate\Http\RedirectResponse;
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
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        /*
         * "cal" is what the shared calendar's own controls emit, so that is
         * what is read. It took the tests moving here to notice: this page
         * was reading "anchor", which nothing sends, so its next and previous
         * arrows went nowhere and every month was this one.
         */
        $anchor = $request->query('cal')
            ?: $request->query('anchor')
            // ?month=&year= links were sent out before the calendar had a page
            // of its own. They still land on the month they meant.
            ?: ($request->filled('month') && $request->filled('year')
                ? sprintf('%04d-%02d-01', $request->integer('year'), $request->integer('month'))
                : null);

        /*
         * Sir Peter, 12 October: "I want the clients calendar, Availability,
         * and Hired as separate webpages", with the three availabilities as
         * tabs inside Availability. So My Availability left this page, and
         * this page is a calendar and nothing else. An old ?tab=availability
         * link is sent on to where that tab lives now.
         */
        if ($request->query('tab') === 'availability') {
            return redirect()->route('client.check-availability.index', array_filter([
                'tab' => 'mine',
                'cal' => $request->query('cal') ?: $request->query('anchor'),
            ]));
        }

        $view = $request->query('calview');

        $calendar = ClientCalendar::build($user, $view, $anchor);

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

        return view('client.calendar.index', compact(
            'calendar', 'grid', 'upcoming', 'counts',
        ));
    }

    /**
     * Say something about one day, or take it back.
     *
     * The control that sets an answer is the control that removes it: marking
     * a day the state it already holds clears it. One button, no separate
     * undo to find.
     */
    public function markDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day'   => ['required', 'date_format:Y-m-d'],
            'state' => ['required', 'in:available,unavailable'],
            'back'  => ['nullable', 'string', 'max:300'],
        ]);

        Availability::mark($request->user(), $data['day'], $data['state']);

        return $this->backToCalendar($data['back'] ?? null);
    }

    /** Set availability for a run of dates in one go, including clearing it. */
    public function markRange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from'  => ['required', 'date_format:Y-m-d'],
            'to'    => ['required', 'date_format:Y-m-d'],
            // Three choices, per Sir Peter on 20 October. "not_set" is the
            // absence of an answer rather than an answer, so it clears.
            'state' => ['required', 'in:available,unavailable,not_set'],
            'back'  => ['nullable', 'string', 'max:300'],
        ]);

        $state = $data['state'] === 'not_set' ? null : $data['state'];

        $written = Availability::markRange($request->user(), $data['from'], $data['to'], $state);

        $word = $state ? strtolower(Availability::STATES[$state][0]) : 'back to not set';

        return $this->backToCalendar($data['back'] ?? null)
            ->with('status', $written === 0 && $state === null
                ? 'Those days had no answer on them already, so nothing changed.'
                : $written . ' ' . \Illuminate\Support\Str::plural('day', $written) . ' set ' . $word . '.');
    }

    /**
     * Back where they were, including the month and the tab, and never off
     * this site: the address comes from the page and is therefore theirs to
     * tamper with.
     */
    private function backToCalendar(?string $back): RedirectResponse
    {
        // My Availability moved to the Availability page on 12 October, so
        // that is where somebody who just marked a day belongs.
        $fallback = route('client.check-availability.index', ['tab' => 'mine']);

        if (! $back) {
            return redirect()->to($fallback);
        }

        $path = parse_url($back, PHP_URL_PATH) ?: '';
        $qs   = parse_url($back, PHP_URL_QUERY);

        /*
         * Two paths, because My Availability moved to its own page on 12
         * October and this list still named only the calendar. A client who
         * marked a day from the availability list was sent back to the grid
         * instead of the list they were reading, which is half of what Sir
         * Peter reported on 20 October about not being able to stay in the
         * list view.
         *
         * Still an allow-list rather than a redirect to whatever arrived: the
         * address comes from the page and is therefore theirs to tamper with.
         */
        $allowed = ['/client/calendar', '/client/availability'];

        if (! in_array($path, $allowed, true)) {
            return redirect()->to($fallback);
        }

        return redirect()->to($path . ($qs ? '?' . $qs : ''));
    }
}
