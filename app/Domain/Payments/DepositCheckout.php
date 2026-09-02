<?php

namespace App\Domain\Payments;

use App\Models\Finalization;
use App\Domain\Settings\Services\SettingsService;

/**
 * Send the client to Stripe's own page to pay a booking deposit.
 *
 * Ali, 2026-09-02: "payment details bhi daalein card etc to payment process
 * work kare, just for testing purpose."
 *
 * The card fields are Stripe's, not ours, and that is the point rather than a
 * shortcut. A card number typed into a form on this server is our problem the
 * moment it arrives — PCI scope, logs, error reports, database backups — and
 * a field added "just for testing" is the field that is still there at launch.
 * Stripe Checkout collects it on their domain and hands us back a session id.
 *
 * Nothing here can move real money. PaymentGuard already refuses a live mode
 * or a live secret key before launch, so with TEST keys the client gets a
 * genuine card form, types 4242 4242 4242 4242, and comes back to a completed
 * booking having charged nobody.
 *
 * Availability is decided by one question — are Stripe keys configured? With
 * none, the existing test-mode behaviour is untouched: the deposit is recorded
 * as a test payment and the booking completes, exactly as it does today. That
 * is deliberate. This is a money path days before a demo, and a change that
 * can only add a route is a change that cannot break the one being demonstrated.
 */
class DepositCheckout
{
    /**
     * Is a real (test-mode) card journey available?
     *
     * Asked of the settings rather than config, because that is where the
     * admin Payment Settings screen writes them.
     */
    public static function isConfigured(): bool
    {
        return trim((string) self::secretKey()) !== '';
    }

    public static function secretKey(): ?string
    {
        return app(SettingsService::class)->get('payment.stripe_secret_key');
    }

    /**
     * Create the Stripe Checkout session and return where to send the client.
     *
     * Two line items, never one. The deposit belongs to the professional and
     * the $2.99 request fee belongs to GigResource; folding them together
     * would overstate every deposit by the fee, and the Bookings page reads
     * deposits to work out what a client still owes.
     *
     * @return string the URL to redirect to
     */
    public static function begin(Finalization $f, string $successUrl, string $cancelUrl): string
    {
        $secret = self::secretKey();

        if (! $secret) {
            throw new \RuntimeException('Stripe is not configured; the caller should not have reached here.');
        }

        // The same choke point the rest of the payment code uses. Pre-launch
        // this refuses a live mode or an sk_live_ key outright.
        PaymentGuard::assertLiveChargeAllowed(
            (string) (app(SettingsService::class)->get('payment.mode', 'test')),
            $secret,
        );

        $items = [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Booking deposit — '.$f->event->title,
                    'description' => 'Secures '.$f->supplier->name.' for this booking.',
                ],
                'unit_amount' => (int) round(((float) $f->deposit_amount) * 100),
            ],
            'quantity' => 1,
        ]];

        $fee = (float) config('payments.client_request_fee', 0);

        if ($fee > 0) {
            $items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'GigResource request fee',
                        'description' => 'Charged once, when a request finalizes.',
                    ],
                    'unit_amount' => (int) round($fee * 100),
                ],
                'quantity' => 1,
            ];
        }

        $session = (new \Stripe\StripeClient($secret))->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $items,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            // Carried so the return leg knows which finalization was paid
            // without trusting anything the browser sends back.
            'metadata' => [
                'finalization_id' => (string) $f->id,
                'client_id' => (string) $f->client_id,
            ],
        ]);

        return $session->url;
    }

    /**
     * Confirm a returning session actually paid, and for this finalization.
     *
     * The success URL is a plain address the client's browser lands on, so it
     * proves nothing on its own — somebody could simply open it. What settles
     * it is asking Stripe about the session and checking both that it is paid
     * and that its metadata names this finalization.
     */
    public static function confirm(Finalization $f, string $sessionId): bool
    {
        $secret = self::secretKey();

        if (! $secret) {
            return false;
        }

        try {
            $session = (new \Stripe\StripeClient($secret))->checkout->sessions->retrieve($sessionId);
        } catch (\Throwable) {
            return false;
        }

        return ($session->payment_status ?? null) === 'paid'
            && (int) ($session->metadata['finalization_id'] ?? 0) === $f->id;
    }
}
