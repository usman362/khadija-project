<?php

/*
 * What earns a client badge.
 *
 * The dashboard has said for a while that badges "are awarded from what you
 * actually do on GigResource — events completed, paying on time, and coming
 * back to the same professionals". These are those three sentences, made real.
 * Nothing here was invented: each rule is measured against something the
 * platform already records.
 *
 * The Owner sets the names and the thresholds. They live here so he can change
 * them, add a tier, or retire one without a code change — and so that a badge
 * can never be awarded on a rule nobody agreed to.
 *
 * 'rule' is the measurement; 'need' is how much of it earns the badge.
 *
 * The SHAPE is settled — every badge on the site is a hexagon (Sir Peter,
 * 2026-09-09). The colour and the icon inside each one are Khadijah's, and
 * they are here so she can change them without a code change.
 *
 *   id_verified          client ID verification completed (not built yet: 0)
 *   events_completed     distinct events with a booking that reached completed
 *   prompt_payer         paid on time at least once, and nothing late this month
 *   reviews_written      reviews this client has submitted
 */
return [

    /*
     * The two badges that are not earned by activity but by verification and
     * by reviews. Same hexagon, same rule about who picks the colour.
     */
    'verified_colour'  => '#2563eb',
    'top_rated_colour' => '#f59e0b',

    /*
     * Sir Peter's PM-14 client badge spec (answered Sep 5): four badges, all
     * automatic, simple flat icons in brand blue (shield, calendar stack,
     * checkmark card, star speech bubble). Shown on the client's own profile
     * and next to their name where professionals see it, not on the dashboard.
     */
    'client' => [
        [
            'key'    => 'verified-client',
            'name'   => 'Verified Client',
            'blurb'  => 'Awarded when you complete ID verification.',
            'icon'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
            'colour' => '#2563eb',
            'rule'   => 'id_verified',
            'need'   => 1,
        ],
        [
            'key'    => 'frequent-planner',
            'name'   => 'Frequent Planner',
            'blurb'  => 'Five events completed on GigResource.',
            'icon'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="M6 3.5h12" opacity=".6"/></svg>',
            'colour' => '#2563eb',
            'rule'   => 'events_completed',
            'need'   => 5,
        ],
        [
            'key'    => 'prompt-payer',
            'name'   => 'Prompt Payer',
            'blurb'  => 'Pays on time. Checked every month, and removed after a late payment.',
            'icon'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M8 15l2 2 4-4"/></svg>',
            'colour' => '#2563eb',
            'rule'   => 'prompt_payer',
            'need'   => 1,
        ],
        [
            'key'    => 'community-voice',
            'name'   => 'Community Voice',
            'blurb'  => 'Three reviews written for professionals.',
            'icon'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 6.8l1.1 2.2 2.4.3-1.7 1.7.4 2.4-2.2-1.2-2.2 1.2.4-2.4-1.7-1.7 2.4-.3z"/></svg>',
            'colour' => '#2563eb',
            'rule'   => 'reviews_written',
            'need'   => 3,
        ],
    ],
];
