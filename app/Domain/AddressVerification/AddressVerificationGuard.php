<?php

namespace App\Domain\AddressVerification;

use App\Domain\AddressVerification\Exceptions\VerificationNotLiveException;

/**
 * Pre-launch lock for PAID address verification (Developer Feedback v1.1 §7.4).
 *
 * Until ADDRESS_VERIFICATION_GO_LIVE=true, no billable provider call is made.
 * Free Layer-1 filtering still runs; the service simply routes the user to
 * manual review instead of spending an API credit. Mirrors PaymentGuard.
 */
class AddressVerificationGuard
{
    /**
     * True only when paid provider calls are permitted (launched + driver + key).
     */
    public static function paidVerificationEnabled(): bool
    {
        if (! filter_var(config('address_verification.go_live', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $driver = config('address_verification.driver');
        if (! $driver) {
            return false;
        }

        // A driver with no credentials can't make a real call.
        return match ($driver) {
            'usps'   => (bool) config('address_verification.providers.usps.user_id'),
            'google' => (bool) config('address_verification.providers.google.api_key'),
            default  => false,
        };
    }

    /**
     * Hard assert at the provider call site — refuse to spend money pre-launch.
     */
    public static function assertPaidVerificationAllowed(): void
    {
        if (! self::paidVerificationEnabled()) {
            throw new VerificationNotLiveException();
        }
    }
}
