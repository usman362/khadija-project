<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Services\PaymentService;
use App\Domain\Settings\Services\SettingsService;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * Create a Stripe Checkout Session.
     */
    public function createSession(User $user, UserSubscription $subscription, MembershipPlan $plan): array
    {
        $secretKey = $this->settings->get('payment.stripe_secret_key');
        $currency = strtolower($this->settings->get('payment.currency', 'USD'));

        if (empty($secretKey)) {
            throw new \RuntimeException('Stripe secret key is not configured. Please set it in Payment Settings.');
        }

        $stripe = new \Stripe\StripeClient($secretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        // Show the contract term on the Stripe checkout line so the
                        // client sees "Professional — 12-month contract" rather than
                        // a recurring-looking label.
                        'name' => $plan->name . ' — ' . $plan->contractTermLabel(),
                        'description' => $plan->description ?? "Access to {$plan->name} for {$plan->contractTermLabel()}",
                    ],
                    'unit_amount' => (int) round($plan->price * 100), // Stripe uses cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'customer_email' => $user->email,
            'success_url' => route('app.payments.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('app.payments.cancel') . '?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return [
            'redirect_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    /**
     * Verify Stripe webhook signature.
     */
    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        $webhookSecret = $this->settings->get('payment.stripe_webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }

        $sigHeader = $headers['stripe-signature'][0] ?? $headers['Stripe-Signature'][0] ?? '';

        try {
            \Stripe\Webhook::constructEvent($rawBody, $sigHeader, $webhookSecret);
            return true;
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process Stripe webhook event.
     */
    public function processWebhook(array $payload): void
    {
        $type = $payload['type'] ?? '';
        $data = $payload['data']['object'] ?? [];

        Log::info('Processing Stripe webhook', ['type' => $type]);

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($data),
            'checkout.session.expired' => $this->handleCheckoutExpired($data),
            default => Log::info("Unhandled Stripe event: {$type}"),
        };
    }

    private function handleCheckoutCompleted(array $session): void
    {
        $sessionId = $session['id'] ?? null;

        if (! $sessionId) {
            return;
        }

        // Branch: is this a membership payment or an account reactivation payment?
        $purpose = $session['metadata']['purpose'] ?? null;

        if ($purpose === 'account_reactivation') {
            $this->handleReactivationCompleted($session);
            return;
        }

        $payment = Payment::where('gateway_session_id', $sessionId)->first();

        if (! $payment || $payment->isCompleted()) {
            return;
        }

        $paymentIntentId = $session['payment_intent'] ?? null;
        $paymentMethod = $session['payment_method_types'][0] ?? 'card';

        $paymentService = app(PaymentService::class);
        $paymentService->completePayment($payment, $paymentIntentId, $paymentMethod);

        Log::info('Stripe payment completed', [
            'payment_id' => $payment->id,
            'session_id' => $sessionId,
        ]);
    }

    private function handleReactivationCompleted(array $session): void
    {
        $sessionId = $session['id'] ?? null;
        $payment   = \App\Models\AccountReactivationPayment::where('gateway_session_id', $sessionId)->first();

        if (! $payment || $payment->isCompleted()) {
            return;
        }

        $paymentIntentId = $session['payment_intent'] ?? null;

        app(\App\Domain\Payments\Services\AccountReactivationService::class)
            ->complete($payment, $paymentIntentId);

        Log::info('Account reactivation payment completed via Stripe webhook', [
            'payment_id' => $payment->id,
            'user_id'    => $payment->user_id,
        ]);
    }

    private function handleCheckoutExpired(array $session): void
    {
        $sessionId = $session['id'] ?? null;

        if (! $sessionId) {
            return;
        }

        $payment = Payment::where('gateway_session_id', $sessionId)->first();

        if (! $payment || ! $payment->isPending() && ! $payment->isProcessing()) {
            return;
        }

        $paymentService = app(PaymentService::class);
        $paymentService->failPayment($payment, 'Checkout session expired');
    }
}
