<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * D-2 (Sir Peter, Sep 5): the standard message to every professional booked
 * on an event the client cancelled in full, once an administrator approves.
 * Their wording, with the tier that applies to this professional's booking.
 */
class EventCancelledByClient extends Notification
{
    use Queueable;

    public function __construct(public Event $event, public string $tier) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'event_cancelled_by_client',
            'event_id' => $this->event->id,
            'title'    => $this->event->title,
            'tier'     => $this->tier,
            'message'  => 'The client has cancelled their entire event, including your booking. '
                . 'The "' . $this->tier . '" refund policy applies. This was not caused by anything on your end.',
        ];
    }
}
