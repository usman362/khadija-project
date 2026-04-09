<?php

namespace App\Domain\Payments\Services;

use App\Domain\Settings\Services\SettingsService;
use App\Mail\AccountReactivationConfirmation;
use App\Models\AccountReactivationPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class AccountReactivationService
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    // ── Settings helpers ────────────────────────────────────

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('account_reactivation.enabled', true);
    }

    public function getFee(): float
    {
        return (float) $this->settings->get('account_reactivation.fee', 4.99);
    }

    public function getCurrency(): string
    {
        return strtoupper((string) $this->settings->get('account_reactivation.currency',
            $this->settings->get('payment.currency', 'USD')
        ));
    }

    // ── Payment initiation ──────────────────────────────────

    /**
     * Create a pending reactivation payment and a gateway checkout session.
     * Returns ['redirect_url' => ..., 'payment' => AccountReactivationPayment].
     */
    public function initiate(User $user, string $gateway): array
    {
        if (!in_array($gateway, ['stripe', 'paypal'], true)) {
            throw new RuntimeException("Unsupported payment gateway: {$gateway}");
        }

        if (!$user->hasPendingDeletion()) {
            throw new RuntimeException('This account has no pending deletion to reactivate.');
        }

        $amount   = $this->getFee();
        $currency = $this->getCurrency();

        $payment = AccountReactivationPayment::create([
            'user_id'  => $user->id,
            'amount'   => $amount,
            'currency' => $currency,
            'gateway'  => $gateway,
            'status'   => AccountReactivationPayment::STATUS_PENDING,
            'metadata' => [
                'deletion_requested_at' => $user->deletion_requested_at?->toIso8601String(),
                'deletion_scheduled_at' => $user->deletion_scheduled_at?->toIso8601String(),
            ],
        ]);

        $session = $gateway === 'stripe'
            ? $this->createStripeSession($user, $payment)
            : $this->createPayPalOrder($user, $payment);

        $payment->update([
            'gateway_session_id' => $session['session_id'],
            'status'             => AccountReactivationPayment::STATUS_PROCESSING,
        ]);

        return [
            'redirect_url' => $session['redirect_url'],
            'payment'      => $payment,
        ];
    }

    /**
     * Mark payment as completed and restore the user's account.
     * Idempotent — safe to call multiple times.
     */
    public function complete(AccountReactivationPayment $payment, ?string $gatewayPaymentId = null): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($payment, $gatewayPaymentId) {
            $payment->update([
                'status'             => AccountReactivationPayment::STATUS_COMPLETED,
                'gateway_payment_id' => $gatewayPaymentId,
                'completed_at'       => now(),
            ]);

            // Restore the user account
            $user = $payment->user;
            if ($user) {
                $user->update([
                    'deletion_requested_at' => null,
                    'deletion_scheduled_at' => null,
                    'deletion_reason'       => null,
                ]);

                Log::info('Account reactivated after payment', [
                    'user_id'    => $user->id,
                    'payment_id' => $payment->id,
                    'amount'     => $payment->amount,
                    'gateway'    => $payment->gateway,
                ]);
            }
        });

        // Confirmation email (non-blocking)
        $email = $payment->user?->email;
        if ($email) {
            try {
                Mail::to($email)->send(new AccountReactivationConfirmation($payment->fresh()));
            } catch (Throwable $e) {
                Log::warning('Failed to send reactivation confirmation email', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    public function fail(AccountReactivationPayment $payment, string $reason = ''): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        $payment->update([
            'status'         => AccountReactivationPayment::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);

        Log::warning('Account reactivation payment failed', [
            'payment_id' => $payment->id,
            'user_id'    => $payment->user_id,
            'reason'     => $reason,
        ]);
    }

    public function cancel(AccountReactivationPayment $payment): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        $payment->update([
            'status' => AccountReactivationPayment::STATUS_CANCELLED,
        ]);
    }

    public function findBySessionId(string $sessionId): ?AccountReactivationPayment
    {
        return AccountReactivationPayment::where('gateway_session_id', $sessionId)->first();
    }

    // ── Gateway-specific session creation ──────────────────

    private function createStripeSession(User $user, AccountReactivationPayment $payment): array
    {
        $secretKey = $this->settings->get('payment.stripe_secret_key');

        if (empty($secretKey)) {
            throw new RuntimeException('Stripe is not configured. Please contact support.');
        }

        $stripe = new \Stripe\StripeClient($secretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => [
                        'name'        => 'Account Reactivation',
                        'description' => 'Reactivate your ' . config('app.name') . ' account and cancel scheduled deletion.',
                    ],
                    'unit_amount' => (int) round($payment->amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'customer_email' => $user->email,
            'success_url'    => route('account.reactivation.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => route('account.reactivation.cancel')  . '?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => [
                'user_id'                  => $user->id,
                'reactivation_payment_id'  => $payment->id,
                'purpose'                  => 'account_reactivation',
            ],
        ]);

        return [
            'redirect_url' => $session->url,
            'session_id'   => $session->id,
        ];
    }

    private function createPayPalOrder(User $user, AccountReactivationPayment $payment): array
    {
        $clientId = $this->settings->get('payment.paypal_client_id');
        $secret   = $this->settings->get('payment.paypal_secret');
        $mode     = $this->settings->get('payment.mode', 'test');

        if (empty($clientId) || empty($secret)) {
            throw new RuntimeException('PayPal is not configured. Please contact support.');
        }

        $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        // Get access token
        $tokenResp = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (!$tokenResp->successful()) {
            throw new RuntimeException('Failed to authenticate with PayPal.');
        }

        $accessToken = $tokenResp->json('access_token');

        // Create order
        $orderResp = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'reactivation_' . $payment->id,
                    'description'  => 'Account Reactivation Fee',
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value'         => number_format($payment->amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name'  => config('app.name'),
                    'user_action' => 'PAY_NOW',
                    'return_url'  => route('account.reactivation.success') . '?gateway=paypal&reactivation_payment_id=' . $payment->id,
                    'cancel_url'  => route('account.reactivation.cancel')  . '?gateway=paypal&reactivation_payment_id=' . $payment->id,
                ],
            ]);

        if (!$orderResp->successful()) {
            Log::error('PayPal order creation failed', ['response' => $orderResp->json()]);
            throw new RuntimeException('Failed to create PayPal order. Please try again.');
        }

        $order       = $orderResp->json();
        $orderId     = $order['id'];
        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (!$approveLink) {
            throw new RuntimeException('PayPal did not return an approval URL.');
        }

        return [
            'redirect_url' => $approveLink,
            'session_id'   => $orderId,
        ];
    }

    /**
     * Capture a PayPal order after the user approves it.
     * Called from the success callback (no webhook round-trip required).
     */
    public function capturePayPalOrder(AccountReactivationPayment $payment): bool
    {
        if ($payment->gateway !== 'paypal' || !$payment->gateway_session_id) {
            return false;
        }

        $clientId = $this->settings->get('payment.paypal_client_id');
        $secret   = $this->settings->get('payment.paypal_secret');
        $mode     = $this->settings->get('payment.mode', 'test');

        $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $tokenResp = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (!$tokenResp->successful()) {
            $this->fail($payment, 'PayPal auth failed during capture');
            return false;
        }

        $accessToken = $tokenResp->json('access_token');

        $captureResp = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/checkout/orders/{$payment->gateway_session_id}/capture");

        if (!$captureResp->successful()) {
            $this->fail($payment, 'PayPal capture failed');
            return false;
        }

        $captureData = $captureResp->json();
        $captureId   = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        $this->complete($payment, $captureId);
        return true;
    }
}
