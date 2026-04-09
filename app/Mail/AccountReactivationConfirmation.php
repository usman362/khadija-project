<?php

namespace App\Mail;

use App\Models\AccountReactivationPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountReactivationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AccountReactivationPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Reactivated Successfully — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.reactivation',
            with: [
                'payment' => $this->payment->loadMissing('user'),
                'user'    => $this->payment->user,
            ],
        );
    }
}
