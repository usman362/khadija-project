<?php

/*
 * Who gets the pop-up messenger and the conversation details panel.
 *
 * Sir Peter's Ideas 2, 3 and 4. One messaging system throughout — these
 * settings decide how a conversation can be REACHED and DISPLAYED, never what
 * is in it, so nobody's messages depend on what they pay.
 *
 * Kept in config because the Owner asked for the eligibility to be changeable
 * later without a code change.
 */
return [

    /*
     * The messenger that pops up from the bottom of the screen (Idea 2).
     *
     * 'plans' lists the professional membership slugs that get it. An empty
     * list means every professional — which is where this starts, so the
     * feature can be seen and judged before it is put behind a tier.
     */
    'dock' => [
        'enabled' => env('MESSENGER_DOCK', true),
        'plans'   => array_filter(explode(',', (string) env('MESSENGER_DOCK_PLANS', ''))),
    ],

    /* The expandable details panel beside a conversation (Idea 4). */
    'panel' => [
        'enabled' => env('MESSENGER_PANEL', true),
        'plans'   => array_filter(explode(',', (string) env('MESSENGER_PANEL_PLANS', ''))),
    ],

    /*
     * Roles that never have to buy anything for either.
     *
     * Peter, explicitly: a client must not need a membership to answer a
     * professional who has this. The same goes for influencers and staff.
     */
    'free_roles' => ['client', 'influencer', 'admin'],
];
