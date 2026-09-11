<?php

namespace App\Domain\Requests;

use App\Models\Event;

/**
 * The Emergency Request surcharge.
 *
 * Sir Peter, PM-3 (answered Sep 5): "ESR surcharge = flat +25% on top of the
 * base booking price, with the normal commission still applying to the full
 * higher amount. One surcharge, not a second fee structure."
 *
 * So the professional's quoted price is the base, and the agreed total on an
 * emergency request is that base plus 25%. It is added once, when the proposal
 * becomes an agreement, so everything that reads the agreed price afterwards
 * (deposit, balance, refunds) works on the full amount without knowing it is
 * there.
 */
final class EmergencySurcharge
{
    public const RATE = 0.25;

    public static function applies(?Event $event): bool
    {
        return $event !== null && RequestLifecycle::isEsr($event);
    }

    /** Base price plus the surcharge. */
    public static function total(float $base): float
    {
        return round($base * (1 + self::RATE), 2);
    }

    /** The surcharge on its own. */
    public static function amountOn(float $base): float
    {
        return round($base * self::RATE, 2);
    }

    /** The base inside a total that already carries the surcharge. */
    public static function baseOf(float $total): float
    {
        return round($total / (1 + self::RATE), 2);
    }

    /** What the agreement starts at for a proposal of this amount on this event. */
    public static function priceFor(?Event $event, float $proposed): float
    {
        return self::applies($event) ? self::total($proposed) : $proposed;
    }

    /** "25%", for the page. */
    public static function label(): string
    {
        return (int) round(self::RATE * 100) . '%';
    }
}
