<?php

/**
 * BSR (Bidding Service Request) settings.
 *
 * R37 is explicit: "No invented values, ever. No bidding-window number
 * (minimum, maximum, default, or any threshold) may be prefilled anywhere —
 * in the UI, in the spec, or in seeded data… The production system should not
 * invent fallback values."
 *
 * These were NULL for exactly that reason until they were approved. The
 * current values are the Owner's decision of 2026-08-22: a standard request
 * accepts bids for 48 hours, an emergency one for 2 hours (bids shown in real
 * time, never held until the window closes), and in both cases the client may
 * close early the moment they are happy with what they have. (Supersedes the
 * 2026-07-31 5-day / 24-hour values.)
 *
 * They stay here rather than being hard-coded so the Admin page can move them
 * later without touching the wizard — this file is the single place both the
 * BSR wizard and the ESR form read them from.
 */
return [
    // ── Standard bidding request ─────────────────────────────────────────
    // Approved 2026-08-22: 48 hours. Client may set a shorter deadline; if the
    // event date falls inside the window, persist() shortens it automatically.
    'default_proposal_window_hours' => env('BSR_DEFAULT_WINDOW_HOURS', 48),

    // A floor stops a client setting a window so short nobody can bid; the
    // ceiling stops a request sitting open for months. Both are approved
    // guard-rails around the 48-hour default, not the default itself.
    'min_window_hours' => env('BSR_MIN_WINDOW_HOURS', 24),
    'max_window_days'  => env('BSR_MAX_WINDOW_DAYS', 30),

    // ── Emergency request ────────────────────────────────────────────────
    // Approved 2026-08-22: 2 hours. An emergency window measured in more than
    // a couple of hours is not an emergency.
    'esr' => [
        'default_window_hours' => env('ESR_DEFAULT_WINDOW_HOURS', 2),

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
