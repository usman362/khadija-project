<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Fired at the professional when the client accepts (confirms) their proposal.
 * This is the moment work is officially on — the pro now has a gig.
 */
class ProposalAccepted extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['event:id,title', 'client:id,name']);

        return [
            'type'        => 'proposal_accepted',
            'booking_id'  => $this->booking->id,
            'event_id'    => $this->booking->event_id,
            'event_title' => $this->booking->event?->title,
            'client_id'   => $this->booking->client_id,
            'client_name' => $this->booking->client?->name,
            'message'     => ($this->booking->client?->name ?? 'The client')
                . ' accepted your proposal for "' . ($this->booking->event?->title ?? 'the event') . '".',
            'url'         => route('professional.proposals.index', ['tab' => 'accepted']),
        ];
    }
}
