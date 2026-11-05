<?php

namespace App\Http\Controllers\Professional;

use App\Domain\Calendar\Availability;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Professional → Availability & Resources.
 *
 * Khadijah's 28 October sidebar has this between Calendar and Messages, and
 * it was the one item in that menu with no page behind it.
 *
 * It answers two questions a professional is asked before anyone books them:
 * which days they can work, and what they can bring. The days come from
 * App\Domain\Calendar\Availability, the same store the calendar writes, so
 * the two screens can never disagree. The resources are the crew on the books
 * and the services with a price on them, both of which already exist.
 *
 * Nothing here is new storage. A day with no answer stays unknown, which is
 * the whole point of that domain: an unanswered day is not a free day.
 */
class ProfessionalAvailabilityController extends Controller
{
    /** How many months ahead the grid offers, counting the current one. */
    private const MONTHS = 3;

    public function index(Request $request): View
    {
        $me = $request->user();

        $today = Carbon::today(config('app.display_timezone', config('app.timezone')));
        $from  = $today->copy()->startOfMonth();
        $to    = $today->copy()->addMonths(self::MONTHS - 1)->endOfMonth();

        $marks = Availability::between($me, $from, $to);

        // Booked days are drawn as booked and are not answerable here: a day
        // that already has a gig on it is not a question.
        $booked = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress', 'pending'])
            ->with('event:id,title,event_date')
            ->get()
            ->filter(fn (Booking $b) => $b->event?->event_date)
            ->keyBy(fn (Booking $b) => $b->event->event_date->format('Y-m-d'));

        $months = [];

        for ($i = 0; $i < self::MONTHS; $i++) {
            $cursor = $from->copy()->addMonths($i);
            $months[] = [
                'label' => $cursor->format('F Y'),
                'key'   => $cursor->format('Y-m'),
                'lead'  => (int) $cursor->copy()->startOfMonth()->dayOfWeek,
                'days'  => $this->daysOf($cursor, $today, $marks, $booked),
            ];
        }

        $ahead = collect($months)->flatMap(fn ($m) => $m['days'])
            ->filter(fn ($d) => $d['date'] >= $today->format('Y-m-d'));

        $crew = StaffMember::where('supplier_id', $me->id)->orderBy('name')->get();

        $services = $me->serviceCategories()
            ->where('categories.kind', Category::SERVICE)
            ->orderBy('categories.name')
            ->get();

        return view('professional.availability.index', [
            'months'   => $months,
            'today'    => $today,
            'crew'     => $crew,
            'services' => $services,
            'counts'   => [
                'available'   => $ahead->where('state', Availability::AVAILABLE)->count(),
                'unavailable' => $ahead->where('state', Availability::UNAVAILABLE)->count(),
                'unanswered'  => $ahead->whereNull('state')->where('booked', false)->count(),
                'booked'      => $ahead->where('booked', true)->count(),
                'crew'        => $crew->count(),
                'priced'      => $services->filter(fn ($s) => $s->pivot->price !== null)->count(),
                'services'    => $services->count(),
            ],
            'states'   => Availability::STATES,
        ]);
    }

    /** One day's answer, set or taken back. */
    public function markDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day'   => ['required', 'date_format:Y-m-d'],
            'state' => ['required', 'in:available,unavailable'],
        ]);

        Availability::mark($request->user(), $data['day'], $data['state']);

        return redirect()->route('professional.availability.index')
            ->with('status', Carbon::createFromFormat('Y-m-d', $data['day'])->format('j M') . ' updated.');
    }

    /** A run of days in one go. "not_set" is the absence of an answer, so it clears. */
    public function markRange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from'  => ['required', 'date_format:Y-m-d'],
            'to'    => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'state' => ['required', 'in:available,unavailable,not_set'],
        ]);

        $state   = $data['state'] === 'not_set' ? null : $data['state'];
        $written = Availability::markRange($request->user(), $data['from'], $data['to'], $state);

        $word = $state ? strtolower(Availability::STATES[$state][0]) : 'back to not set';

        return redirect()->route('professional.availability.index')
            ->with('status', $written === 0 && $state === null
                ? 'Those days had no answer on them already, so nothing changed.'
                : $written . ' ' . \Illuminate\Support\Str::plural('day', $written) . ' set ' . $word . '.');
    }

    /**
     * Every day of one month, carrying whatever is known about it.
     *
     * @param  \Illuminate\Support\Collection  $marks   keyed Y-m-d
     * @param  \Illuminate\Support\Collection  $booked  keyed Y-m-d
     */
    private function daysOf(Carbon $month, Carbon $today, $marks, $booked): array
    {
        $days   = [];
        $cursor = $month->copy()->startOfMonth();
        $end    = $month->copy()->endOfMonth();

        while ($cursor <= $end) {
            $key  = $cursor->format('Y-m-d');
            $gig  = $booked->get($key);

            $days[] = [
                'date'    => $key,
                'num'     => (int) $cursor->format('j'),
                'past'    => $cursor->lt($today),
                'today'   => $cursor->isSameDay($today),
                'state'   => $marks->get($key)?->state,
                'note'    => $marks->get($key)?->note,
                'booked'  => $gig !== null,
                'gig'     => $gig?->event?->title,
            ];

            $cursor->addDay();
        }

        return $days;
    }
}
