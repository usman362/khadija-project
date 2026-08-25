<?php

namespace App\Support;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * How many matching professionals have a given date free — for the request
 * wizard's availability step.
 *
 * The mockup for this step offers four buckets: Available, Limited, Not
 * Confirmed, Unavailable. Three of them do not exist in our data, and the one
 * that does is not called "available".
 *
 * `Availability` says why, in its own words: a day with no commitment on
 * GigResource is a day with no commitment ON GIGRESOURCE — the professional
 * may be booked through another channel entirely. So this counts exactly two
 * things it can prove, and the screen words them that way:
 *
 *   matched        professionals offering one of these services, in a state
 *                  the client can actually hire from (R38)
 *   nothingBooked  of those, how many have that date clear on their
 *                  GigResource calendar
 *
 * A professional with a booking that day is genuinely unavailable — that we
 * can state. Everything between the two is a guess, so there is nothing
 * between the two.
 */
final class ServiceAvailability
{
    /** Days either side of the chosen date offered as alternatives. */
    public const NEARBY_DAYS = 2;

    /**
     * @param  array<int>  $serviceIds
     * @return array{matched:int, nothing_booked:int, already_booked:int}
     */
    public static function on(array $serviceIds, ?string $state, Carbon $date): array
    {
        $pros = self::matching($serviceIds, $state);

        $clear = 0;
        foreach ($pros as $pro) {
            if (! in_array($date->toDateString(), Availability::busyDates($pro), true)) {
                $clear++;
            }
        }

        return [
            'matched'        => $pros->count(),
            'nothing_booked' => $clear,
            'already_booked' => $pros->count() - $clear,
        ];
    }

    /**
     * The chosen date plus the days either side, so a client whose date is
     * crowded can see whether moving it helps — the mockup's "are your dates
     * flexible?" prompt, answered with real counts instead of a suggestion.
     *
     * @param  array<int>  $serviceIds
     * @return array<int, array{date:Carbon, nothing_booked:int, matched:int, chosen:bool}>
     */
    public static function around(array $serviceIds, ?string $state, Carbon $date): array
    {
        $pros = self::matching($serviceIds, $state);

        // Each professional's calendar is read once, not once per day.
        $busy = $pros->mapWithKeys(fn (User $p) => [$p->id => Availability::busyDates($p)]);

        $days = [];
        for ($offset = -self::NEARBY_DAYS; $offset <= self::NEARBY_DAYS; $offset++) {
            $day = $date->copy()->addDays($offset);

            if ($day->isPast() && ! $day->isToday()) {
                continue;   // a date nobody can book is not an alternative
            }

            $clear = $busy->filter(fn (array $dates) => ! in_array($day->toDateString(), $dates, true))->count();

            $days[] = [
                'date'           => $day,
                'matched'        => $pros->count(),
                'nothing_booked' => $clear,
                'chosen'         => $day->isSameDay($date),
            ];
        }

        return $days;
    }

    /**
     * Professionals who offer one of these services and whom this client is
     * allowed to hire. R38 is a hard gate, not a ranking: a professional in
     * another state cannot take the work, so counting them would inflate every
     * number on the screen.
     *
     * @param  array<int>  $serviceIds
     */
    private static function matching(array $serviceIds, ?string $state)
    {
        if ($serviceIds === []) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->whereHas('serviceCategories', fn ($c) => $c->whereIn('categories.id', $serviceIds))
            ->when($state, fn ($q) => $q->whereHas('profile', fn ($p) => $p->whereRaw('UPPER(state) = ?', [strtoupper($state)])))
            ->get();
    }
}
