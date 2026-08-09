<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * When a professional is already committed.
 *
 * Checklist row 207 is explicit that the public profile must read the SAME
 * calendar data as the Gig Operations Hub and My Gigs — "not a second
 * independent calendar", per the IA Consolidation Plan's one-calculation-
 * source principle. So this reads exactly what My Calendar reads: assigned
 * shifts, and the events behind this professional's bookings. There is no
 * separate availability table, and there must not be one, or the profile will
 * eventually tell a client something the professional's own calendar denies.
 *
 * What it deliberately does NOT do: claim someone is free. A day with no
 * commitment on GigResource is a day with no commitment ON GIGRESOURCE — the
 * professional may well be booked through another channel. Every method here
 * answers "is this day already taken on the platform", and the profile page
 * words it that way.
 */
final class Availability
{
    /** How far ahead the public profile looks. */
    public const HORIZON_DAYS = 60;

    /**
     * Days in the window that already carry a shift or a booked event.
     *
     * @return array<int, string> Y-m-d, ascending, unique
     */
    public static function busyDates(User $user, int $days = self::HORIZON_DAYS): array
    {
        $from = now()->startOfDay();
        $to   = $from->copy()->addDays($days)->endOfDay();

        $dates = [];

        foreach (self::commitments($user, $from, $to) as [$start, $end]) {
            // A three-day festival blocks three days, not one. Walk the span
            // rather than stamping only the start — the first version of this
            // showed a professional as free on the middle day of their own
            // multi-day event.
            $cursor = $start->copy()->startOfDay();
            $last   = ($end ?? $start)->copy()->startOfDay();

            while ($cursor->lte($last) && $cursor->lte($to)) {
                if ($cursor->gte($from)) {
                    $dates[$cursor->toDateString()] = true;
                }
                $cursor->addDay();
            }
        }

        $out = array_keys($dates);
        sort($out);

        return $out;
    }

    /**
     * The two near-term windows the target mockup asks for.
     *
     * @return array<int, array{label:string, free:bool}>
     */
    public static function windows(User $user): array
    {
        $busy = self::busyDates($user, 21);

        return [
            ['label' => 'This weekend', 'free' => ! self::anyBusyBetween($busy, ...self::comingWeekend())],
            ['label' => 'Next week',    'free' => ! self::anyBusyBetween($busy, ...self::nextWeek())],
        ];
    }

    /** Is anything on the platform booked in the next fortnight? */
    public static function hasUpcomingWork(User $user): bool
    {
        return self::busyDates($user, 14) !== [];
    }

    /**
     * The raw commitments — shifts and booked events, the calendar's two sources.
     *
     * `requested` bookings are excluded on purpose. A proposal nobody has
     * accepted is not a commitment, and showing a professional as busy on the
     * strength of one would cost them work they have not won.
     *
     * @return array<int, array{0: Carbon, 1: ?Carbon}>
     */
    public static function commitments(User $user, Carbon $from, Carbon $to): array
    {
        $out = [];

        $shifts = Shift::where('supplier_id', $user->id)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $to)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $from))
            ->get(['starts_at', 'ends_at']);

        foreach ($shifts as $shift) {
            $out[] = [$shift->starts_at, $shift->ends_at];
        }

        $bookings = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q
                ->whereNotNull('starts_at')
                ->where('starts_at', '<=', $to))
            ->with('event:id,starts_at,ends_at')
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->event?->starts_at) {
                $out[] = [$booking->event->starts_at, $booking->event->ends_at];
            }
        }

        return $out;
    }

    /** @return array{0: string, 1: string} */
    private static function comingWeekend(): array
    {
        $saturday = now()->isSaturday() ? now()->copy() : now()->copy()->next(Carbon::SATURDAY);

        return [$saturday->toDateString(), $saturday->copy()->addDay()->toDateString()];
    }

    /** @return array{0: string, 1: string} */
    private static function nextWeek(): array
    {
        $monday = now()->copy()->startOfWeek()->addWeek();

        return [$monday->toDateString(), $monday->copy()->addDays(6)->toDateString()];
    }

    private static function anyBusyBetween(array $busy, string $from, string $to): bool
    {
        foreach ($busy as $date) {
            if ($date >= $from && $date <= $to) {
                return true;
            }
        }

        return false;
    }
}
