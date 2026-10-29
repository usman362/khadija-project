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
 * The T-24 reminder to a professional who chose "decide later".
 *
 * Khadijah's wording, 29 October, kept as she wrote it. The one change is
 * that the event time is printed where the reader is rather than in UTC, and
 * that is not a change to the words.
 */
class AssignYourOnSiteContact extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Assign your on-site contact for '
                . ($this->booking->event?->title ?? 'your event'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance.assign-contact',
            with: [
                'booking'   => $this->booking,
                'event'     => $this->booking->event,
                // First name only, as her copy asks.
                'firstName' => Str::of($this->booking->supplier?->name ?? '')->explode(' ')->first() ?: 'there',
                'url'       => $this->booking->event_id
                    ? route('professional.gigs.show', $this->booking->event_id)
                    : route('professional.gigs.index'),
            ],
        );
    }
}
