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

    /** Sent, not yet accepted by the professional. */
    public static function awaiting(User $client, ?int $eventId = null): float
    {
        return (float) self::base($client, $eventId)->where('status', 'requested')->sum('price');
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
