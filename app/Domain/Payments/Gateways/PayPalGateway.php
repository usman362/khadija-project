<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Services\PaymentService;
use App\Domain\Settings\Services\SettingsService;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    public function getName(): string
    {
        return 'paypal';
    }

    /**
     * Create a PayPal Order and return the approval URL.
     */
    public function createSession(User $user, UserSubscription $subscription, MembershipPlan $plan): array
    {
        $accessToken = $this->getAccessToken();
        $currency = $this->settings->get('payment.currency', 'USD');

        $response = Http::withToken($accessToken)
            ->post($this->getBaseUrl() . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => "sub_{$subscription->id}",
                    'description' => "Subscription to {$plan->name}",
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($plan->price, 2, '.', ''),
                    ],
                    'custom_id' => json_encode([
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'plan_id' => $plan->id,
                    ]),
                ]],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => route('app.payments.success') . '?gateway=paypal',
                    'cancel_url' => route('app.payments.cancel') . '?gateway=paypal',
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        if (! $response->successful()) {
            Log::error('PayPal order creation failed', ['response' => $response->json()]);
            throw new \RuntimeException('Failed to create PayPal order. Please try again.');
        }

        $order = $response->json();
        $approvalUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approvalUrl) {
            throw new \RuntimeException('PayPal did not return an approval URL.');
        }

        return [
            'redirect_url' => $approvalUrl,
            'session_id' => $order['id'],
        ];
    }

    /**
     * Verify PayPal webhook signature.
     */
    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        $webhookId = $this->settings->get('payment.paypal_webhook_id');

        if (empty($webhookId)) {
            Log::warning('PayPal webhook ID not configured');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post($this->getBaseUrl() . '/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $headers['PAYPAL-AUTH-ALGO'][0] ?? $headers['paypal-auth-algo'][0] ?? '',
                    'cert_url' => $headers['PAYPAL-CERT-URL'][0] ?? $headers['paypal-cert-url'][0] ?? '',
                    'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'][0] ?? $headers['paypal-transmission-id'][0] ?? '',
                    'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'][0] ?? $headers['paypal-transmission-sig'][0] ?? '',
                    'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'][0] ?? $headers['paypal-transmission-time'][0] ?? '',
                    'webhook_id' => $webhookId,
                    'webhook_event' => json_decode($rawBody, true),
                ]);

            $result = $response->json();

            return ($result['verification_status'] ?? '') === 'SUCCESS';
        } catch (\Exception $e) {
            Log::warning('PayPal webhook verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process PayPal webhook event.
     */
    public function processWebhook(array $payload): void
    {
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        Log::info('Processing PayPal webhook', ['type' => $eventType]);

        match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($resource),
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($resource),
            default => Log::info("Unhandled PayPal event: {$eventType}"),
        };
    }

    private function handleOrderApproved(array $resource): void
    {
        $orderId = $resource['id'] ?? null;

        if (! $orderId) {
            return;
        }

        // Capture the order
        try {
            $accessToken = $this->getAccessToken();
            $response = Http::withToken($accessToken)
                ->post($this->getBaseUrl() . "/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $captureData = $response->json();
                $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

                $payment = Payment::where('gateway_session_id', $orderId)->first();
                if ($payment && ! $payment->isCompleted()) {
                    $paymentService = app(PaymentService::class);
                    $paymentService->completePayment($payment, $captureId, 'paypal');
                }
            }
        } catch (\Exception $e) {
            Log::error('PayPal capture failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    private function handleCaptureCompleted(array $resource): void
    {
        $captureId = $resource['id'] ?? null;
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $payment = Payment::where('gateway_session_id', $orderId)->first();

        if (! $payment || $payment->isCompleted()) {
            return;
        }

        $paymentService = app(PaymentService::class);
        $paymentService->completePayment($payment, $captureId, 'paypal');
    }

    // ── Helpers ────────────────────────────────────────────

    private function getAccessToken(): string
    {
        $clientId = $this->settings->get('payment.paypal_client_id');
        $secret = $this->settings->get('payment.paypal_secret');

        if (empty($clientId) || empty($secret)) {
            throw new \RuntimeException('PayPal credentials are not configured. Please set them in Payment Settings.');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->getBaseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to authenticate with PayPal.');
        }

        return $response->json('access_token');
    }

    private function getBaseUrl(): string
    {
        $mode = $this->settings->get('payment.mode', 'test');

        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
