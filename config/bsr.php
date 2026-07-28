<?php

/**
 * BSR (Bidding Service Request) settings.
 *
 * R37 is explicit: "No invented values, ever. No bidding-window number
 * (minimum, maximum, default, or any threshold) may be prefilled anywhere —
 * in the UI, in the spec, or in seeded data… The production system should not
 * invent fallback values."
 *
 * So these are NULL until GigResource formally approves them on the Admin/Ops
 * page (/admin/settings/ssr-bidding-windows). While they are null the create
 * wizard requires the client to set a proposal deadline themselves, rather
 * than the platform quietly picking one.
 *
 * At approval: set the values here (or move them behind the Admin page) —
 * this file is the single place the wizard reads them from.
 */
return [
    // Approved default window, in days. NULL = not yet configured.
    'default_proposal_window_days' => env('BSR_DEFAULT_WINDOW_DAYS'),

    // Approved floor and ceiling. NULL = not yet configured, so not enforced.
    'min_window_hours' => env('BSR_MIN_WINDOW_HOURS'),
    'max_window_days'  => env('BSR_MAX_WINDOW_DAYS'),
];
