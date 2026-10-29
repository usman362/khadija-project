<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * The client's confirmation that somebody has been named.
 *
 * Khadijah's wording, 29 October. Her copy says the client can view the
 * headshot on their dashboard rather than embedding it, which is right for a
 * second reason she may not have meant: an image in an email is fetched from
 * wherever the reader opens it, and the headshot is behind a sign-in.
 */
class YourOnSiteContactIsSet extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'On-site contact updated for '
                . ($this->booking->event?->title ?? 'your event'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance.contact-set',
            with: [
                'booking'   => $this->booking,
                'event'     => $this->booking->event,
                'staff'     => $this->booking->pointOfContact,
                'pro'       => $this->booking->supplier,
                'firstName' => Str::of($this->booking->client?->name ?? '')->explode(' ')->first() ?: 'there',
                'url'       => route('client.bookings.index'),
            ],
        );
    }
}
