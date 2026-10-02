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

        // Which of Sir Peter's four tabs is open. Only two of them answer.
        $tab = in_array($request->query('tab'), ['availability'], true) ? $request->query('tab') : 'calendar';

        $availability = Availability::between($user, $calendar['first'], $calendar['last']);
        $tally        = Availability::tally($availability);

        return view('client.calendar.index', compact(
            'calendar', 'grid', 'upcoming', 'counts', 'tab', 'availability', 'tally',
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

    /** Block a stretch of dates in one go. */
    public function markRange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from'  => ['required', 'date_format:Y-m-d'],
            'to'    => ['required', 'date_format:Y-m-d'],
            'state' => ['required', 'in:available,unavailable'],
            'back'  => ['nullable', 'string', 'max:300'],
        ]);

        $written = Availability::markRange($request->user(), $data['from'], $data['to'], $data['state']);

        [$word] = Availability::STATES[$data['state']];

        return $this->backToCalendar($data['back'] ?? null)
            ->with('status', $written . ' ' . \Illuminate\Support\Str::plural('day', $written) . ' marked ' . strtolower($word) . '.');
    }

    /**
     * Back where they were, including the month and the tab, and never off
     * this site: the address comes from the page and is therefore theirs to
     * tamper with.
     */
    private function backToCalendar(?string $back): RedirectResponse
    {
        $fallback = route('client.calendar.index', ['tab' => 'availability']);

        if (! $back) {
            return redirect()->to($fallback);
        }

        $path = parse_url($back, PHP_URL_PATH) ?: '';
        $qs   = parse_url($back, PHP_URL_QUERY);

        if ($path !== '/client/calendar') {
            return redirect()->to($fallback);
        }

        return redirect()->to('/client/calendar' . ($qs ? '?' . $qs : ''));
    }
}
