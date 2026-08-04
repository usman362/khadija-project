<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Which toolkit tier unlocks which tools
    |--------------------------------------------------------------------------
    |
    | Rule R31: access is gated by tool SUBSET per tier, not by automation
    | depth. Semi unlocks one set of the 12 Client Toolkit tools; Maximum
    | unlocks more. Manual gets no toolkit access at all.
    |
    | This is deliberately NOT config/ai-levels.php. That file says how deeply
    | a tool may automate; this one says whether the client can open it.
    |
    | 'confirmed' is the important column. R31 says the breakdown must come
    | from Peter and warns "do not invent it", so only what is actually
    | confirmed is marked true:
    |
    |   Timeline Builder — Semi, seen live on the screen Peter reviewed.
    |
    | The rest carry Khadijah's proposal of 2026-08-04, which she flagged in
    | the same note as needing Peter's confirmation. Until 'confirmed' is true
    | the tier table shows them as awaiting confirmation rather than stating
    | them as fact to a paying client.
    |
    | Her proposal was written against an older tool list and named five tools
    | that are not in the client 12 — Pricing Calculator, Proposal Builder and
    | Staffing Elements are professional tools, and SMART Checkout and Legal
    | Responsibility Check do not exist in the catalogue. Those are dropped
    | here; the three client tools she did not mention (Smart Checklist,
    | Message Builder, Language) have no proposal at all.
    |
    | To lock a row: set its tier from Peter's answer and flip confirmed to
    | true. Nothing else needs touching.
    |
    */

    'tiers' => ['semi' => 'Semi Tools', 'maximum' => 'Maximum Tools'],

    // tool title => ['tier' => semi|maximum|null, 'confirmed' => bool, 'note' => ?string]
    'tools' => [

        'Timeline Builder' => [
            'tier' => 'semi',
            'confirmed' => true,
            'note' => 'Confirmed from the live screen.',
        ],

        'Budget Planner' => [
            'tier' => 'semi',
            'confirmed' => false,
            'note' => "Khadijah's proposal, 2026-08-04.",
        ],
        'Guided Event Planner' => [
            'tier' => 'maximum',
            'confirmed' => false,
            'note' => "Khadijah's proposal, 2026-08-04.",
        ],
        'Style & Inspiration' => [
            'tier' => 'maximum',
            'confirmed' => false,
            'note' => "Khadijah's proposal, 2026-08-04 (listed under its old name, Theme & Style Advisor).",
        ],
        'Best Match' => [
            'tier' => 'maximum',
            'confirmed' => false,
            'note' => "Khadijah's proposal, 2026-08-04 (listed under its old name, SMART Match).",
        ],
        'Review Builder' => [
            'tier' => 'maximum',
            'confirmed' => false,
            'note' => "Khadijah's proposal, 2026-08-04.",
        ],

        // She proposed "Venue Capacity Calculator" — a name that sits between
        // these two real tools, so neither can take it.
        'Venue Compatibility Check' => ['tier' => null, 'confirmed' => false, 'note' => null],
        'Guest Capacity Calculator' => ['tier' => null, 'confirmed' => false, 'note' => null],

        // Not mentioned in any proposal.
        'Smart Checklist'    => ['tier' => null, 'confirmed' => false, 'note' => null],
        'Contract Assistant' => ['tier' => null, 'confirmed' => false, 'note' => null],
        'Message Builder'    => ['tier' => null, 'confirmed' => false, 'note' => null],
        'Language'           => ['tier' => null, 'confirmed' => false, 'note' => null],
    ],

    /*
    | Maximum is expected to be a superset of Semi — everything Semi unlocks,
    | plus more — unless Peter says otherwise (his words, via the spec).
    */
    'maximum_includes_semi' => true,

];
