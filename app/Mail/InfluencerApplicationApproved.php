<?php

namespace App\Mail;

use App\Models\Influencer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InfluencerApplicationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Influencer $influencer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're approved! Welcome to the affiliate program — " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.influencer.application-approved',
            with: [
                'influencer' => $this->influencer,
                'loginUrl'   => route('login.affiliate'),
            ],
        );
    }
}
