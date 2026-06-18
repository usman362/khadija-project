<?php

namespace App\Domain\AddressVerification\Providers;

use LogicException;

/**
 * USPS Address Validation provider (Developer Feedback v1.1 §7.3 Step 1).
 *
 * SCAFFOLD: the HTTP integration is intentionally not implemented — it is only
 * reachable once ADDRESS_VERIFICATION_GO_LIVE=true AND a USPS_USER_ID exists.
 * Implement verify() against the USPS Web Tools "Verify" endpoint when Peter
 * provisions the account.
 */
class UspsAddressProvider implements AddressProvider
{
    public function verify(array $address): array
    {
        $userId = config('address_verification.providers.usps.user_id');

        if (! $userId) {
            throw new LogicException('USPS provider is selected but USPS_USER_ID is not configured.');
        }

        // TODO(launch): POST to USPS Web Tools "Verify" API, parse <Address>
        // response, set matched/normalized/reason. Until then this is unreachable
        // in production because AddressVerificationGuard blocks the call.
        throw new LogicException('UspsAddressProvider::verify() not implemented yet — awaiting USPS credentials & go-live.');
    }
}
