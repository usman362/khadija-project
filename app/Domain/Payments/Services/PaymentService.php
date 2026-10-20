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
    /**
     * Join a free plan, with no gateway and no payment at all.
     *
     * Starter became free on 20 October 2026. Before this, `initiate()` saw a
     * free plan and REFUSED it with "Free plans do not require payment", so
     * making Starter $0 would have meant nobody could join Starter at all: the
     * button returned an error. The price was the easy half.
     *
     * No Payment row is written, deliberately. The revised plan says it plainly:
     * "Do not make a monthly Stripe subscription for Starter." A $0 charge
     * through a gateway is a real API call, a real fee in some configurations,
     * and a row in the money record that says nothing happened.
     *
     * Idempotent: somebody already on the free plan stays on the subscription
     * they have rather than collecting a second one.
     */
    public function activateFree(User $user, MembershipPlan $plan): UserSubscription
    {
        if (! $plan->isFree()) {
            throw new \InvalidArgumentException('That plan is not free.');
        }

        $existing = $user->subscriptions()->active()->first();

        if ($existing && (int) $existing->membership_plan_id === (int) $plan->id) {
            return $existing;
        }

        $expiryDays = $plan->duration_days ?: $plan->cycleDurationDays();

        $subscription = UserSubscription::create([
            'user_id'            => $user->id,
            'membership_plan_id' => $plan->id,
            'status'             => 'active',
            'starts_at'          => now(),
            // A free plan that expires is a free plan that quietly stops
            // working, so it does not expire.
            'expires_at'         => null,
            'amount_paid'        => 0,
        ]);

        // Whatever they were on before is over, the same as a paid switch.
        $existing?->cancel('Switched to the free Starter plan');

        return $subscription;
    }

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
