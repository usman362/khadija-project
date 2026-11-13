<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Carbon;

/**
 * The clock this platform is read by.
 *
 * Issue #115, logged as "the Activity tab's timestamp looks four hours out".
 * It was not the Activity tab. config('app.timezone') is UTC, which is the
 * right thing for storage, and nothing in the application ever converted for
 * display — so every time on every page was UTC, and a client in Baltimore
 * read a request they had just created as made at 5:06 PM while their own
 * clock said 1:06 PM, on a page that simultaneously said "3 minutes ago".
 *
 * There is one clock, not a picker, and that is a finding rather than a
 * shortcut: config('geo.allowed_states') is Maryland, Virginia, Washington
 * D.C., Delaware, Pennsylvania, New Jersey and West Virginia, and every one
 * of the seven keeps Eastern time. A chooser would be offering a decision
 * this platform does not yet have, and a map of state to zone would be sixty
 * lines all answering the same thing.
 *
 * It is also the right answer for a client who is elsewhere: the event is in
 * Maryland, so its times are Maryland's. Someone booking a Baltimore wedding
 * from California wants to read the start time the way the venue will.
 *
 * The account column is honoured if something ever sets it, so the day Peter
 * opens a state on another clock this becomes a setting instead of a
 * constant. Nothing offers it today, because nothing can mean anything by it.
 *
 * Storage does not move. Times are written in UTC and read back as the same
 * instant; only the clock they are shown on changes.
 */
final class DisplayTimezone
{
    /** Where the seven jurisdictions are, and so where the platform is. */
    public const FALLBACK = 'America/New_York';

    /** The clock for whoever is signed in. */
    public static function current(): string
    {
        return self::forUser(auth()->user());
    }

    public static function forUser(?User $user): string
    {
        $chosen = (string) ($user?->timezone ?? '');

        return $chosen !== '' && self::isReal($chosen) ? $chosen : self::platformDefault();
    }

    /** True when this account carries a zone of its own rather than the platform's. */
    public static function chosen(?User $user): bool
    {
        $chosen = (string) ($user?->timezone ?? '');

        return $chosen !== '' && self::isReal($chosen);
    }

    /**
     * The short name to print beside a time: EDT in July, EST in January.
     *
     * Taken for the moment in question rather than for now, because an event
     * in summer and the same event's record in winter do not wear the same
     * letters.
     */
    public static function abbreviation(?string $tz = null, ?Carbon $at = null): string
    {
        $zone = $tz ?: self::current();

        return ($at ? $at->copy() : Carbon::now())->setTimezone($zone)->format('T');
    }

    /** The zone in words, for a settings page. */
    public static function label(?string $tz = null): string
    {
        $zone = $tz ?: self::current();

        return $zone === self::FALLBACK ? 'Eastern' : $zone;
    }

    /**
     * A wall-clock time somebody typed, as an instant.
     *
     * A client filling in "7:00 PM" means seven in the evening on the
     * platform's clock. Stored without converting it becomes seven in the
     * evening UTC — the afternoon here — and the form then reads back a time
     * nobody entered. While display was also UTC the two mistakes cancelled
     * out, which is why this went unnoticed for so long.
     */
    public static function toUtc(?string $localDateTime, ?User $user = null): ?string
    {
        if (blank($localDateTime)) {
            return null;
        }

        $zone = $user ? self::forUser($user) : self::current();

        try {
            return Carbon::parse($localDateTime, $zone)->setTimezone('UTC')->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            // Not a time we can read: hand it back untouched and let
            // validation say so, rather than inventing one.
            return $localDateTime;
        }
    }

    /**
     * A user-entered date or time, read on the platform's clock.
     *
     * Carbon::parse on a bare string uses the application timezone, which is
     * UTC — so a wizard that assembled "the 21st" and "18:00" into a Carbon
     * produced six in the evening UTC and stored it faithfully, which is one
     * in the afternoon here. The model converts strings it is handed; a
     * Carbon arrives already believing it knows its own zone, so the place
     * to be right is where it is built.
     */
    public static function parse(?string $value, ?User $user = null): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value, $user ? self::forUser($user) : self::current());
    }

    /** The other direction, for putting a stored instant back into a form field. */
    public static function forInput(?Carbon $at, ?User $user = null): ?string
    {
        if (! $at) {
            return null;
        }

        return $at->copy()->setTimezone($user ? self::forUser($user) : self::current())->format('Y-m-d\TH:i');
    }

    /**
     * The pair of UTC instants that bound one day on the platform's clock.
     *
     * whereDate('starts_at', today) compares a LOCAL calendar date against a
     * column stored in UTC, so a gig at nine in the evening Eastern is written
     * as two in the morning the next day and drops off today's schedule onto
     * tomorrow's. On a marketplace whose work is almost entirely evening work,
     * that is most of the day's jobs.
     *
     * Returned as a half-open range, [start, end), so a job at exactly
     * midnight belongs to one day rather than to both.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dayWindow(Carbon|string|null $localDay = null, ?User $user = null): array
    {
        $zone = $user ? self::forUser($user) : self::current();

        $start = $localDay instanceof Carbon
            ? $localDay->copy()->setTimezone($zone)->startOfDay()
            : Carbon::parse($localDay ?: 'today', $zone)->startOfDay();

        return [
            $start->copy()->utc(),
            $start->copy()->addDay()->utc(),
        ];
    }

    /**
     * The pair of UTC instants bounding a run of days on the platform's clock.
     *
     * dayWindow() for a calendar rather than a day. The same trap in its
     * other form: whereBetween('starts_at', [$first->startOfDay(),
     * $last->endOfDay()]) builds its bounds from local intent and compares
     * them against a UTC column, so a month grid loses the late events of its
     * last day and borrows them from the day after its first.
     *
     * Half-open, [start, end), to match dayWindow.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function rangeWindow(Carbon|string $firstDay, Carbon|string $lastDay, ?User $user = null): array
    {
        // Read by the calendar date each bound is written as, not by moving
        // it between clocks. A grid's edges arrive as startOfMonth and
        // endOfWeek, which Carbon builds in the application zone: converting
        // 1 November 00:00 UTC to Eastern gives the evening of 31 October,
        // and a month that begins a day early is a different bug from the
        // one being fixed. dayWindow does the converting, from a plain date.
        return [
            self::dayWindow(self::plainDay($firstDay), $user)[0],
            self::dayWindow(self::plainDay($lastDay), $user)[1],
        ];
    }

    /** A bound as the day it is written as, with no clock attached. */
    private static function plainDay(Carbon|string $day): string
    {
        return $day instanceof Carbon ? $day->format('Y-m-d') : $day;
    }

    /**
     * Today, on the platform's clock.
     *
     * Carbon::today() is the UTC today, which after 8pm Eastern is tomorrow:
     * a calendar built on it highlights the wrong cell all evening, every
     * evening. Screens that hand-rolled this from config() got the platform
     * zone but not the account's.
     */
    public static function today(?User $user = null): Carbon
    {
        return Carbon::today($user ? self::forUser($user) : self::current());
    }

    public static function platformDefault(): string
    {
        $configured = (string) config('app.display_timezone', self::FALLBACK);

        return self::isReal($configured) ? $configured : self::FALLBACK;
    }

    private static function isReal(string $tz): bool
    {
        try {
            new CarbonTimeZone($tz);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
