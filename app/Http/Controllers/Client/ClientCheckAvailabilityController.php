<?php

namespace App\Http\Controllers\Client;

use App\Domain\Calendar\Availability;
use App\Domain\CheckAvailability;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ClientCalendar;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Check Availability, the screen Sir Peter calls "a different task".
 *
 * His words: "Before I hire somebody, are they available for my event? It
 * needs event selection, multiple dates, search/filtering, availability
 * results, profiles, and messaging. That's substantial enough to deserve its
 * own URL/page."
 *
 * The honest answer is only half available. Who is BUSY we know, from their
 * bookings. Who is FREE only they can say, and their side has nowhere to say
 * it. So the page defaults to telling the truth, which is a column of "Not
 * Answered", and offers a sample mode for looking at the finished screen.
 * Sample mode is loud about being sample.
 */
class ClientCheckAvailabilityController extends Controller
{
    /**
     * The three tabs, in Sir Peter's order.
     *
     * 12 October: "the three availabilities (My Availability, Professionals
     * and Influencers) can be setup as tabs instead of subpages ... But I want
     * the clients calendar, Availability, and Hired as separate webpages."
     *
     * So My Availability moved off the calendar and became the first tab here.
     * The calendar is now only a calendar.
     */
    public const TABS = [
        'mine'          => ['My Availability', 'Say which days suit you'],
        'professionals' => ['Professionals',   'Before you hire'],
        'influencers'   => ['Influencers',     'Before you hire'],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        /*
         * ?who= was how the two halves were told apart while they were
         * subpages, and links carrying it are still in the wild, so it still
         * answers. ?tab= is what the page emits now.
         */
        $tab = $request->query('tab');

        if (! array_key_exists((string) $tab, self::TABS)) {
            $tab = $request->query('who') === 'influencers' ? 'influencers' : null;
        }

        $tab ??= $request->has('who') ? 'professionals' : 'mine';

        if ($tab === 'mine') {
            return $this->mine($request);
        }

        $events = Event::where('client_id', $user->id)
            ->whereNotNull('starts_at')
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'starts_at', 'ends_at', 'city', 'state', 'backup_dates']);

        $event = $events->firstWhere('id', (int) $request->query('event'))
            ?? $events->first();

        $kind = $tab === 'influencers' ? 'influencer' : 'professional';

        // Off unless asked for. A screen that invents answers should never be
        // what somebody lands on by accident.
        $sample = $request->boolean('sample');

        $dates = CheckAvailability::dates($event);

        $q         = trim((string) $request->query('q', ''));
        $serviceId = (int) $request->query('service') ?: null;
        $place     = trim((string) $request->query('place', ''));
        $sort      = in_array($request->query('sort'), ['match', 'name', 'rating'], true)
            ? $request->query('sort')
            : 'match';

        return view('client.check-availability.index', [
            'tab'      => $tab,
            'tabs'     => self::TABS,
            'events'   => $events,
            'event'    => $event,
            'dates'    => $dates,
            'kind'     => $kind,
            'sample'   => $sample,
            'q'        => $q,
            'serviceId' => $serviceId,
            'place'    => $place,
            'sort'     => $sort,
            'services' => CheckAvailability::serviceOptions($kind),
            'places'   => CheckAvailability::placeOptions($kind),
            // Anything narrowing the list, so "Clear all" can be offered only
            // when there is something to clear.
            'filtered' => $q !== '' || $serviceId !== null || $place !== '' || $sort !== 'match',
            'rows'     => $dates->isEmpty()
                ? collect()
                : CheckAvailability::suppliers($dates, $kind, $sample, $q, $serviceId, $place, $sort),
        ]);
    }

    /**
     * My Availability, which used to be the second tab on the calendar page.
     *
     * The month is built here rather than read from the calendar page,
     * because this tab is always a month: the grid loops first to last and
     * greys the days either side. Arriving from a day or a week view used to
     * draw a single strip of seven days with "Previous month" on an arrow
     * that moved by one week, which is not a screen for saying which days of
     * the year suit you.
     */
    private function mine(Request $request): View
    {
        $user = $request->user();

        $anchor = $request->query('cal') ?: $request->query('anchor');

        $calendar = ClientCalendar::build($user, 'month', $anchor);

        $availability = Availability::between($user, $calendar['first'], $calendar['last']);

        return view('client.check-availability.index', [
            'tab'          => 'mine',
            'tabs'         => self::TABS,
            'calendar'     => $calendar,
            'availability' => $availability,
            'tally'        => Availability::tally($availability),
        ]);
    }
}
