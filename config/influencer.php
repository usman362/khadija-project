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
            'color' => '#22c55e',
            'icon' => 'leaf',
            'tagline' => 'Kickstart your journey and start earning.',
            'benefits' => ['Access to referral tools', 'Community access', 'Email support', '5% commission on referrals'],
        ],
        'rising' => [
            'label' => 'Rising',
            'rate' => 7.5,
            'min_referrals' => 11,
            'color' => '#2563eb',
            'icon' => 'star',
            'tagline' => 'Build momentum and grow your network.',
            'benefits' => ['All Starter benefits', 'Priority support', 'Higher visibility', '7.5% commission on referrals'],
        ],
        'pro' => [
            'label' => 'Pro',
            'rate' => 10,
            'min_referrals' => 26,
            'color' => '#7c3aed',
            'icon' => 'gem',
            'tagline' => "You're a top contributor and influencer.",
            'benefits' => ['All Rising benefits', 'Exclusive content & assets', 'Featured in directory', '10% commission on referrals'],
        ],
        'elite' => [
            'label' => 'Elite',
            'rate' => 12.5,
            'min_referrals' => 51,
            'color' => '#f97316',
            'icon' => 'crown',
            'tagline' => 'A recognized leader with proven results.',
            'benefits' => ['All Pro benefits', 'Personal account manager', 'VIP invitations', '12.5% commission on referrals'],
        ],
    ],

    // Achievement badges — earned/locked computed from real data.
    // slug => [label, desc, icon, color, metric(referrals|earnings|profile|tier), threshold].
    'badges' => [
        'first_referral'  => ['label' => 'First Referral', 'desc' => 'Earned your first referral',        'icon' => 'gift',   'color' => '#22c55e', 'metric' => 'referrals', 'threshold' => 1],
        'ten_referrals'   => ['label' => 'Networker',       'desc' => 'Reached 10 successful referrals',    'icon' => 'users',  'color' => '#2563eb', 'metric' => 'referrals', 'threshold' => 10],
        'fifty_referrals' => ['label' => 'Super Connector', 'desc' => 'Reached 50 successful referrals',    'icon' => 'zap',    'color' => '#7c3aed', 'metric' => 'referrals', 'threshold' => 50],
        'first_earnings'  => ['label' => 'First Earnings',  'desc' => 'Earned your first commission',       'icon' => 'dollar', 'color' => '#16a34a', 'metric' => 'earnings',  'threshold' => 1],
        'hundred_club'    => ['label' => '$100 Club',       'desc' => 'Earned over $100 in commissions',    'icon' => 'wallet', 'color' => '#f97316', 'metric' => 'earnings',  'threshold' => 100],
        'thousand_club'   => ['label' => '$1K Club',        'desc' => 'Earned over $1,000 in commissions',  'icon' => 'trophy', 'color' => '#d97706', 'metric' => 'earnings',  'threshold' => 1000],
        'profile_pro'     => ['label' => 'Profile Pro',     'desc' => 'Completed your influencer profile',  'icon' => 'badge',  'color' => '#db2777', 'metric' => 'profile',   'threshold' => 100],
        'top_tier'        => ['label' => 'Top Tier',        'desc' => 'Reached the Elite commission tier',  'icon' => 'crown',  'color' => '#ea580c', 'metric' => 'tier',      'threshold' => 4],
    ],
];
