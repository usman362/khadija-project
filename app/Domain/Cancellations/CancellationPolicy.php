<?php

namespace App\Domain\Cancellations;

use App\Models\Booking;

/**
 * Cancellation & Refund Policy v1 (2026-07-19) — what a client gets back.
 *
 * The rule the whole policy turns on: THE DEPOSIT IS NEVER REFUNDED. Not at
 * any notice period, not in a dispute, not here. It secures the professional's
 * date, and the moment it is taken that date is off the market. Every figure
 * below is computed on the held balance alone.
 *
 * Per-service, not per-event (R12). Cancelling the caterer on a five-service
 * wedding refunds against the caterer's line and touches nothing else.
 *
 * Deliberately absent: any calculation for a PROFESSIONAL cancelling. The
 * policy says the client is made whole "including deposit" and then puts the
 * mechanics out of scope with no spec written. Inventing that arithmetic here
 * would be inventing a refund rule, so the professional-side form records the
 * report and hands it to a human instead (R1–R3).
 */
final class CancellationPolicy
{
    /** Deposit — 30% default, negotiable per booking, bounded. LOCKED 2026-07-19c. */
    public const DEPOSIT_DEFAULT = 0.30;
    public const DEPOSIT_FLOOR   = 0.15;
    public const DEPOSIT_CEILING = 0.50;

    /**
     * Refund of the HELD BALANCE, by how much notice the client gives.
     *
     * The display table. The decision itself is in tierFor(), written the way
     * the policy words it — "> 30 days", "14–30", "< 14".
     *
     * @var array<int, array{share:float, label:string}>
     */
    public const TIERS = [
        ['share' => 1.00, 'label' => 'More than 30 days before the event'],
        ['share' => 0.50, 'label' => '14 to 30 days before the event'],
        ['share' => 0.00, 'label' => 'Less than 14 days before the event'],
    ];

    /**
     * What this cancellation would return, right now.
     *
     * @return array{
     *     agreed: float, deposit: float, balance: float,
     *     refund: float, retained: float, share: float,
     *     tier: string, days_before: ?int, has_terms: bool
     * }
     */
    public static function quote(Booking $booking, ?\DateTimeInterface $at = null): array
    {
        $at    = $at ?? now();
        $terms = $booking->latestFinalization ?? $booking->finalizations()->latest('id')->first();

        // The agreed price and the deposit come from the signed terms. A
        // booking with no finalization has no agreed deposit, so the price on
        // the booking stands in and the deposit is zero — never the 30%
        // default, which would quote a client a figure nobody agreed.
        $agreed  = (float) ($terms?->agreed_price ?? $booking->price ?? 0);
        $deposit = (float) ($terms?->deposit_amount ?? 0);
        $balance = max(0, $agreed - $deposit);

        $exact      = self::exactDaysBefore($booking, $at);
        $daysBefore = self::daysBefore($booking, $at);
        $tier       = self::tierFor($exact);

        $refund = round($balance * $tier['share'], 2);

        return [
            'agreed'      => round($agreed, 2),
            'deposit'     => round($deposit, 2),
            'balance'     => round($balance, 2),
            'refund'      => $refund,
            'retained'    => round($agreed - $refund, 2),
            'share'       => $tier['share'],
            'tier'        => $tier['label'],
            'days_before' => $daysBefore,
            'has_terms'   => $terms !== null,
        ];
    }

    /**
     * Whole days between now and the event.
     *
     * Null when the event has no date — and null falls into the least
     * generous tier on purpose. Guessing "probably plenty of notice" on a
     * booking with no date would refund money against an assumption.
     */
    public static function daysBefore(Booking $booking, ?\DateTimeInterface $at = null): ?int
    {
        $exact = self::exactDaysBefore($booking, $at);

        return $exact === null ? null : (int) floor($exact);
    }

    /**
     * The notice period as a fraction, which is what the tiers are decided on.
     *
     * Whole days are for display. Deciding the tier on them cost the client
     * real money at the boundary: an event 30 days and 23 hours away floors to
     * 30, drops out of the "more than 30 days" band, and takes half the
     * refundable balance off someone who by any plain reading gave more than
     * thirty days' notice.
     */
    public static function exactDaysBefore(Booking $booking, ?\DateTimeInterface $at = null): ?float
    {
        $starts = $booking->event?->starts_at;

        if ($starts === null) {
            return null;
        }

        $at = $at ? \Illuminate\Support\Carbon::parse($at) : now();

        return (float) $at->diffInDays($starts, false);
    }

    /**
     * The bands, written the way the policy words them.
     *
     * A null notice period — an event with no date — takes the least
     * generous band. Guessing "probably plenty of notice" would refund money
     * against an assumption.
     *
     * @return array{share:float, label:string}
     */
    public static function tierFor(int|float|null $daysBefore): array
    {
        if ($daysBefore === null) {
            return self::TIERS[2];
        }

        return match (true) {
            $daysBefore > 30  => self::TIERS[0],
            $daysBefore >= 14 => self::TIERS[1],
            default           => self::TIERS[2],
        };
    }

    /** Is a negotiated deposit inside the locked 15–50% band? */
    public static function depositPercentAllowed(float $percent): bool
    {
        return $percent >= self::DEPOSIT_FLOOR * 100 && $percent <= self::DEPOSIT_CEILING * 100;
    }

    /**
     * Can this booking still be cancelled by the client?
     *
     * A completed booking cannot: the work was delivered, and what the client
     * wants then is the dispute module, not a refund tier.
     */
    public static function cancellable(Booking $booking): bool
    {
        return in_array($booking->status, ['requested', 'confirmed'], true);
    }
}
