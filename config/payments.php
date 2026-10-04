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

    /*
    |--------------------------------------------------------------------------
    | Deposit
    |--------------------------------------------------------------------------
    | Sir Peter, 2 October: "The clients should not have to enter any deposits
    | that the professionals and influencers role bc it's upto them to decide
    | on the percentage if any."
    |
    | He is right, and the client no longer chooses one. The professional has
    | nowhere to set theirs either, because their side of the agreement is not
    | open yet, and the deposit is what the checkout charges: left empty it
    | would take nothing. So until a professional can name their own figure,
    | this is the one used, and the client is told where it came from rather
    | than being asked to pick it.
    |
    | The 15 to 50 band stays the rule for whatever is set.
    */
    'deposit_percent_default' => (int) env('DEPOSIT_PERCENT_DEFAULT', 25),
    'deposit_percent_min'     => 15,
    'deposit_percent_max'     => 50,

    /*
    |--------------------------------------------------------------------------
    | Client request fee (R10)
    |--------------------------------------------------------------------------
    | "$0 to submit / post / bid; a single $2.99 only on finalization (per
    | request instance, not per gig/pro); $0 if nothing finalizes."
    |
    | `collect_at` is the one open pricing decision: 'finalization' is what R10
    | and R35 say today. Moving it to 'post' is a rule change — R10's "$0 to
    | post" and R35's "no pay-posting-fee step in the wizard" both have to be
    | rewritten first, so this stays where the rules are until that happens.
    |
    | The professional's commission is untouched by this either way: that is
    | theirs, deducted at payout (Commission::rateFor), and a client payment is
    | never offset against it.
    */
    'client_request_fee' => 2.99,
    'client_request_fee_collect_at' => 'finalization',
];
