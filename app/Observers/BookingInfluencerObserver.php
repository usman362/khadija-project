<?php

namespace App\Observers;

use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Models\Booking;

class BookingInfluencerObserver
{
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        if ($booking->status !== 'completed') {
            return;
        }

        app(InfluencerServiceInterface::class)->attributeBookingCommission($booking);
    }
}
