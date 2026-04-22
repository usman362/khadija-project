<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Fired at the OTHER participant when one side cancels. Whoever pulled the
 * trigger is recorded in $actor so the message reads correctly from the
 * recipient's point of view ("Client Jane cancelled" vs "Supplier Bob cancelled").
 */
class ProposalCancelled extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public User $actor) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['event:id,title']);

        $actorRole = $this->actor->id === $this->booking->client_id ? 'client' : 'professional';
        $actorName = $this->actor->name;
        $title     = $this->booking->event?->title ?? 'the event';

        return [
            'type'        => 'proposal_cancelled',
            'booking_id'  => $this->booking->id,
            'event_id'    => $this->booking->event_id,
            'event_title' => $this->booking->event?->title,
            'actor_id'    => $this->actor->id,
            'actor_name'  => $actorName,
            'actor_role'  => $actorRole,
            'message'     => "The {$actorRole} ({$actorName}) cancelled the booking for \"{$title}\".",
            'url'         => $notifiable->id === $this->booking->client_id
                ? route('client.bookings.index', ['tab' => 'cancelled'])
                : route('professional.proposals.index', ['tab' => 'cancelled']),
        ];
    }
}
