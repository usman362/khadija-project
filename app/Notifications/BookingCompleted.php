<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Fired at the client when the professional marks the booking `completed`.
 * Doubles as a prompt to leave a review — the notification URL drops the
 * client on their bookings page where the "Leave Review" CTA appears.
 */
class BookingCompleted extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['event:id,title', 'supplier:id,name']);

        return [
            'type'          => 'booking_completed',
            'booking_id'    => $this->booking->id,
            'event_id'      => $this->booking->event_id,
            'event_title'   => $this->booking->event?->title,
            'supplier_id'   => $this->booking->supplier_id,
            'supplier_name' => $this->booking->supplier?->name,
            'message'       => ($this->booking->supplier?->name ?? 'Your professional')
                . ' marked "' . ($this->booking->event?->title ?? 'your event')
                . '" as completed. Leave a review?',
            'url'           => route('client.bookings.index', ['tab' => 'completed']),
        ];
    }
}
