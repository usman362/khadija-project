<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Your request went out, and here is what it cost.
 *
 * Sir Peter, 18 October: notifications must cover "every notification from
 * whatever the clients do themselves from BR, DR, ER, buying a package".
 *
 * Publishing told the professionals and told the client nothing but a flash
 * message, which is gone the moment they navigate. There was no record they
 * could come back to of what went out, when, or what they were charged for it,
 * and since the posting fee moved to publish there is now a charge to account
 * for at exactly that moment.
 *
 * So the client gets the receipt. This is the one case where telling somebody
 * what they themselves just did is not noise: it is the only record of a fee.
 */
class YourRequestIsLive extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public ?float $fee = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // A charge is a booking matter rather than event news.
        if (method_exists($notifiable, 'acceptsEmail') && $notifiable->acceptsEmail('bookings')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'request_published',
            'event_id' => $this->event->id,
            'title'    => $this->event->title,
            'fee'      => $this->fee,
            'message'  => $this->sentence(),
            'url'      => route('client.events.show', $this->event),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your request is live: ' . ($this->event->title ?? 'your event'))
            ->view('emails.requests.published', [
                'user' => $notifiable,
                'data' => $this->toArray($notifiable),
            ]);
    }

    public function sentence(): string
    {
        $what = $this->event->title ?? 'Your request';

        if ($this->fee === null || $this->fee <= 0) {
            return "{$what} is live. Professionals in your state can see it and send proposals.";
        }

        return "{$what} is live, and the \${$this->money()} posting fee applies. "
            . 'Professionals in your state can see it and send proposals.';
    }

    private function money(): string
    {
        return number_format((float) $this->fee, 2);
    }
}
