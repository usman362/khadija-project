<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rule R33 §6 — the lighter of the two notices.
 *
 * Sent when an EXISTING listing comes back: the free grace reopen and the
 * paid extension both use this one. The rule is explicit that a reactivation
 * must never repeat the full "New Event Available" blast — a client who pays
 * to extend three times would otherwise be paying to notify the same
 * professionals three more times, which is an advertising channel, not an
 * extension.
 */
class EventReopened extends Notification
{
    use Queueable;

    public function __construct(public Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'event_reopened',
            'event_id' => $this->event->id,
            'title'    => $this->event->title,
            'message'  => 'This event has reopened and is accepting proposals again.',
            'deadline' => $this->event->proposal_deadline?->toIso8601String(),
            'url'      => route('professional.bidding-board.index'),
        ];
    }
}
