<?php

/*
|--------------------------------------------------------------------------
| Address Verification (Developer Feedback v1.1 §7.3–7.5)
|--------------------------------------------------------------------------
| Risk-based, two-layer address verification.
|
|  Layer 1 (FREE, always on): front-end + AddressFilter reject blank fields,
|          PO Boxes, and obviously-fake input BEFORE any paid API is called.
|  Layer 2 (PAID, launch-gated): a real validation provider (USPS / Google
|          Address Validation) runs only after Layer 1 passes, capped at
|          `max_paid_attempts` per user.
|
| GO-LIVE LOCK: like the payments lock, no PAID verification call is made
| while `go_live` is false — the flow still runs end-to-end and free filtering
| still protects signup, but the provider call is skipped and the user lands in
| "manual review" instead. Flip `ADDRESS_VERIFICATION_GO_LIVE=true` at launch
| once a provider account + keys exist.
*/

return [
    // Pre-launch lock for the PAID provider call (Layer 2).
    'go_live' => env('ADDRESS_VERIFICATION_GO_LIVE', false),

    // Which paid provider Layer 2 uses: 'usps' | 'google' | null (none yet).
    'driver' => env('ADDRESS_VERIFICATION_DRIVER', 'usps'),

    // §7.4 — hard cap on paid attempts before the input locks to manual review.
    'max_paid_attempts' => (int) env('ADDRESS_VERIFICATION_MAX_ATTEMPTS', 2),

    // §7.4 — Peter: paid verification should trigger only AFTER payment clears
    // where possible. When true, callers defer the paid call to post-payment.
    'verify_after_payment' => env('ADDRESS_VERIFICATION_AFTER_PAYMENT', true),

    // Provider credentials (empty until Peter provisions the accounts).
    'providers' => [
        'usps' => [
            'user_id' => env('USPS_USER_ID'),
            'base_url' => env('USPS_BASE_URL', 'https://secure.shippingapis.com/ShippingAPI.dll'),
        ],
        'google' => [
            'api_key' => env('GOOGLE_ADDRESS_API_KEY'),
        ],
    ],

    // §7.3 — KYB / business-match provider (Persona, Middesk, Google Places).
    // Stubbed until selected; the service degrades to manual review without it.
    'kyb' => [
        'driver' => env('ADDRESS_KYB_DRIVER'), // 'persona' | 'middesk' | 'google_places' | null
        'api_key' => env('ADDRESS_KYB_API_KEY'),
    ],
];
