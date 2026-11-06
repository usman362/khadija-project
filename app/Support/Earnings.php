<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\User;

/**
 * One professional's money, computed in one place.
 *
 * This exists because the same figures were being derived in three separate
 * spots — the Earnings page (which returned hardcoded zeros and so showed
 * "No earnings recorded yet"), the Transactions page (which queried for real),
 * and the payout request guard (its own copy of the same query). The result
 * was one account reporting Pending = $0.00 on one screen and $4,826.00 on
 * another at the same moment.
 *
 * Two figures matter and are easy to confuse:
 *   • gross   — the booking price the client agreed to
 *   • net     — what the professional actually receives, after commission
 *
 * Everything returned here is NET. A gross figure on the payout screen would
 * let a professional withdraw the platform's commission along with their own
 * money, so the withdrawal guard must use the same basis the screen shows.
 */
class Earnings
{
    /** Bookings that count as money already made. */
    private const EARNED_STATUSES = ['completed'];

    /*
     * Bookings that count as money on the way.
     *
     * in_progress belongs here: a job being worked on right now was falling
     * through both lists, so it appeared in neither total and the page added
     * up to less than the work the professional actually had on.
     */
    private const PENDING_STATUSES = ['pending', 'confirmed', 'in_progress'];

    /**
     * The full picture for one professional.
     *
     * @return array{
     *     total: int, gross: float, earned: float, commission: float,
     *     commissionPct: float, pending: float, pendingCount: int,
     *     withdrawn: float, requested: float, available: float,
     *     hasActivity: bool
     * }
     */
    public static function forProfessional(User $user): array
    {
        $bookings = Booking::where('supplier_id', $user->id);

        $gross        = (float) (clone $bookings)->whereIn('status', self::EARNED_STATUSES)->sum('price');
        $pendingGross = (float) (clone $bookings)->whereIn('status', self::PENDING_STATUSES)->sum('price');
        $earned       = Commission::netOf($gross, $user);

        $payouts   = Payout::where('user_id', $user->id);
        $withdrawn = (float) (clone $payouts)->where('status', 'paid')->sum('amount');
        $requested = (float) (clone $payouts)->where('status', 'requested')->sum('amount');

        $total = (clone $bookings)->count();

        return [
            'total'         => $total,
            'gross'         => $gross,
            'earned'        => $earned,
            'commission'    => round($gross - $earned, 2),
            'commissionPct' => Commission::rateFor($user),
            'pending'       => Commission::netOf($pendingGross, $user),
            'pendingCount'  => (clone $bookings)->whereIn('status', self::PENDING_STATUSES)->count(),
            'withdrawn'     => $withdrawn,
            'requested'     => $requested,
            // Still withdrawable: earned, less what has been paid out, less
            // what is already in flight.
            'available'     => max(0.0, $earned - $withdrawn - $requested),
            // An account with bookings has a story to tell even at $0 earned —
            // "no earnings yet" is only true when there is nothing at all.
            'hasActivity'   => $total > 0,
            'payoutCount'   => (clone $payouts)->where('status', 'paid')->count(),
        ];
    }

    /**
     * The same figures over a window, for the tier band on Payments & Payouts.
     *
     * Khadijah's screen reports the tier's earnings over the last ninety days
     * rather than over all time, because that is the span somebody compares a
     * tier against when deciding whether to move.
     *
     * @return array{gross: float, fees: float, net: float, from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon}
     */
    public static function window(User $user, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $gross = (float) Booking::where('supplier_id', $user->id)
            ->whereIn('status', self::EARNED_STATUSES)
            ->whereBetween('bookings.created_at', [$from, $to])
            ->sum('price');

        return [
            'gross' => round($gross, 2),
            'fees'  => Commission::on($gross, $user),
            'net'   => Commission::netOf($gross, $user),
            'from'  => \Illuminate\Support\Carbon::instance(\Illuminate\Support\Carbon::parse($from)),
            'to'    => \Illuminate\Support\Carbon::instance(\Illuminate\Support\Carbon::parse($to)),
        ];
    }

