<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * What you bought, from whom, and what it costs.
 *
 * Sir Peter, 18 October: notifications must cover "every notification from
 * whatever the clients do themselves from BR, DR, ER, buying a package".
 *
 * Buying a package notified the professional and left the client with a
 * confirmation page. Navigate away and there was no record of what was bought,
 * from whom, or what was charged for it.
 *
 * It says "requested" rather than "booked" on purpose. A package purchase opens
 * a booking in the requested state and the professional still has to accept it,
 * so telling the client it is confirmed would be the platform promising
 * something the professional has not agreed to.
 */
class YouBoughtAPackage extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public Package $package,
        public ?float $agreementFee = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'acceptsEmail') && $notifiable->acceptsEmail('bookings')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'package_purchased',
            'booking_id' => $this->booking->id,
            'package_id' => $this->package->id,
            'title'      => $this->package->title,
            'supplier'   => $this->booking->supplier?->name,
            'price'      => (float) $this->booking->price,
            'fee'        => $this->agreementFee,
            'message'    => $this->sentence(),
            'url'        => route('client.packages.booked', $this->booking),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your package request: ' . $this->package->title)
            ->view('emails.packages.purchased', [
                'user' => $notifiable,
                'data' => $this->toArray($notifiable),
            ]);
    }

    public function sentence(): string
    {
        $who = $this->booking->supplier?->name ?? 'the professional';

        return "You requested {$this->package->title} from {$who}. "
            . 'It is not confirmed until they accept it.';
    }
}
