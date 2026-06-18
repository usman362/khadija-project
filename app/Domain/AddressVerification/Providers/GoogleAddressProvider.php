<?php

namespace App\Domain\AddressVerification\Providers;

use LogicException;

/**
 * Google Address Validation provider (Developer Feedback v1.1 §7.3 Step 1, alt).
 *
 * SCAFFOLD: reachable only once ADDRESS_VERIFICATION_GO_LIVE=true AND a
 * GOOGLE_ADDRESS_API_KEY exists. Implement verify() against the Address
 * Validation API (addressvalidation.googleapis.com) when keys are provisioned.
 */
class GoogleAddressProvider implements AddressProvider
{
    public function verify(array $address): array
    {
        $key = config('address_verification.providers.google.api_key');

        if (! $key) {
            throw new LogicException('Google provider is selected but GOOGLE_ADDRESS_API_KEY is not configured.');
        }

        // TODO(launch): POST to Address Validation API, read verdict
        // (addressComplete, hasUnconfirmedComponents) → matched/normalized/reason.
        throw new LogicException('GoogleAddressProvider::verify() not implemented yet — awaiting Google credentials & go-live.');
    }
}
