<?php

namespace App\Domain\Calendar;

use App\Models\AvailabilityDay;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Who is free when, and the one place that answers it.
 *
 * Sir Peter asked why the legend says Unavailable but not Available. It said
 * one and not the other because neither was recorded: a client's calendar was
 * built from their events, and a professional's from their bookings and
 * shifts, and both of those say when somebody is BUSY. Being busy and being
 * unavailable are not the same claim, and neither of them is being free.
 *
 * So there are three answers here and the difference between them matters:
 *
 *   available    the person said so
 *   unavailable  the person said so
 *   unknown      nobody has said anything, which is most days
 *
 * Unknown is never shown as free. A calendar that colours every unanswered
 * day green is telling a client something nobody told it.
 */
final class Availability
{
    public const AVAILABLE   = AvailabilityDay::AVAILABLE;
    public const UNAVAILABLE = AvailabilityDay::UNAVAILABLE;

    /** How each state is named and coloured, wherever it is drawn. */
    public const STATES = [
        self::AVAILABLE   => ['Available', '#10b981'],
        // Red, from Sir Peter's 4 October comparison. Grey now means the
        // third answer, which is no answer at all.
        self::UNAVAILABLE => ['Unavailable', '#ef4444'],
    ];

    public static function isState(?string $state): bool
    {
        return in_array($state, [self::AVAILABLE, self::UNAVAILABLE], true);
    }

    /**
     * What this person has said about every day in a range.
     *
     * @return Collection<string, AvailabilityDay>  keyed Y-m-d
     */
    public static function between(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return AvailabilityDay::where('user_id', $user->id)
            ->whereBetween('day', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get()
            ->keyBy(fn (AvailabilityDay $d) => $d->day->format('Y-m-d'));
    }

    /**
     * Say something about a day, or take it back.
     *
     * Marking a day the state it already holds clears it, so the same control
     * that sets an answer removes it and there is no separate way to undo.
     * Passing null clears it outright.
     */
    public static function mark(User $user, string $day, ?string $state): ?AvailabilityDay
    {
        $date = Carbon::createFromFormat('Y-m-d', $day)->startOfDay();

        $existing = AvailabilityDay::where('user_id', $user->id)
            ->whereDate('day', $date)
            ->first();

        if ($state === null || ! self::isState($state) || ($existing && $existing->state === $state)) {
            $existing?->delete();

            return null;
        }

        if ($existing) {
            $existing->update(['state' => $state]);

            return $existing;
        }

        return AvailabilityDay::create([
            'user_id' => $user->id,
            'day'     => $date,
            'state'   => $state,
        ]);
    }

    /**
     * Mark every day in a range, for "block these dates".
     *
     * @return int  how many days were written
     */
    public static function markRange(User $user, string $from, string $to, string $state): int
    {
        if (! self::isState($state)) {
            return 0;
        }

        $start = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $to)->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // A range is a convenience, not a licence to write a decade of rows.
        if ($start->diffInDays($end) > 366) {
            $end = $start->copy()->addDays(366);
        }

        $written = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            AvailabilityDay::updateOrCreate(
                ['user_id' => $user->id, 'day' => $d->copy()],
                ['state' => $state],
            );
            $written++;
        }

        return $written;
    }

    /** How many days in a range carry each answer. */
    public static function tally(Collection $days): array
    {
        return [
            self::AVAILABLE   => $days->where('state', self::AVAILABLE)->count(),
            self::UNAVAILABLE => $days->where('state', self::UNAVAILABLE)->count(),
        ];
    }
}
