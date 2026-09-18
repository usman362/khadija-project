<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Finalization;

/**
 * Does this proposal hold for the client's date?
 *
 * "One event. One date for all services." (Sir Peter's Availability Match.)
 * The client offers a preferred date and backups; each professional names
 * the one they can do on their proposal (bids.confirmed_date). This decides,
 * for each proposal, what the client is told and whether it can be accepted.
 *
 *   clash        already booked on another event that day
 *   mismatch     for a different day from a service already accepted on this
 *                request; it cannot be accepted, because every service must
 *                be on the same date
 *   confirmed    for the client's preferred date (or the date already chosen)
 *   different    for one of the client's backup dates; accepting it moves the
 *                whole request to that date, so the client is warned first
 *   unconfirmed  the professional did not say
 *   no_date      the request has no date yet
 */
final class ProposalDate
{
    public const CLASH       = 'clash';
    public const MISMATCH    = 'mismatch';
    public const CONFIRMED   = 'confirmed';
    public const DIFFERENT   = 'different';
    public const UNCONFIRMED = 'unconfirmed';
    public const NO_DATE     = 'no_date';

    /** The day this proposal is for, if it says. */
    public static function dayOf(Bid $bid): ?string
    {
        if ($bid->confirmed_date) {
            return $bid->confirmed_date->toDateString();
        }

        // A proposal from before professionals picked a date: the tick meant
        // the preferred one.
        return $bid->available_confirmed ? $bid->event?->starts_at?->toDateString() : null;
    }

    /**
     * The date this request is now fixed to, once any service has been
     * accepted (an agreement opened or a booking made). Null while every
     * option is still open.
     */
    public static function lockedDate(\App\Models\Event $event): ?string
    {
        $taken = Finalization::where('event_id', $event->id)->where('status', '!=', 'cancelled')->pluck('bid_id')
            ->merge(Booking::where('event_id', $event->id)->whereIn('status', ['confirmed', 'completed'])->get()
                ->map(fn ($b) => Bid::where('event_id', $b->event_id)->where('supplier_id', $b->supplier_id)
                    ->where('category_id', $b->category_id)->value('id')))
            ->filter()->unique();

        foreach (Bid::whereIn('id', $taken)->with('event')->get() as $b) {
            if ($day = self::dayOf($b)) {
                return $day;
            }
        }

        return null;
    }

    public static function check(Bid $bid): string
    {
        $event = $bid->event;
        $date = $event?->starts_at;

        if (! $date) {
            return self::NO_DATE;
        }

        $day = self::dayOf($bid);

        // Already committed elsewhere that day outranks anything they said.
        $busy = Booking::where('supplier_id', $bid->supplier_id)
            ->where('event_id', '!=', $bid->event_id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q->whereDate('starts_at', $day ?? $date->toDateString()))
            ->exists();

        if ($busy) {
            return self::CLASH;
        }

        if ($day === null) {
            return self::UNCONFIRMED;
        }

        $locked = self::lockedDate($event);

        if ($locked !== null) {
            return $day === $locked ? self::CONFIRMED : self::MISMATCH;
        }

        return $day === $date->toDateString() ? self::CONFIRMED : self::DIFFERENT;
    }

    /** Should accepting this proposal come with a warning? */
    public static function needsWarning(string $state): bool
    {
        return in_array($state, [self::CLASH, self::UNCONFIRMED, self::DIFFERENT], true);
    }

    /** Can this proposal be accepted at all? */
    public static function blocks(string $state): bool
    {
        return $state === self::MISMATCH;
    }

    /** What the client reads beside the proposal. */
    public static function label(string $state, ?\Carbon\CarbonInterface $date, ?Bid $bid = null): string
    {
        $o = $bid ? EventDates::option($bid->event, self::dayOf($bid)) : null;
        $day = $o ? EventDates::label($o, false) : $date?->format('M j, Y');

        return match ($state) {
            self::CLASH       => "Already booked elsewhere on {$day}",
            self::MISMATCH    => "Different date ({$day}) from your accepted services",
            self::CONFIRMED   => "Confirmed for {$day}",
            self::DIFFERENT   => "Different date: {$day} (your backup)",
            self::UNCONFIRMED => 'Has not confirmed a date',
            default           => 'No event date set yet',
        };
    }

    /** The sentence shown before the client accepts a proposal that does not hold. */
    public static function warning(string $state, ?\Carbon\CarbonInterface $date, ?string $name, ?Bid $bid = null): string
    {
        $who = $name ?: 'This professional';
        $o = $bid ? EventDates::option($bid->event, self::dayOf($bid)) : null;
        $day = $o ? EventDates::label($o, false) : $date?->format('M j, Y');

        return match ($state) {
            self::CLASH     => "{$who} is already booked on another event on {$day}. Check with them before you go ahead.",
            self::DIFFERENT => "{$who} can do {$day}, one of your backup dates, not your preferred date. Accepting this moves your event to {$day}, and every other service must be for that date too.",
            self::MISMATCH  => "{$who} is for {$day}, but you have already accepted a service for a different date. All services must be for the same date.",
            default         => "{$who} has not confirmed they can do your date. Ask them to confirm before you go ahead.",
        };
    }
}
