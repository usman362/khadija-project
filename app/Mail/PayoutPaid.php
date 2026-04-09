<?php

namespace App\Mail;

use App\Models\InfluencerPayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutPaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InfluencerPayoutRequest $payoutRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Payout Has Been Processed — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payouts.paid',
            with: [
                'payout'     => $this->payoutRequest,
                'influencer' => $this->payoutRequest->influencer,
            ],
        );
    }
}
