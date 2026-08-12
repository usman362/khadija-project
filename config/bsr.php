<?php

/**
 * BSR (Bidding Service Request) settings.
 *
 * R37 is explicit: "No invented values, ever. No bidding-window number
 * (minimum, maximum, default, or any threshold) may be prefilled anywhere —
 * in the UI, in the spec, or in seeded data… The production system should not
 * invent fallback values."
 *
 * These were NULL for exactly that reason until 2026-07-31, when Khadijah set
 * them and Sir Peter approved: a standard request accepts bids for 5 days, an
 * emergency one for 24 hours, and in both cases the client may close early the
 * moment they are happy with what they have.
 *
 * They stay here rather than being hard-coded so the Admin page can move them
 * later without touching the wizard — this file is the single place both the
 * BSR wizard and the ESR form read them from.
 */
return [
    // ── Standard bidding request ─────────────────────────────────────────
    // Approved 2026-07-31: 5 days.
    'default_proposal_window_days' => env('BSR_DEFAULT_WINDOW_DAYS', 5),

    // A floor stops a client setting a window so short nobody can bid; the
    // ceiling stops a request sitting open for months. Both are approved
    // guard-rails around the 5-day default, not the default itself.
    'min_window_hours' => env('BSR_MIN_WINDOW_HOURS', 24),
    'max_window_days'  => env('BSR_MAX_WINDOW_DAYS', 30),

    // ── Emergency request ────────────────────────────────────────────────
    // Approved 2026-07-31: 24 hours. Expressed in hours because a window
    // measured in days is not an emergency.
    'esr' => [
        'default_window_hours' => env('ESR_DEFAULT_WINDOW_HOURS', 24),

        // Rule R7 — bidding closes at least this long before the event
        // starts. Checklist row 108: the deadline used to cap at the event
        // itself, so a professional could win a rush job with no time left to
        // travel to it, and the client had nobody to fall back on.
        'closes_hours_before_start' => env('ESR_CLOSE_BUFFER_HOURS', 5),
    ],

    // Either kind can be closed by the client as soon as they accept a bid —
    // the window is the outer limit, not a waiting period.
    'client_may_close_early' => true,
];
