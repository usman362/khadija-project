<?php

namespace App\Domain\Finance;

use App\Models\Booking;
use App\Models\User;

/**
 * What a client has agreed to spend — one calculation, every finance page.
 *
 * OA-146: Bookings and Reports said $80. Spending and Payments said $5,080,
 * "across every booking". The difference was one cancelled $5,000 booking,
 * which Bookings excluded and the other two did not — and Spending went
 * further and showed it under "Remaining", presenting a cancelled booking as
 * money the client still had to spend.
 *
 * Four finance views, two totals, and the wrong one on the page a client would
 * check before paying. Money is the one place where "each page works out its
 * own figure" cannot survive contact with a second page.
 *
 * The rule, which is the Bookings label the PM approved: an agreed total is
 * what both sides agreed and neither has cancelled. Cancelled money is real
 * and belongs on the record — it has its own figure here, so a page can show
 * it deliberately, on its own line, rather than by forgetting to exclude it.
 */
class ClientTotals
{
    /**
     * Statuses that mean nobody owes anybody anything.
     *
     * Kept as a list rather than `!= cancelled` because the next status of
     * this kind — declined, withdrawn, expired — should be added here once,
     * not discovered missing on three pages.
     */
    public const VOID_STATUSES = ['cancelled', 'declined'];

    /**
     * Every booking of this client's, whatever its state.
     *
     * The event filter is a parameter rather than something each caller adds
     * afterwards. Spending and Payments both let a client narrow to one event,
     * and a shared total that quietly ignored that scope would replace four
     * pages disagreeing with each other by four pages disagreeing with their
     * own filter — which is harder to notice and no better.
     */
    public static function base(User $client, ?int $eventId = null): \Illuminate\Database\Eloquent\Builder
    {
        return Booking::where('client_id', $client->id)
            // Issue #117: a booking naming the client as its own professional
            // is bad data, not money. The lists on Spending and Payments now
            // leave those rows out, so the totals above them have to as well.
            ->notSelfSupplied()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId));
    }

    /**
     * The agreed total: what stands, with the void bookings taken out.
     *
     * `price` is the column the agreed figure lives in. Two others were tried
     * historically — total_amount and agreed_price — and neither has ever
     * existed on bookings, which is how every finance page once read $0.
     */
    public static function agreed(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)
            ->whereNotIn('status', self::VOID_STATUSES)
            ->sum('price');
    }

    /** What was agreed and then cancelled. Its own line, never inside a total. */
    public static function cancelled(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)
            ->whereIn('status', self::VOID_STATUSES)
            ->sum('price');
    }

    /** Money that actually moved. */
    public static function paid(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)->where('status', 'completed')->sum('price');
    }

    /** Agreed, both sides committed, not yet paid. */
    public static function agreedUnpaid(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)->where('status', 'confirmed')->sum('price');
    }

    /**
     * How many bookings that figure is made of.
     *
     * The money and the count have to come from the same rows. Messages read
     * the total off the bookings carrying a price and counted every confirmed
     * booking, so one booking with no price agreed yet turned "$80" into
     * "across 2 bookings" — an amount no pair of bookings adds up to.
     */
    public static function agreedUnpaidCount(User $client, ?int $eventId = null): int
    {
        return self::base($client, $eventId)->where('status', 'confirmed')->where('price', '>', 0)->count();
    }

    /** Sent, not yet accepted by the professional. */
    public static function awaiting(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)->where('status', 'requested')->sum('price');
    }

    /**
     * How many bookings the agreed total is made of.
     *
     * The figure and the count come from the same rows, for the same reason
     * agreedUnpaidCount does: a money panel sitting above a list invites the
     * reader to add the list up, and the only honest way to stop that going
     * wrong is to say how many bookings the figure actually covers. The list
     * beneath is filtered, searched and paginated; this is not.
     */
    public static function agreedCount(User $client, ?int $eventId = null): int
    {
        return self::base($client, $eventId)
            ->whereNotIn('status', self::VOID_STATUSES)
            ->count();
    }

    /**
     * Deposits taken against the bookings that still stand.
     *
     * Deposits are Payments carrying the event and supplier they were taken
     * for; that pair identifies the booking. Bookings summed every completed
     * deposit on the account against an agreed total that excludes cancelled
     * bookings — so a deposit paid before a cancellation counted as paid
     * towards a figure it was no longer part of, and Outstanding (floored at
     * zero) hid the difference. The two figures now come from the same set.
     */
    public static function depositsPaid(User $client, ?int $eventId = null): float
    {
        $standing = self::base($client, $eventId)
            ->whereNotIn('status', self::VOID_STATUSES)
            ->get(['event_id', 'supplier_id'])
            ->map(fn ($b) => $b->event_id . ':' . $b->supplier_id)
            ->flip();

        return (float) \App\Models\Payment::where('user_id', $client->id)
            ->where('status', 'completed')
            ->get()
            ->filter(fn ($p) => ($p->metadata['kind'] ?? null) === 'booking_deposit'
                && $standing->has(($p->metadata['event_id'] ?? '') . ':' . ($p->metadata['supplier_id'] ?? '')))
            ->sum('amount');
    }

    /**
     * Still to pay against what stands.
     *
     * Floored at zero: an overpayment is a refund question, not a negative
     * amount outstanding, and "-$120 remaining" on a client's own page reads
     * as a fault in the platform rather than a credit.
     */
    public static function outstanding(User $client, float $paidSoFar, ?int $eventId = null): float
    {
        return max(0.0, self::agreed($client, $eventId) - $paidSoFar);
    }
}
