<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Influencer Module Configuration
    |--------------------------------------------------------------------------
    */

    // Fixed signup bonus (awarded when a referred user successfully registers)
    'signup_bonus' => env('INFLUENCER_SIGNUP_BONUS', 5.00),

    // Minimum balance required before an influencer can request a payout
    'min_payout_threshold' => env('INFLUENCER_MIN_PAYOUT', 50.00),

    // Currency
    'currency' => env('INFLUENCER_CURRENCY', 'USD'),

    // Referral cookie
    'cookie_name' => 'khadija_ref',
    'cookie_days' => 30,

    // Commission tiers — auto-upgraded based on total successful referrals,
    // keyed from lowest to highest. Rates per Peter Zylstra (June 2026,
    // Developer Feedback v1.1 §8.2): reduced to control acquisition costs
    // while conversion data is gathered.
    'tiers' => [
        'starter' => [
            'label' => 'Starter',
            'rate' => 5, // percent
            'min_referrals' => 0,
        ],
        'rising' => [
            'label' => 'Rising',
            'rate' => 7.5,
            'min_referrals' => 11,
        ],
        'pro' => [
            'label' => 'Pro',
            'rate' => 10,
            'min_referrals' => 26,
        ],
        'elite' => [
            'label' => 'Elite',
            'rate' => 12.5,
            'min_referrals' => 51,
        ],
    ],
];
