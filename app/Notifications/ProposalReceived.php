<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Fired at the client when a professional submits a proposal on their event.
 * Database-only — appears in the client's in-app notification feed.
 */
class ProposalReceived extends Notification
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
            'type'          => 'proposal_received',
            'booking_id'    => $this->booking->id,
            'event_id'      => $this->booking->event_id,
            'event_title'   => $this->booking->event?->title,
            'supplier_id'   => $this->booking->supplier_id,
            'supplier_name' => $this->booking->supplier?->name,
            'message'       => ($this->booking->supplier?->name ?? 'A professional')
                . ' sent a proposal for "' . ($this->booking->event?->title ?? 'your event') . '".',
            'url'           => route('client.bookings.index', ['tab' => 'pending']),
        ];
    }
}
