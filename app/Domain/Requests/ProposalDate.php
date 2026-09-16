<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Booking;

/**
 * Does this proposal hold for the client's date?
 *
 * The Availability Match step tells the client to ask professionals to confirm
 * the date. Sir Peter, 2026-09-17: that has to show up where the choice is
 * made — the date each proposal is good for, and a warning before accepting
 * one that is not. A proposal carries the professional's own "I am available
 * on your date" tick; what it never carried was anything the client could see.
 *
 * Four answers, in the order they matter:
 *
 *   clash        the professional is already booked on another event that day
 *   confirmed    they ticked that they are free on the date
 *   unconfirmed  they did not say
 *   no_date      the request has no date yet, so there is nothing to check
 */
final class ProposalDate
{
    public const CLASH       = 'clash';
    public const CONFIRMED   = 'confirmed';
    public const UNCONFIRMED = 'unconfirmed';
    public const NO_DATE     = 'no_date';

    public static function check(Bid $bid): string
    {
        $date = $bid->event?->starts_at;

        if (! $date) {
            return self::NO_DATE;
        }

        // Already committed elsewhere that day outranks anything they ticked:
        // the tick may predate the other booking.
        $busy = Booking::where('supplier_id', $bid->supplier_id)
            ->where('event_id', '!=', $bid->event_id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q->whereDate('starts_at', $date->toDateString()))
            ->exists();

        if ($busy) {
            return self::CLASH;
        }

        return $bid->available_confirmed ? self::CONFIRMED : self::UNCONFIRMED;
    }

    /** Should accepting this proposal come with a warning? */
    public static function needsWarning(string $state): bool
    {
        return in_array($state, [self::CLASH, self::UNCONFIRMED], true);
    }

    /** What the client reads beside the proposal. */
    public static function label(string $state, ?\Carbon\CarbonInterface $date): string
    {
        $day = $date?->format('M j, Y');

        return match ($state) {
            self::CLASH       => "Already booked elsewhere on {$day}",
            self::CONFIRMED   => "Confirmed for {$day}",
            self::UNCONFIRMED => "Has not confirmed {$day}",
            default           => 'No event date set yet',
        };
    }

    /** The sentence shown before the client accepts a proposal that does not hold. */
    public static function warning(string $state, ?\Carbon\CarbonInterface $date, ?string $name): string
    {
        $who = $name ?: 'This professional';
        $day = $date?->format('M j, Y');

        return $state === self::CLASH
            ? "{$who} is already booked on another event on {$day}. Check with them before you go ahead."
            : "{$who} has not confirmed they are free on {$day}. Ask them to confirm before you go ahead.";
    }
}
