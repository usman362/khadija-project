<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmation — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.confirmation',
            with: [
                'payment'      => $this->payment->loadMissing(['user', 'subscription.plan']),
                'user'         => $this->payment->user,
                'subscription' => $this->payment->subscription,
                'plan'         => $this->payment->subscription?->plan,
            ],
        );
    }
}
