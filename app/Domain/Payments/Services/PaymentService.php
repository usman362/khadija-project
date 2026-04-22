<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Gateways\PayPalGateway;
use App\Domain\Payments\Gateways\StripeGateway;
use App\Domain\Settings\Services\SettingsService;
use App\Mail\PaymentConfirmation;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentService
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    /**
     * Initiate a payment for a membership plan.
     *
     * Creates a pending subscription + payment record,
     * calls the active gateway, and returns a redirect URL.
     */
    public function initiatePayment(User $user, MembershipPlan $plan): array
    {
        $gateway = $this->getActiveGateway();
        $currency = $this->settings->get('payment.currency', 'USD');

        // Cancel any existing active subscription (will be reactivated on payment success)
        $existingActive = $user->subscriptions()->active()->first();

        // Compute expiry. For new 6/12/18-month contract plans, prefer the
        // cycle-derived duration (180/365/540) so admins no longer have to
        // hand-fill duration_days for every plan. Explicit duration_days
        // still wins when set.
        $expiryDays = $plan->duration_days ?: $plan->cycleDurationDays();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'expires_at' => $expiryDays > 0 ? now()->addDays($expiryDays) : null,
            'amount_paid' => $plan->price,
        ]);

        // Create a pending payment
        $payment = Payment::create([
            'user_id' => $user->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => $gateway->getName(),
            'status' => 'pending',
            'amount' => $plan->price,
            'currency' => $currency,
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'previous_subscription_id' => $existingActive?->id,
            ],
        ]);

        // Create gateway session
        $session = $gateway->createSession($user, $subscription, $plan);

        // Store gateway session reference
        $payment->update([
            'gateway_session_id' => $session['session_id'],
            'status' => 'processing',
        ]);

        return [
            'redirect_url' => $session['redirect_url'],
            'payment' => $payment,
        ];
    }

    /**
     * Complete a payment after gateway confirmation (webhook).
     */
    public function completePayment(Payment $payment, ?string $gatewayPaymentId = null, ?string $paymentMethod = null): void
    {
        if ($payment->isCompleted()) {
            return; // Idempotent
        }

        $payment->markCompleted($gatewayPaymentId, $paymentMethod);

        // Activate the subscription
        $subscription = $payment->subscription;
        $subscription->update(['status' => 'active']);

        // Cancel old subscription if there was one
        $previousSubId = $payment->metadata['previous_subscription_id'] ?? null;
        if ($previousSubId) {
            $oldSub = UserSubscription::find($previousSubId);
            $oldSub?->cancel('Switched to new plan');
        }

        // Send confirmation email to the customer (non-blocking)
        $email = $payment->user?->email;
        if ($email) {
            try {
                Mail::to($email)->send(new PaymentConfirmation($payment->fresh()));
            } catch (Throwable $e) {
                Log::warning('Failed to send payment confirmation email', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Mark a payment as failed.
     */
    public function failPayment(Payment $payment, string $reason = ''): void
    {
        $payment->markFailed($reason);
        $payment->subscription?->update(['status' => 'cancelled']);
    }

    /**
     * Get the active payment gateway instance.
     */
    public function getActiveGateway(): PaymentGatewayInterface
    {
        $activeGateway = $this->settings->get('payment.active_gateway', 'stripe');

        return match ($activeGateway) {
            'paypal' => new PayPalGateway($this->settings),
            default => new StripeGateway($this->settings),
        };
    }

    /**
     * Get a specific gateway by name.
     */
    public function getGateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'paypal' => new PayPalGateway($this->settings),
            default => new StripeGateway($this->settings),
        };
    }

    /**
     * Find a payment by gateway session ID.
     */
    public function findBySessionId(string $sessionId): ?Payment
    {
        return Payment::where('gateway_session_id', $sessionId)->first();
    }
}
