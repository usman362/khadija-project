<?php

namespace App\Domain\Requests;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * When each service on a request runs (Sir Peter, 27 September).
 *
 * "We need to have the timeline added into this as a sub-step per service, so
 * that the bidders can see when each service is to start/end or a rough idea
 * ... just bc an event starts at a certain time and date, the timeline might
 * needed as a option so that services dont over lap or maybe they will need
 * to."
 *
 * A request carried one start and one end for the whole event, so a DJ and a
 * photographer on the same booking were handed the same hours whatever the
 * client wanted. Each service can now say its own, and what it does not say
 * it takes from the event.
 *
 * Two things this deliberately does NOT do:
 *
 *  - It does not refuse an overlap. Two services running at once is normal —
 *    the photographer shoots while the DJ plays — and his own note says as
 *    much. Overlaps are named on screen so the client can see them, never
 *    blocked.
 *  - It does not invent a time. A service with nothing entered inherits the
 *    event's, and is shown as doing so, rather than being given an hour
 *    nobody chose.
 */
class ServiceTimeline
{
    /**
     * The times as they were posted: [service id => ['start' => H:i, 'end' => H:i]].
     *
     * @return array<int, array{starts_at: ?string, ends_at: ?string}>
     */
    public static function fromInput(array $times, array $serviceIds, ?CarbonInterface $eventDay): array
    {
        $day = $eventDay ? Carbon::parse($eventDay)->startOfDay() : null;
        $out = [];

        foreach ($serviceIds as $id) {
            $id = (int) $id;
            $start = trim((string) ($times[$id]['start'] ?? ''));
            $end   = trim((string) ($times[$id]['end'] ?? ''));

            $out[$id] = [
                'starts_at' => $day && $start !== '' ? $day->copy()->setTimeFromTimeString($start) : null,
                'ends_at'   => $day && $end !== '' ? $day->copy()->setTimeFromTimeString($end) : null,
            ];
        }

        return $out;
    }

    /**
     * Every service on a request, with the times it runs and where they came
     * from, in the order the services are attached.
     *
     * @return array<int, array{id: int, name: string, starts_at: ?Carbon, ends_at: ?Carbon, own: bool, minutes: ?int}>
     */
    public static function of(Event $event): array
    {
        return $event->categories->map(function ($c) use ($event) {
            $start = $c->pivot->starts_at ? Carbon::parse($c->pivot->starts_at) : null;
            $end   = $c->pivot->ends_at ? Carbon::parse($c->pivot->ends_at) : null;
            $own   = $start !== null || $end !== null;

            $start ??= $event->starts_at;
            $end   ??= $event->ends_at;

            return [
                'id'        => $c->id,
                'name'      => $c->name,
                'starts_at' => $start,
                'ends_at'   => $end,
                // Whether the client set this one, or it follows the event.
                'own'       => $own,
                'minutes'   => $start && $end && $end->gt($start) ? $start->diffInMinutes($end) : null,
            ];
        })->values()->all();
    }

    /** "2 hrs", "45 min", "1 hr 30 min" — or nothing when there is no span. */
    public static function duration(?int $minutes): ?string
    {
        if (! $minutes || $minutes <= 0) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        return trim(($hours ? $hours . ' hr' . ($hours > 1 ? 's' : '') : '') . ($rest ? ' ' . $rest . ' min' : ''));
    }

    /**
     * Pairs of services whose hours cross.
     *
     * Named, not refused: the client is told so they can decide, because two
     * services at once is often exactly what they want.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function overlaps(Event $event): array
    {
        $rows = array_values(array_filter(self::of($event), fn ($r) => $r['starts_at'] && $r['ends_at']));
        $found = [];

        for ($i = 0; $i < count($rows); $i++) {
            for ($j = $i + 1; $j < count($rows); $j++) {
                if ($rows[$i]['starts_at']->lt($rows[$j]['ends_at']) && $rows[$j]['starts_at']->lt($rows[$i]['ends_at'])) {
                    $found[] = [$rows[$i]['name'], $rows[$j]['name']];
                }
            }
        }

        return $found;
    }
}
