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
