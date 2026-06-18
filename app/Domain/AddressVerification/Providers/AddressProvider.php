<?php

namespace App\Domain\AddressVerification\Providers;

/**
 * Contract for a Layer-2 paid address-validation provider (USPS, Google, …).
 *
 * Implementations are only ever constructed/called once
 * AddressVerificationGuard::paidVerificationEnabled() is true (launched + keyed).
 */
interface AddressProvider
{
    /**
     * Validate a physical address against the provider.
     *
     * @param array{line1:string,line2?:string,city:string,state:string,zip:string} $address
     * @return array{matched:bool,normalized:?array,reason:?string}
     */
    public function verify(array $address): array;
}
