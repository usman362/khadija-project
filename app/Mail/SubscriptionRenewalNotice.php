<?php

namespace App\Mail;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your membership renews on …"
 *
 * Washington DC, Automatic Renewal Protections Act of 2018: the member is told
 * before the first renewal and before every renewal after it.
 *
 * This is a legal notice, not marketing. It says the date, the amount and how
 * to stop it, and it goes whatever a member has chosen about other email —
 * because the obligation is to inform, and an unsubscribe link on it would be
 * offering to break the law on request.
 */
class SubscriptionRenewalNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserSubscription $subscription,
        public int $daysBefore,
    ) {}

    public function envelope(): Envelope
    {
        $on = $this->subscription->expires_at?->format('F j, Y');

        return new Envelope(
            subject: 'Your GigResource membership renews on ' . $on,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscriptions.renewal-notice',
            with: [
                'subscription' => $this->subscription,
                'daysBefore'   => $this->daysBefore,
                'plan'         => $this->subscription->plan,
                'renewsOn'     => $this->subscription->expires_at,
                'cancelUrl'    => route('app.membership-plans.index'),
            ],
        );
    }
}
