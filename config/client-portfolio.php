<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Client Portfolio Page — Rule R53
    |--------------------------------------------------------------------------
    |
    | The page is APPROVED TO BUILD (Peter, 2026-08-06). Four of its twelve
    | sections carry an open question, and the spec's own instruction is to
    | ship those hidden rather than hold the other eight — "the affected
    | sections should ship as placeholder/hidden until answered, per the same
    | discipline already applied to Fit Score/Best Match."
    |
    | Each flag below is one of those questions. Flipping it to true is the
    | whole of "ship that section" once the answer arrives — the sections are
    | built, not stubbed.
    |
    */

    'sections' => [

        /*
        | Section 4 — Client Badges.
        |
        | Ten badges are drawn in the mockup and none has an earning rule.
        | R29 requires every earned badge to have a fixed, auditable criterion,
        | so showing them now would mean either inventing the rules or awarding
        | badges nobody earned. Khadijah's badge catalog (v3.0, 2026-08-08) is
        | still PROPOSED, not locked.
        */
        'badges' => env('CLIENT_PORTFOLIO_BADGES', false),

        /*
        | Section 5 — Event History.
        |
        | The page is visible to every professional on the platform, and this
        | section would put the names, venues, dates and photos of a client's
        | private events — weddings, birthday parties — in front of all of
        | them, not only the professionals who worked those events.
        |
        | When this is enabled, `event_history_detail` decides how much shows.
        */
        'event_history' => env('CLIENT_PORTFOLIO_EVENT_HISTORY', false),

        /*
        | Section 10 — Favourite Professionals.
        |
        | A public list says which professionals a client prefers, in front of
        | the ones they did not pick and the competitors of the ones they did.
        | Default private until Peter says otherwise.
        */
        'favourite_professionals' => env('CLIENT_PORTFOLIO_FAVOURITES', false),

        /*
        | Section 11 — "How It Works With [Client]".
        |
        | Blocked on a data-source decision rather than a privacy one: nobody
        | has said whether the five traits are derived from reviews or authored
        | by the client in Profile & Settings. Derived needs a formula;
        | authored needs a field to author it in. Neither exists yet, so there
        | is nothing honest to render.
        */
        'working_style' => env('CLIENT_PORTFOLIO_WORKING_STYLE', false),
    ],

    /*
    | How much of an event shows when Section 5 is on.
    |
    |   generalised — event type, month and city. No event name, no venue,
    |                 no photographs. This is the recommended default: a name
    |                 plus a venue plus a date locates a private party.
    |   full        — everything the mockup draws.
    */
    'event_history_detail' => env('CLIENT_PORTFOLIO_EVENT_DETAIL', 'generalised'),

];
