<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rule R33 §6 — the first-publication notice, and only that.
 *
 * There are exactly two notices in this rule and they must not be conflated.
 * This one fires once, when a listing is first published. Every later
 * reactivation — free grace reopen or paid extension — uses EventReopened.
 */
class NewEventAvailable extends Notification
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
            'type'     => 'new_event_available',
            'event_id' => $this->event->id,
            'title'    => $this->event->title,
            'message'  => 'A new event is open for proposals.',
            'deadline' => $this->event->proposal_deadline?->toIso8601String(),
            'url'      => route('professional.bidding-board.index'),
        ];
    }
}
