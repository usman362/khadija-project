<?php

namespace App\Domain\Requests;

use App\Domain\Settings\Services\SettingsService;
use App\Models\Event;
use App\Models\EventExtension;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Rule R33 — reopening an expired listing.
 *
 * Built on the same shape as AccountReactivationService, deliberately: that
 * is this codebase's existing one-off-fee flow, it already goes through
 * PaymentGuard (which refuses a live charge until PAYMENTS_GO_LIVE is set),
 * and a second, differently-shaped payment path is how one of them ends up
 * missing the guard.
 *
 * The rule this class exists to hold: nothing moves a deadline except a
 * COMPLETED extension. A pending or failed one leaves the listing expired,
 * which is exactly what §2 says a payment failure must do.
 */
final class EventExtensionService
{
    public function __construct(private SettingsService $settings) {}

    /* ── §2 The free grace reopen ── */

    /**
     * Put an expired listing back inside the 24-hour grace window.
     *
     * Free, once per event, and not counted toward the three-extension cap.
     * There is no payment step, so it completes immediately — but it is still
     * written as an extension row, because "has this event had its free one"
     * is a question only the history can answer.
     */
    public function graceReopen(Event $event, User $actor, \DateTimeInterface $newDeadline): EventExtension
    {
        if (! RequestLifecycle::inGracePeriod($event)) {
            throw new RuntimeException('This event is not inside its free reopen window.');
        }

        $this->assertDeadlineFits($event, $newDeadline);

        return DB::transaction(function () use ($event, $actor, $newDeadline) {
            $extension = EventExtension::create([
                'event_id'          => $event->id,
                'user_id'           => $actor->id,
                'days'              => 0,
                'is_grace'          => true,
                'amount'            => 0,
                'currency'          => $this->currency(),
                'status'            => EventExtension::STATUS_COMPLETED,
                'previous_deadline' => $event->proposal_deadline,
                'new_deadline'      => $newDeadline,
                'completed_at'      => now(),
            ]);

            $this->reopen($event, $newDeadline);

            return $extension;
        });
    }

    /* ── §2 The paid extension ── */

    /**
     * Start a paid extension. Returns the gateway redirect and the record.
     *
     * The deadline is NOT moved here. It moves in complete(), when the
     * processor says the money arrived.
     *
     * @return array{redirect_url: string, extension: EventExtension}
     */
    public function initiate(Event $event, User $actor, int $days, string $gateway): array
    {
        if (! in_array($gateway, ['stripe', 'paypal'], true)) {
            throw new RuntimeException("Unsupported payment gateway: {$gateway}");
        }

        $option = collect(RequestLifecycle::extensionOptions($event))
            ->firstWhere('days', $days);

        if ($option === null) {
            // Covers all four refusals — not expired, an ESR, the cap is
            // reached, or this tier would land past the event date — because
            // extensionOptions() is the one place that decides.
            throw new RuntimeException('That extension is not available for this event.');
        }

        $extension = EventExtension::create([
            'event_id'          => $event->id,
            'user_id'           => $actor->id,
            'days'              => $days,
            'is_grace'          => false,
            'amount'            => $option['price'],
            'currency'          => $this->currency(),
            'gateway'           => $gateway,
            'status'            => EventExtension::STATUS_PENDING,
            'previous_deadline' => $event->proposal_deadline,
            'new_deadline'      => $option['new_deadline'],
        ]);

        $session = $gateway === 'stripe'
            ? $this->createStripeSession($event, $actor, $extension)
            : $this->createPayPalOrder($event, $actor, $extension);

        $extension->update([
            'gateway_session_id' => $session['session_id'],
            'status'             => EventExtension::STATUS_PROCESSING,
        ]);

        return ['redirect_url' => $session['redirect_url'], 'extension' => $extension];
    }

    /**
     * The money arrived — move the deadline. Idempotent.
     *
     * The new deadline is recomputed rather than trusted from the row: the
     * client may have sat on the checkout page for an hour, and §2 measures
     * a paid extension from the end of the grace period, not from whenever
     * the row happened to be written.
     */
    public function complete(EventExtension $extension, ?string $gatewayPaymentId = null): void
    {
        if ($extension->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($extension, $gatewayPaymentId) {
            $event = $extension->event;

            $newDeadline = RequestLifecycle::extendFrom($event)->addDays($extension->days);

            // §2's hard ceiling still applies at the moment of payment. If
            // the client moved the event date closer while the checkout page
            // was open, the deadline lands on the event date rather than past
            // it — the extension is honoured, the ceiling is not broken.
            if ($event->starts_at !== null && $newDeadline->greaterThan($event->starts_at)) {
                $newDeadline = $event->starts_at->copy();
            }

            $extension->update([
                'status'             => EventExtension::STATUS_COMPLETED,
                'gateway_payment_id' => $gatewayPaymentId,
                'new_deadline'       => $newDeadline,
                'completed_at'       => now(),
            ]);

            $this->reopen($event, $newDeadline);

            Log::info('Event extension completed', [
                'event_id' => $event->id, 'extension_id' => $extension->id, 'days' => $extension->days,
            ]);
        });
    }

