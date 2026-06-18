<?php

namespace App\Domain\AddressVerification\Exceptions;

use RuntimeException;

/**
 * Thrown when a PAID address-verification call is attempted before the platform
 * has flipped ADDRESS_VERIFICATION_GO_LIVE=true (mirrors PaymentsNotLiveException).
 */
class VerificationNotLiveException extends RuntimeException
{
    public function __construct(string $message = 'Paid address verification is not live yet.')
    {
        parent::__construct($message);
    }
}
