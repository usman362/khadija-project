<?php

namespace App\Domain\Payments\Contracts;

use App\Models\MembershipPlan;
use App\Models\UserSubscription;
use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * Create a payment session and return redirect URL + session ID.
     *
     * @return array{redirect_url: string, session_id: string}
     */
    public function createSession(
        User $user,
        UserSubscription $subscription,
        MembershipPlan $plan,
    ): array;

    /**
     * Verify webhook signature from the gateway.
     */
    public function verifyWebhook(array $headers, string $rawBody): bool;

    /**
     * Process a webhook event from the gateway.
     */
    public function processWebhook(array $payload): void;

    /**
     * Get the gateway name identifier.
     */
    public function getName(): string;
}