    /** §2 — a failed payment grants nothing and leaves the listing expired. */
    public function fail(EventExtension $extension, ?string $reason = null): void
    {
        if ($extension->isCompleted()) {
            return;
        }

        $extension->update([
            'status'         => EventExtension::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /* ── §1 Close ── */

    /**
     * The client closes the listing themselves.
     *
     * Recorded rather than derived: closing is a decision, and "the client
     * closed this" and "the deadline went past" are different answers to
     * give a professional who asks what happened to a request they bid on.
     */
    public function close(Event $event, User $actor): void
    {
        $event->forceFill(['closed_at' => now()])->save();

        Log::info('Event closed by client', ['event_id' => $event->id, 'user_id' => $actor->id]);
    }

    /* ── Shared ── */

    /**
     * Put the listing back on the board.
     *
     * `reopened_at` is what §2's ranking reads and what §6's notice type
     * turns on — a reactivated listing gets the lighter "Event Reopened"
     * notice and sits below the same day's new listings, never at the top.
     * Paying repeatedly must not buy permanent first place.
     */
    private function reopen(Event $event, \DateTimeInterface $newDeadline): void
    {
        $event->forceFill([
            'proposal_deadline' => $newDeadline,
            'reopened_at'       => now(),
            'closed_at'         => null,
        ])->save();

        EventNotifier::reopened($event);
    }

    /** §2's hard ceiling, for the free reopen where the client picks the date. */
    private function assertDeadlineFits(Event $event, \DateTimeInterface $newDeadline): void
    {
        if ($newDeadline <= now()) {
            throw new RuntimeException('The new deadline has to be in the future.');
        }

        if ($event->starts_at !== null && $newDeadline > $event->starts_at) {
            throw new RuntimeException('A proposal deadline cannot fall after the event itself. Move the event date first.');
        }
    }

    private function currency(): string
    {
        return strtoupper((string) $this->settings->get('payment.currency', 'USD'));
    }

    private function createStripeSession(Event $event, User $user, EventExtension $extension): array
    {
        $secretKey = $this->settings->get('payment.stripe_secret_key');

        if (empty($secretKey)) {
            throw new RuntimeException('Stripe is not configured. Please contact support.');
        }

        \App\Domain\Payments\PaymentGuard::assertLiveChargeAllowed(
            $this->settings->get('payment.mode', 'test'),
            $secretKey,
        );

        $stripe = new \Stripe\StripeClient($secretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($extension->currency),
                    'product_data' => [
                        'name'        => "Extend your request by {$extension->days} days",
                        'description' => $event->title,
                    ],
                    'unit_amount' => (int) round($extension->amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'customer_email' => $user->email,
            'success_url'    => route('client.events.extension.success', $event) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => route('client.events.extension.cancel', $event)  . '?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => [
                'user_id'      => $user->id,
                'event_id'     => $event->id,
                'extension_id' => $extension->id,
                'purpose'      => 'event_extension',
            ],
        ]);

        return ['redirect_url' => $session->url, 'session_id' => $session->id];
    }

    private function createPayPalOrder(Event $event, User $user, EventExtension $extension): array
    {
        $clientId = $this->settings->get('payment.paypal_client_id');
        $secret   = $this->settings->get('payment.paypal_secret');
        $mode     = $this->settings->get('payment.mode', 'test');

        \App\Domain\Payments\PaymentGuard::assertLiveChargeAllowed($mode);

        if (empty($clientId) || empty($secret)) {
            throw new RuntimeException('PayPal is not configured. Please contact support.');
        }

        $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $token = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $order = \Illuminate\Support\Facades\Http::withToken($token)
            ->post("{$baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'event-extension-' . $extension->id,
                    'description'  => "Extend {$event->title} by {$extension->days} days",
                    'amount' => [
                        'currency_code' => $extension->currency,
                        'value'         => number_format((float) $extension->amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('client.events.extension.success', $event),
                    'cancel_url' => route('client.events.extension.cancel', $event),
                ],
            ])
            ->throw()
            ->json();

        $approve = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if ($approve === null) {
            throw new RuntimeException('PayPal did not return an approval link.');
        }

        return ['redirect_url' => $approve, 'session_id' => $order['id']];
    }
}
