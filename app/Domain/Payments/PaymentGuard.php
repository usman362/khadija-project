<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Exceptions\PaymentsNotLiveException;

/**
 * Pre-launch payment lock. Until PAYMENTS_GO_LIVE=true, any attempt to move
 * real money is refused — while TEST mode (test keys / test cards) keeps
 * working so the flow can be exercised end-to-end.
 *
 * Called at every gateway charge-creation point (the definitive choke points).
 */
class PaymentGuard
{
    /**
     * Allow the charge to proceed only when it cannot move real money,
     * unless the platform has officially gone live.
     *
     * @param  string       $mode       'test' | 'live' (from payment.mode)
     * @param  string|null  $secretKey  the gateway secret key in use, if known
     */
    public static function assertLiveChargeAllowed(string $mode, ?string $secretKey = null): void
    {
        // Launched — real charges are permitted.
        if (filter_var(config('payments.go_live', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        // Pre-launch: block anything that would charge real money — either an
        // explicit "live" mode, or a live Stripe secret key slipped in while
        // mode is still "test".
        $modeIsLive = strtolower(trim($mode)) === 'live';
        $keyIsLive  = is_string($secretKey) && str_starts_with($secretKey, 'sk_live_');

        if ($modeIsLive || $keyIsLive) {
            throw new PaymentsNotLiveException();
        }
    }
}
