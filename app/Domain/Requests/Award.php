<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Finalization;

/**
 * How a proposal becomes a booking, in one place.
 *
 * There were two ways, and they disagreed. The Compare page and the chat went
 * through finalization: scope, price, schedule, contract and the $2.99 fee,
 * then a booking. The Proposals list's Accept went straight to a confirmed
 * booking and skipped all of it. Ali, 2026-09-10: "han finalization pe laga
 * do". Every award now opens a finalization, and only finishing one books.
 *
 * Both steps are keyed on the service as well as the event and professional
 * (B6): a professional awarded two services on one request gets two
 * finalizations and two bookings, each with its own price.
 */
final class Award
{
    /** Open the agreement for this proposal, or pick up the one already open. */
    public static function openFinalization(Bid $bid): Finalization
    {
        return Finalization::firstOrCreate(
            ['event_id' => $bid->event_id, 'supplier_id' => $bid->supplier_id, 'category_id' => $bid->category_id],
            [
                'bid_id'        => $bid->id,
                'client_id'     => $bid->event->client_id,
                // PM-3: an emergency request's agreement starts at the
                // professional's price plus the 25% surcharge.
                'agreed_price'  => EmergencySurcharge::priceFor($bid->event, (float) $bid->amount),
                'scope'         => $bid->plan,
                'payment_terms' => $bid->terms,
            ]
        );
    }

    /**
     * The finished agreement becomes its booking, and the proposal is won.
     *
     * Only this makes a booking from a proposal. Everything before it was an
     * agreement either side could walk away from.
     */
    public static function book(Finalization $f, string $notes): Booking
    {
        $booking = Booking::updateOrCreate(
            ['event_id' => $f->event_id, 'supplier_id' => $f->supplier_id, 'category_id' => $f->category_id],
            [
                'client_id'  => $f->client_id,
                'created_by' => $f->client_id,
                'status'     => 'confirmed',
                'price'      => $f->agreed_price,
                'currency'   => 'USD',
                'booked_at'  => now(),
                'source'     => 'finalization',
                'notes'      => $notes,
            ]
        );

        $f->bid?->update(['status' => 'won']);

        return $booking;
    }
}
