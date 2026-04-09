<?php

namespace App\Mail;

use App\Models\InfluencerPayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InfluencerPayoutRequest $payoutRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payout Request Received — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payouts.requested',
            with: [
                'payout'     => $this->payoutRequest,
                'influencer' => $this->payoutRequest->influencer,
            ],
        );
    }
}
