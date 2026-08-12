<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * The badge on a single gig — the per-booking counterpart to GigStats, which
 * counts the buckets.
 *
 * Checklist row 133. The buckets and the badges were separate pieces of code
 * that happened to share vocabulary, and they disagreed. A `requested` booking
 * — a proposal the client has not accepted — was badged "In Progress", while
 * the "In Progress" bucket above it counted something else entirely: accepted
 * gigs whose event is running right now. Same two words, two meanings, one
 * screen.
 *
 * Both now come from GigStats' definitions, so a badge cannot describe a state
 * the bucket does not count.
 *
 * On the approved wording: "Payment Secured" and "Paid" are claims about
 * money, so they are only said when the money is there. A confirmed gig with
 * no deposit taken reads "Booked", and a delivered gig nobody has paid for
 * reads "Awaiting Payment". A badge that promises payment that has not
 * happened would be a worse fault than the one this row reports.
 */
class GigStatus
{
    public const TONES = [
        'awaiting_client'  => '#d97706',
        'booked'           => '#2563eb',
        'payment_secured'  => '#059669',
        'work_in_progress' => '#f97316',
        'awaiting_payment' => '#6366f1',
        'paid'             => '#047857',
        'cancelled'        => '#dc2626',
    ];

    /**
     * @return array{0: string, 1: string, 2: string} label, colour, key
     */
    public static function for(Booking $booking, ?Collection $deposits = null): array
    {
        $key = self::keyFor($booking, $deposits);

        return [self::LABELS[$key], self::TONES[$key], $key];
    }

    private const LABELS = [
        'awaiting_client'  => 'Awaiting Client',
        'booked'           => 'Booked',
        'payment_secured'  => 'Payment Secured',
        'work_in_progress' => 'Work in Progress',
        'awaiting_payment' => 'Awaiting Payment',
        'paid'             => 'Paid',
        'cancelled'        => 'Cancelled',
    ];

    private static function keyFor(Booking $booking, ?Collection $deposits): string
    {
        if ($booking->status === 'cancelled') {
            return 'cancelled';
        }

        // A proposal nobody has accepted. This is the one the old map called
        // "In Progress", which is how the badge came to contradict the count.
        if ($booking->status === 'requested') {
            return 'awaiting_client';
        }

        if ($booking->status === 'completed') {
            return self::isPaid($booking, $deposits) ? 'paid' : 'awaiting_payment';
        }

        // Confirmed. GigStats' own definition of in-progress, applied to one
        // row rather than counted over many.
        $event = $booking->event;
        $running = $event
            && $event->starts_at !== null
            && $event->starts_at->lessThanOrEqualTo(now())
            && ($event->ends_at === null || $event->ends_at->greaterThanOrEqualTo(now()));

        if ($running) {
            return 'work_in_progress';
        }

        return self::isPaid($booking, $deposits) ? 'payment_secured' : 'booked';
    }

    /**
     * Has money actually been taken for this gig?
     *
     * Pass $deposits (from depositsFor()) when badging a list, so one query
     * covers the page instead of one per row.
     */
    private static function isPaid(Booking $booking, ?Collection $deposits): bool
    {
        $key = $booking->event_id . ':' . $booking->supplier_id;

        if ($deposits !== null) {
            return $deposits->has($key);
        }

        return self::depositsFor(collect([$booking]))->has($key);
    }

    /** Completed deposits for a set of bookings, keyed event:supplier. */
    public static function depositsFor(Collection $bookings): Collection
    {
        $eventIds = $bookings->pluck('event_id')->filter()->unique();

        if ($eventIds->isEmpty()) {
            return collect();
        }

        return Payment::where('status', 'completed')
            ->get()
            ->filter(fn ($p) => ($p->metadata['kind'] ?? null) === 'booking_deposit'
                && $eventIds->contains($p->metadata['event_id'] ?? null))
            ->keyBy(fn ($p) => ($p->metadata['event_id'] ?? '') . ':' . ($p->metadata['supplier_id'] ?? ''));
    }
}
