<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Go-Live Switch (pre-launch payment lock)
    |--------------------------------------------------------------------------
    | Per Peter: real money must NOT be charged until the platform officially
    | goes live — but the payment flow still needs to be testable.
    |
    | While this is FALSE (the default), any attempt to create a real/live
    | charge is blocked. Testing still works fully in Stripe/PayPal TEST mode
    | (admin Payment Settings → mode = Test) using test cards — no real money
    | ever moves.
    |
    | At launch: set PAYMENTS_GO_LIVE=true in .env AND switch the admin
    | Payment Settings mode to "Live" with live API keys.
    */
    'go_live' => env('PAYMENTS_GO_LIVE', false),
];
