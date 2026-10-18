<?php

namespace App\Domain\Money;

use App\Models\Booking;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The only way money is recorded, and the only way it is counted.
 *
 * Sir Peter approved this model on 16 October 2026. Its one idea, from his own
 * instruction: a booking total is never stored and edited, it is always the sum
 * of a list of money events. To correct something you add a reversing row. The
 * current total is a question we answer by adding up the list, so it can never
 * silently drift from what actually happened.
 *
 * Everything that touches money goes through here rather than writing rows of
 * its own, because the rules below only hold if there is one door:
 *
 *   - Entries are written once and never updated after they settle.
 *   - A correction is a new row pointing at the entry it reverses.
 *   - An amount is always positive; direction lives in the sign.
 *   - An entry already reversed cannot be reversed again.
 *
 * What is deliberately NOT here: anything that moves real money. This records
 * what happened. PaymentGuard still decides whether a charge may happen at all.
 */
final class Ledger
{
    /**
     * Write one money event.
     *
     * `secured` means the gateway has the money. `pending` means it has been
     * asked for. They are counted separately everywhere, because adding them
     * together is how a balance comes to promise more than can be withdrawn.
     */
    public static function record(
        string $type,
        float $amount,
        string $sign = '+',
        ?User $party = null,
        ?Event $event = null,
        ?Booking $booking = null,
        string $state = LedgerEntry::PENDING,
        ?Payment $payment = null,
        $subject = null,
        array $metadata = [],
    ): LedgerEntry {
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                'A ledger amount is always positive. Use the sign to say which way the money went.',
            );
        }

        $entry = new LedgerEntry([
            'entry_type'    => $type,
            'amount'        => round($amount, 2),
            'sign'          => $sign,
            'party_user_id' => $party?->id,
            'event_id'      => $event?->id ?? $booking?->event_id,
            'booking_id'    => $booking?->id,
            'state'         => $state,
            'payment_id'    => $payment?->id,
            'metadata'      => $metadata ?: null,
            'occurred_at'   => now(),
        ]);

        if ($subject) {
            $entry->subject()->associate($subject);
        }

        $entry->save();

        return $entry;
    }

    /**
     * Move an entry on in its life.
     *
     * The one update this class allows, and only forwards: pending to secured
     * when the gateway confirms, secured to released when it is paid out.
     * Nothing may go back, because an entry that has settled is a record of
     * something that happened.
     */
    public static function settle(LedgerEntry $entry, string $state): LedgerEntry
    {
        $order = [
            LedgerEntry::PENDING  => 0,
            LedgerEntry::SECURED  => 1,
            LedgerEntry::RELEASED => 2,
        ];

        // Refunded and failed are endings rather than steps, so they are
        // reachable from anywhere but lead nowhere.
        $ending = in_array($state, [LedgerEntry::REFUNDED, LedgerEntry::FAILED], true);

        if (! $ending) {
            $from = $order[$entry->state] ?? null;
            $to   = $order[$state] ?? null;

            if ($from === null || $to === null || $to <= $from) {
                throw new \InvalidArgumentException(
                    "A ledger entry cannot move from {$entry->state} to {$state}.",
                );
            }
        }

        $entry->forceFill(['state' => $state])->save();

        return $entry;
    }

    /**
     * Correct an entry by adding its opposite.
     *
     * Never an edit and never a delete. The original stays exactly as it was,
     * because what somebody was charged, and when, is a record. The reversal
     * carries the opposite sign and points back at what it corrects.
     */
    public static function reverse(LedgerEntry $entry, ?string $reason = null, ?float $amount = null): LedgerEntry
    {
        if ($entry->wasReversed()) {
            throw new \InvalidArgumentException('That entry has already been reversed.');
        }

        $amount ??= (float) $entry->amount;

        if ($amount > (float) $entry->amount) {
            throw new \InvalidArgumentException('A reversal cannot be larger than the entry it corrects.');
        }

        $reversal = new LedgerEntry([
            'entry_type'    => LedgerEntry::REFUND,
            'amount'        => round($amount, 2),
            'sign'          => $entry->sign === '+' ? '-' : '+',
            'party_user_id' => $entry->party_user_id,
            'event_id'      => $entry->event_id,
            'booking_id'    => $entry->booking_id,
            'state'         => LedgerEntry::REFUNDED,
            'reverses_id'   => $entry->id,
            'subject_type'  => $entry->subject_type,
            'subject_id'    => $entry->subject_id,
            'metadata'      => array_filter([
                'reason'   => $reason,
                'reverses' => $entry->entry_type,
                'partial'  => $amount < (float) $entry->amount ? true : null,
            ]),
            'occurred_at'   => now(),
        ]);

        $reversal->save();

        return $reversal;
    }

    // ── Reading ──────────────────────────────────────────────

    /** Every entry for a booking, oldest first, which is how a statement reads. */
    public static function forBooking(Booking $booking): Collection
    {
        return LedgerEntry::where('booking_id', $booking->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /** Every entry for a request, including fees charged before any booking. */
    public static function forEvent(Event $event): Collection
    {
        return LedgerEntry::where('event_id', $event->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * What a booking comes to.
     *
     * The charge plus every confirmed upgrade, less anything reversed. There is
     * no stored total to disagree with this, which is the whole point.
     */
    public static function bookingTotal(Booking $booking): float
    {
        return self::sum(
            self::forBooking($booking),
            [LedgerEntry::BOOKING_CHARGE, LedgerEntry::UPGRADE],
        );
    }

    /**
     * The four questions a dashboard asks, answered from the list.
     *
     * @return array{owed: float, held: float, earned: float, paid: float}
     */
    public static function standing(User $user): array
    {
        $entries = LedgerEntry::where('party_user_id', $user->id)->get();

        return [
            // Asked for, not yet received.
            'owed'   => self::stateSum($entries, LedgerEntry::PENDING),
            // The gateway has it, nobody has been paid from it.
            'held'   => self::stateSum($entries, LedgerEntry::SECURED),
            // Released, which is money this person has actually earned.
            'earned' => self::stateSum($entries, LedgerEntry::RELEASED),
            // Sent out.
            'paid'   => self::sum($entries, [LedgerEntry::PAYOUT]),
        ];
    }

    /**
     * What GigResource kept on something, after the processor took its cut.
     *
     * This is the figure Peter asked for a calculator of: the gross charge, the
     * actual provider fee, and what is left. The provider fee is read from the
     * ledger rather than estimated, so a refunded transaction shows the fee the
     * platform really lost.
     *
     * @return array{gross: float, platform: float, commission: float, provider_fee: float, net: float}
     */
    public static function margin(Collection $entries): array
    {
        $platform   = self::sum($entries, [LedgerEntry::PLATFORM_FEE]);
        $commission = self::sum($entries, [LedgerEntry::COMMISSION]);
        $provider   = self::sum($entries, [LedgerEntry::PROVIDER_FEE]);

        return [
            'gross'        => round(self::sum($entries, [LedgerEntry::BOOKING_CHARGE, LedgerEntry::UPGRADE, LedgerEntry::PLATFORM_FEE]), 2),
            'platform'     => round($platform, 2),
            'commission'   => round($commission, 2),
            'provider_fee' => round(abs($provider), 2),
            'net'          => round($platform + $commission - abs($provider), 2),
        ];
    }

    /**
     * Sum a set of entry types, honouring reversals.
     *
     * A reversal has the opposite sign and is summed with everything else, so a
     * refunded charge nets to nothing without the original being touched.
     */
    private static function sum(Collection $entries, array $types): float
    {
        return round(
            $entries
                ->filter(fn (LedgerEntry $e) => in_array($e->entry_type, $types, true)
                    || ($e->entry_type === LedgerEntry::REFUND
                        && in_array($e->metadata['reverses'] ?? null, $types, true)))
                ->sum(fn (LedgerEntry $e) => $e->signed()),
            2,
        );
    }

    private static function stateSum(Collection $entries, string $state): float
    {
        return round(
            $entries->where('state', $state)->sum(fn (LedgerEntry $e) => $e->signed()),
            2,
        );
    }
}
