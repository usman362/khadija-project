<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\StaffMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The person the client will meet on the day has changed.
 *
 * Sir Peter, 28 October: after the agreement is done the professional may
 * swap in a different staff member, and the client should be told unless it
 * was already said during the agreement. App\Domain\PointOfContact decides
 * whether that applies; this only carries the news.
 *
 * It says who is coming and how to reach them, and it says the thing a client
 * reading it most wants to know: the professional is still the one who owes
 * them the work. Without that line the notice reads as being handed off to
 * somebody else, which is exactly what has not happened.
 */
class SomebodyElseIsComing extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public ?StaffMember $staff,
        public string $professional,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'point_of_contact_changed',
            'booking_id' => $this->booking->id,
            'event_id'   => $this->booking->event_id,
            'title'      => $this->booking->event?->title,
            'staff'      => $this->staff?->name,
            'role'       => $this->staff?->role,
            'phone'      => $this->staff?->phone,
            'photo'      => $this->staff?->photoUrl(),
            'message'    => $this->sentence(),
            'url'        => route('client.bookings.index'),
        ];
    }

    public function sentence(): string
    {
        $event = $this->booking->event?->title ?? 'your event';

        if (! $this->staff) {
            return $this->professional . ' will be at ' . $event . ' themselves.';
        }

        $who = $this->staff->name
            . ($this->staff->role ? ' (' . $this->staff->role . ')' : '');

        return $who . ' will be your point of contact at ' . $event . '. '
            . $this->professional . ' is still responsible for the work.';
    }
}
