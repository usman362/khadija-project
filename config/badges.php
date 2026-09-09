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
 *   events_completed     bookings of this client that reached completed
 *   paid_on_time         agreements funded on or before the balance was due
 *   repeat_professional  professionals this client has booked more than once
 */
return [

    'client' => [
        [
            'key'   => 'first-event',
            'name'  => 'First Event',
            'blurb' => 'Completed your first event on GigResource.',
            'icon'  => '🎉',
            'colour' => '#f59e0b',
            'rule'  => 'events_completed',
            'need'  => 1,
        ],
        [
            'key'   => 'seasoned-host',
            'name'  => 'Seasoned Host',
            'blurb' => 'Completed five events.',
            'icon'  => '🏆',
            'colour' => '#f97316',
            'rule'  => 'events_completed',
            'need'  => 5,
        ],
        [
            'key'   => 'pays-on-time',
            'name'  => 'Pays On Time',
            'blurb' => 'Settled three agreements on or before the balance was due.',
            'icon'  => '⏱',
            'colour' => '#10b981',
            'rule'  => 'paid_on_time',
            'need'  => 3,
        ],
        [
            'key'   => 'they-came-back',
            'name'  => 'Worth Coming Back To',
            'blurb' => 'Booked the same professional more than once.',
            'icon'  => '🤝',
            'colour' => '#6366f1',
            'rule'  => 'repeat_professional',
            'need'  => 1,
        ],
    ],
];
