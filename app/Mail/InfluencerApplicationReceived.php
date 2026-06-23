<?php

namespace App\Mail;

use App\Models\Influencer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InfluencerApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Influencer $influencer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your affiliate application — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.influencer.application-received',
            with: ['influencer' => $this->influencer],
        );
    }
}
