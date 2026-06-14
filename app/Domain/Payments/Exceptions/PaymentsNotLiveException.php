<?php

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

/**
 * Thrown when a real/live charge is attempted before the platform has
 * officially gone live (PAYMENTS_GO_LIVE=false). Testing in TEST mode is
 * unaffected — this only blocks live money movement pre-launch.
 */
class PaymentsNotLiveException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: 'Live payments are not enabled yet. The platform is in test mode until launch — use Test mode and test cards to try the payment flow. No real charges are processed.');
    }
}
