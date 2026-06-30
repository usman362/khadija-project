<?php

/*
|--------------------------------------------------------------------------
| Opportunity Early-Access (SSR / MSR / ESR)
|--------------------------------------------------------------------------
| Higher membership tiers see new direct requests (SSR / MSR / ESR) before
| everyone else — a competitive edge (Peter / ChatGPT membership restructure).
|
| This is the source-of-truth CONFIG; the gate is enforced once the live
| SSR/MSR/ESR pipeline lands. `early_access_minutes` is how long a request is
| held back from a tier AFTER it is published to the tiers above it:
|   - elite        => 0    (immediate — sees every new request first)
|   - professional => 60   (visible 60 minutes after Elite)
|   - starter      => null (no early access — standard visibility only)
*/

return [

    'request_types' => ['SSR', 'MSR', 'ESR'],

    'early_access_minutes' => [
        'elite'        => 0,
        'professional' => 60,
        'starter'      => null,
    ],

    // Featured / premium opportunity access per tier.
    'premium_access' => [
        'elite'        => 'full',
        'professional' => 'limited',
        'starter'      => 'none',
    ],
];
