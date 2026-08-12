<?php

namespace App\Domain\Money;

use App\Models\Booking;
use App\Models\HeldFundEntry;
use App\Models\User;

/**
 * Where a booking's money actually stands — checklist row 181.
 *
 * The position is COMPUTED from the ledger, every time. There is no stored
 * balance, and there must not be one: a column and the movements behind it
 * are two copies of the same fact, and the day they disagree somebody is paid
 * the wrong amount with no way to tell which number was right.
 *
 * Never "escrow". The money is held at the licensed payment processor;
 * GigResource decides what happens to it and records that decision here. This
 * class is the record, not the custody — which is why posting an entry never
 * moves a penny on its own.
 */
final class HeldFunds
{
    /**
     * @return array{
     *     funded: float, released: float, refunded: float,
     *     held: float, deposit: float, settled_held: float, currency: string
     * }
     */
    public static function position(Booking $booking): array
    {
        $entries = HeldFundEntry::where('booking_id', $booking->id)
            ->whereNull('reverses')
            ->get();

        // A reversed entry and its reversal cancel out. Excluding both is
        // what makes a correction a correction rather than a second charge.
        $reversed = HeldFundEntry::where('booking_id', $booking->id)
            ->whereNotNull('reverses')->pluck('reverses');

        $live = $entries->reject(fn ($e) => $reversed->contains($e->id));

        $sum = fn ($filter) => round((float) $live->filter($filter)->sum('amount'), 2);

        $funded   = $sum(fn ($e) => $e->direction === HeldFundEntry::IN);
        $released = $sum(fn ($e) => $e->kind === HeldFundEntry::RELEASE);
        $refunded = $sum(fn ($e) => $e->kind === HeldFundEntry::REFUND);
        $deposit  = $sum(fn ($e) => $e->kind === HeldFundEntry::DEPOSIT);

        return [
            'funded'   => $funded,
            'released' => $released,
            'refunded' => $refunded,
            'deposit'  => $deposit,

            // What is still sitting there. Never negative: more going out
            // than came in is a bookkeeping error, and showing it as a
            // negative hold would present it as a normal state.
            'held'     => (float) max(0, round($funded - $released - $refunded, 2)),

            // The same figure counting only what the processor confirmed.
            // §8 of the dispute rules: a platform decision is not a payment.
            'settled_held' => (float) max(0, round(
                $live->where('state', HeldFundEntry::SETTLED)->sum(fn ($e) => $e->signedAmount()), 2,
            )),

            'currency' => $live->first()->currency ?? 'USD',
        ];
    }

    /** Money in — a deposit or the balance arriving into the hold. */
    public static function fund(
        Booking $booking, float $amount, string $kind, string $reason,
        $source = null, ?User $by = null,
    ): HeldFundEntry {
        return self::post($booking, $amount, HeldFundEntry::IN, $kind, $reason, $source, $by);
    }

    /**
     * Money out to the professional.
     *
     * Refuses to release more than is held. A ledger that lets you take out
     * more than went in is not a ledger, and this is the one place the error
     * is cheap to catch — after the processor has moved it, it is not.
     */
    public static function release(
        Booking $booking, float $amount, string $reason, $source = null, ?User $by = null,
    ): HeldFundEntry {
        self::assertAvailable($booking, $amount);

        return self::post($booking, $amount, HeldFundEntry::OUT, HeldFundEntry::RELEASE, $reason, $source, $by);
    }

    /** Money out to the client. Same ceiling, same reason. */
    public static function refund(
        Booking $booking, float $amount, string $reason, $source = null, ?User $by = null,
    ): HeldFundEntry {
        self::assertAvailable($booking, $amount);

        return self::post($booking, $amount, HeldFundEntry::OUT, HeldFundEntry::REFUND, $reason, $source, $by);
    }

    /**
     * Undo an entry by posting its opposite.
     *
     * Both stay on the ledger. "We released it and then took it back" is a
     * different fact from "we never released it", and only one of them is
     * true.
     */
    public static function reverse(HeldFundEntry $entry, string $reason, ?User $by = null): HeldFundEntry
    {
        return HeldFundEntry::create([
            'booking_id'  => $entry->booking_id,
            'event_id'    => $entry->event_id,
            'kind'        => HeldFundEntry::ADJUSTMENT,
            'direction'   => $entry->direction === HeldFundEntry::IN ? HeldFundEntry::OUT : HeldFundEntry::IN,
            'amount'      => $entry->amount,
            'currency'    => $entry->currency,
            'reason'      => $reason,
            'reverses'    => $entry->id,
            'recorded_by' => $by?->id,
        ]);
    }

    /** The processor confirmed the money moved. */
    public static function settle(HeldFundEntry $entry, ?string $processorReference = null): void
    {
        if ($entry->isSettled()) {
            return;
        }

        $entry->update([
            'state'               => HeldFundEntry::SETTLED,
            'processor_reference' => $processorReference,
            'settled_at'          => now(),
        ]);
    }

    /** The whole history, oldest first — what an admin or a dispute reads. */
    public static function ledger(Booking $booking)
    {
        return HeldFundEntry::where('booking_id', $booking->id)
            ->with('recorder')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    private static function post(
        Booking $booking, float $amount, string $direction, string $kind,
        string $reason, $source, ?User $by,
    ): HeldFundEntry {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('A ledger entry must move a positive amount.');
        }

        return HeldFundEntry::create([
            'booking_id'  => $booking->id,
            'event_id'    => $booking->event_id,
            'kind'        => $kind,
            'direction'   => $direction,
            'amount'      => round($amount, 2),
            'currency'    => $booking->currency ?: 'USD',
            'reason'      => $reason,
            'source_type' => $source ? $source::class : null,
            'source_id'   => $source?->id,
            'recorded_by' => $by?->id,
        ]);
    }

    private static function assertAvailable(Booking $booking, float $amount): void
    {
        $held = self::position($booking)['held'];

        if (round($amount, 2) > $held) {
            throw new \RuntimeException(
                'Only ' . number_format($held, 2) . ' is held on this booking.',
            );
        }
    }
}
