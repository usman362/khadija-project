<?php

namespace App\Http\Controllers\Client;

use App\Domain\CheckAvailability;
use App\Http\Controllers\Controller;
use App\Models\Event;
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
    public function index(Request $request): View
    {
        $user = $request->user();

        $events = Event::where('client_id', $user->id)
            ->whereNotNull('starts_at')
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'starts_at', 'ends_at', 'city', 'state', 'backup_dates']);

        $event = $events->firstWhere('id', (int) $request->query('event'))
            ?? $events->first();

        $kind = $request->query('who') === 'influencers' ? 'influencer' : 'professional';

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
}
