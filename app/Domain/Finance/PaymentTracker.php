<?php

namespace App\Domain\Finance;

use App\Models\Booking;
use App\Models\Finalization;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * My Events → Payment Tracker: one row per professional the client is
 * paying, and where the money stands (Sir Peter's Payment Tracker).
 *
 * A row is a finalization (the agreement a proposal becomes) or, for a
 * booking made without one, the booking itself. Each gets one status:
 *
 *   Paid            the work is completed, or the whole price is secured
 *   Partial         a deposit is secured, the rest is not
 *   Pending         a price is agreed, nothing paid yet
 *   Pending Amount  no price agreed yet
 *   Not Scheduled   a price is agreed but the event has no date
 *   Cancelled       the agreement or booking was called off
 *
 * Overdue is a flag on top, never a guess: only when an amount is set AND
 * the balance due date has passed with money still owed.
 *
 * The tab and the Payment Summary beside it both read this, so they add up.
 */
class PaymentTracker
{
    public const STATUSES = [
        'paid'           => 'Paid',
        'partial'        => 'Partial',
        'pending'        => 'Pending',
        'pending_amount' => 'Pending Amount',
        'not_scheduled'  => 'Not Scheduled',
        'cancelled'      => 'Cancelled',
    ];

    /** @return Collection<int, array> */
    public static function rows(User $client, ?CarbonInterface $since = null): Collection
    {
        // A client never owes themselves: a row naming their own account as
        // the professional is bad data, not a payment, and is left out.
        $finalizations = Finalization::where('client_id', $client->id)
            ->where('supplier_id', '!=', $client->id)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->with(['event:id,title,starts_at', 'supplier:id,name', 'category:id,name', 'booking:id,status,price'])
            ->get();

        $bookings = Booking::where('client_id', $client->id)
            ->where('supplier_id', '!=', $client->id)
            ->whereNotIn('id', Finalization::where('client_id', $client->id)->whereNotNull('booking_id')->pluck('booking_id'))
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->with(['event:id,title,starts_at', 'supplier:id,name', 'category:id,name'])
            ->get();

        /*
         * An agreement and its booking are one row. Most carry booking_id;
         * a booking made for the same professional and service without the
         * link is matched on event, professional and service instead, so the
         * same money is never listed twice.
         */
        $key = fn ($m) => $m->event_id . '|' . $m->supplier_id . '|' . ($m->category_id ?? '');
        $unlinked = $bookings->keyBy($key);
        $covered = [];

        $rows = $finalizations->map(function (Finalization $f) use ($unlinked, $key, &$covered) {
            $booking = $f->booking;
            if (! $booking && $f->status !== 'cancelled' && ($match = $unlinked->get($key($f))) && ! in_array($match->id, $covered, true)) {
                $booking = $match;
                $covered[] = $match->id;
            }

            return self::fromFinalization($f, $booking);
        });

        // Bookings that never went through an agreement (older flows).
        $bookings = $bookings->whereNotIn('id', $covered);

        return $rows->concat($bookings->map(fn (Booking $b) => self::fromBooking($b)))
            ->sortBy(fn ($r) => [$r['status'] === 'cancelled' ? 1 : 0, $r['due']?->timestamp ?? $r['date']?->timestamp ?? PHP_INT_MAX])
            ->values();
    }

    /** Money still owed with a due date, soonest first, for Upcoming Payments. */
    public static function upcoming(Collection $rows, int $take = 3): Collection
    {
        return $rows->filter(fn ($r) => $r['status'] !== 'cancelled' && $r['balance'] > 0 && $r['due'] !== null)
            ->sortBy(fn ($r) => $r['due']->timestamp)
            ->take($take)
            ->values();
    }

    /** Totals for the Payment Summary. Cancelled rows owe nothing. */
    public static function summary(Collection $rows): array
    {
        $live = $rows->where('status', '!=', 'cancelled');
        $overdue = (float) $live->where('overdue', true)->sum('balance');

        return [
            'total'   => (float) $live->sum('amount'),
            'paid'    => (float) $live->sum('paid'),
            'pending' => max(0, (float) $live->sum('balance') - $overdue),
            'overdue' => $overdue,
        ];
    }

    private static function fromFinalization(Finalization $f, ?Booking $booking = null): array
    {
        $amount = $f->agreed_price !== null ? (float) $f->agreed_price : null;
        $deposit = (float) ($f->deposit_amount ?? 0);
        $date = $f->service_start ?? $f->event?->starts_at;

        $status = match (true) {
            $f->status === 'cancelled',
            $booking && in_array($booking->status, ClientTotals::VOID_STATUSES, true) => 'cancelled',
            $booking?->status === 'completed' => 'paid',
            $f->isFunded() && $amount !== null && $deposit >= $amount => 'paid',
            $f->isFunded() => 'partial',
            $amount === null => 'pending_amount',
            $date === null => 'not_scheduled',
            default => 'pending',
        };

        $paid = match ($status) {
            'paid' => (float) $amount,
            'partial' => $deposit,
            default => 0.0,
        };

        return self::row($status, $amount, $paid, $f->balance_due_on, $date, [
            'professional' => $f->supplier?->name ?? 'Professional',
            'service'      => $f->category?->name,
            'event'        => $f->event?->title,
            'event_id'     => $f->event_id,
            // Pay Now carries on the agreement, which ends at Secure Payment.
            'pay_url'      => $f->status === 'in_progress' ? route('client.finalize.step', $f) : null,
            'view_url'     => $booking
                ? route('client.payments.show', $booking->id)
                : ($f->event_id ? route('client.events.show', $f->event_id) : null),
        ]);
    }

    private static function fromBooking(Booking $b): array
    {
        $amount = $b->price !== null ? (float) $b->price : null;
        $date = $b->event?->starts_at;

        $status = match (true) {
            in_array($b->status, ClientTotals::VOID_STATUSES, true) => 'cancelled',
            $b->status === 'completed' => 'paid',
            $amount === null => 'pending_amount',
            $date === null => 'not_scheduled',
            default => 'pending',
        };

        return self::row($status, $amount, $status === 'paid' ? (float) $amount : 0.0, null, $date, [
            'professional' => $b->supplier?->name ?? 'Professional',
            'service'      => $b->category?->name,
            'event'        => $b->event?->title,
            'event_id'     => $b->event_id,
            'pay_url'      => null,
            'view_url'     => route('client.payments.show', $b->id),
        ]);
    }

    private static function row(string $status, ?float $amount, float $paid, $due, $date, array $extra): array
    {
        $balance = $status === 'cancelled' || $amount === null ? 0.0 : max(0, $amount - $paid);

        return $extra + [
            'status'  => $status,
            'label'   => self::STATUSES[$status],
            'amount'  => $amount,
            'paid'    => $paid,
            'balance' => $balance,
            'due'     => $due,
            'date'    => $date,
            // Only when an amount is set and its due date has gone by.
            'overdue' => $amount !== null && $balance > 0 && $due !== null && $due->copy()->endOfDay()->isPast(),
        ];
    }
}
