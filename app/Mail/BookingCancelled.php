<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $recipient,
        public ?User $cancelledBy,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Cancelled — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.cancelled',
            with: [
                'booking'     => $this->booking->loadMissing(['event', 'client', 'supplier']),
                'recipient'   => $this->recipient,
                'cancelledBy' => $this->cancelledBy,
                'reason'      => $this->reason,
            ],
        );
    }
}