    /**
     * One booking as a line of money, with the whole arithmetic behind it.
     *
     * This is the breakdown Khadijah's screen opens each row into. Three
     * things decide what a booking is worth:
     *
     *   • a completed booking is worth its price;
     *   • a cancelled booking is worth whatever the signed cancellation
     *     terms let the professional retain, which is rarely nothing and
     *     is never the full price;
     *   • anything else is worth nothing yet, and says so rather than
     *     reporting a zero that looks like a finished sum.
     *
     * The fee is then taken on that amount, not on the original price, which
     * is the difference between a fee a professional can check and one they
     * have to take on trust.
     *
     * @return array<string, mixed>
     */
    public static function transaction(Booking $booking, User $user): array
    {
        $price = (float) ($booking->price ?? 0);
        $rate  = Commission::rateFor($user);

        $cancelled = $booking->status === 'cancelled';
        $adjust    = 0.0;
        $note      = null;

        if ($cancelled && $price > 0) {
            /*
             * What the professional keeps when a booking is cancelled, from
             * the signed terms and the notice given. Without signed terms
             * there is no agreed deposit, so nothing is retained: quoting the
             * 30% default here would credit money nobody agreed to.
             */
            $quote    = \App\Domain\Cancellations\CancellationPolicy::quote($booking);
            $retained = $quote['has_terms'] ? (float) $quote['retained'] : 0.0;
            $adjust   = round($retained - $price, 2);

            $note = $quote['has_terms']
                ? 'Cancelled ' . $quote['tier'] . '. Per the signed terms, $'
                    . number_format($retained, 2) . ' of $' . number_format($price, 2) . ' is retained.'
                : 'Cancelled before any terms were signed, so there is no agreed deposit to retain.';
        }

        /*
         * What the fee is charged on, and so what the row is worth.
         *
         * A booking still being worked on is worth its price: the Payment
         * Status column is what says the money has not arrived yet. Leaving
         * it at zero here made the row contradict the Pending Earnings total
         * at the top of the same screen. A request nobody has accepted is
         * worth nothing, and says so.
         */
        $basis = match (true) {
            $booking->status === 'completed'                    => $price,
            $cancelled                                          => max(0.0, round($price + $adjust, 2)),
            in_array($booking->status, self::PENDING_STATUSES, true) => $price,
            default                                             => 0.0,
        };

        $fee = Commission::on($basis, $user);

        /*
         * Where the money has got to. Read from the ledger, which is the only
         * record of money actually moving: a booking's own status says what
         * was agreed, not what was paid.
         */
        $released = $booking->relationLoaded('ledgerEntries')
            ? $booking->ledgerEntries
            : \App\Models\LedgerEntry::where('booking_id', $booking->id)->get();

        $paidOut = $released->first(fn ($e) => in_array($e->entry_type, ['payout', 'milestone_release'], true)
            && $e->state === 'released');

        $secured = $released->first(fn ($e) => $e->entry_type === 'booking_charge' && $e->state === 'secured');

        /*
         * The three words the Payment Status column uses, kept in step with
         * the cards above the table: a booking counted as Pending Earnings
         * there must not read "Not Earned" here on the same screen.
         */
        [$state, $stateNote] = match (true) {
            $paidOut !== null => ['Paid Out', 'Released to you.'],
            $cancelled && $basis <= 0
                => ['Not Earned', 'Cancelled with nothing retained.'],
            $secured !== null => ['Pending', 'The client has paid. Awaiting release.'],
            $booking->status === 'completed'
                => ['Pending', 'Completed. Awaiting payout.'],
            $cancelled        => ['Pending', 'Retained under the cancellation terms.'],
            in_array($booking->status, self::PENDING_STATUSES, true)
                => ['Pending', 'Event not completed yet.'],
            default           => ['Not Earned', 'Not accepted yet.'],
        };

        return [
            'booking'   => $booking,
            'date'      => $booking->booked_at ?? $booking->created_at,
            'price'     => round($price, 2),
            'adjust'    => $adjust,
            'basis'     => $basis,
            'rate'      => $rate,
            'fee'       => $fee,
            'net'       => round($basis - $fee, 2),
            'state'     => $state,
            'state_note' => $stateNote,
            'cancel_note' => $note,
            // Only a payout that has happened has a date. Nothing on this
            // platform schedules the next one, so nothing is estimated.
            'paid_at'   => $paidOut?->occurred_at,
        ];
    }
}
