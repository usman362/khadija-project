<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fired at the client when a professional submits a proposal on their event.
 * In-app, plus email when the client's notify_email_events says so.
 */
class ProposalReceived extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * In-app always; email only if this account still wants it.
     * A proposal landing on your event is news about that event.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'acceptsEmail') && $notifiable->acceptsEmail('events')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject('You have a new proposal')
            ->view('emails.lifecycle.proposal-received', [
                'user' => $notifiable,
                'data' => $data,
            ]);
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
