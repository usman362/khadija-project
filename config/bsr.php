<?php

/**
 * BSR (Bidding Service Request) settings.
 *
 * These are the values Peter's R37 "Admin/Ops SSR Bidding Window Settings" page
 * will own once it is built. It was agreed to run on fixed config values until
 * then, so they live here — one place to change, and one place for that page to
 * take over from — rather than scattered through the create flow.
 */
return [
    // How long proposals stay open when the client doesn't pick a deadline.
    'default_proposal_window_days' => env('BSR_DEFAULT_WINDOW_DAYS', 7),

    // Floor and ceiling the Admin page will enforce. Not applied as validation
    // yet: R37 is explicit that no window values should be treated as approved
    // until GigResource signs them off.
    'min_window_hours' => env('BSR_MIN_WINDOW_HOURS', 24),
    'max_window_days'  => env('BSR_MAX_WINDOW_DAYS', 60),
];
