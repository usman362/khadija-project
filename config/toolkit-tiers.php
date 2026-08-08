<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Which toolkit tier unlocks which tools — Rule R31
    |--------------------------------------------------------------------------
    |
    | Answered in full by Peter on 2026-08-05
    | (GigResource_Toolkit_Suite_and_Tier_Assignment_2026-08-05.xlsx).
    |
    | Access is a tool SUBSET per tier, not automation depth — that second
    | thing is config/ai-levels.php and is a different question entirely.
    |
    | Manual is a preset: ALWAYS zero tools, on both sides. It is not evaluated
    | per tool, so no tool is ever listed under it.
    |
    | Maximum is not a curated set — it is everything. Only the Semi subset is
    | chosen, six a side.
    |
    */

    'tiers' => ['manual' => 'Manual', 'semi' => 'Semi', 'maximum' => 'Maximum'],

    // One-time purchases, not subscriptions. Both sides pay the same.
    'prices' => ['manual' => 0.00, 'semi' => 2.99, 'maximum' => 5.99],

    /*
    | Toolkit access is a purchased entitlement tied to account status — it is
    | not perpetual or portable if the account is closed (Peter, 2026-08-05).
    */
    'requires_active_account' => true,

    // Manual unlocks nothing, by rule rather than by an empty list.
    'manual_unlocks_nothing' => true,

    // Maximum unlocks all 12 on its side; only Semi is a chosen subset.
    'maximum_unlocks_everything' => true,

    /*
    |--------------------------------------------------------------------------
    | The Semi subset — six tools a side
    |--------------------------------------------------------------------------
    |
    | Names match AiToolCatalog exactly. Everything not listed here is
    | Maximum-only.
    |
    */

    'semi_tools' => [

        // Five tools, as R31 records them after Peter's 2026-08-05 spreadsheet
        // correction: "Client Semi is now (5) = Budget Planner, Smart
        // Checklist, Timeline Builder, Best Match, Message Builder; Client
        // Maximum-only is now (7) = Guided Event Planner, Guest Capacity
        // Calculator, Venue Compatibility Check, Style & Inspiration, Review
        // Builder, Contract Assistant, Language."
        //
        // The Marketplace slot was previously read from Khadijah's draft as
        // Review Builder. The correction reverses that pair: Best Match is
        // Semi, Review Builder is Maximum-only. It is the right way round —
        // finding a professional is how a client starts, and Review Builder
        // only has anything to write about after an event is over.
        'client' => [
            // Planning
            'Budget Planner',
            'Smart Checklist',
            // Confirmed live at Semi on 2026-07-24 and locked into R31. Peter
            // has now traced it through every later revision: never moved,
            // never re-opened.
            'Timeline Builder',

            // Marketplace
            'Best Match',

            // Operations
            'Message Builder',
        ],

        'professional' => [
            'Availability Scheduler',  // you cannot take work without a calendar
            'Bid Calculator',          // needed to bid competitively at all
            'Proposal Builder',        // the proposal IS the bid submission
            'Pricing Calculator',      // feeds every bid and proposal
            'Portfolio Optimizer',     // a strong portfolio is what gets you chosen
            'Message Builder',         // everyday contact with a client
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Which tiers a professional may buy
    |--------------------------------------------------------------------------
    |
    | Re-coupled to membership by Peter on 2026-08-05, after an interim
    | decoupled model that stood for part of the same day. The membership fee
    | still governs commission, early access and visibility on its own — the
    | toolkit is a separate one-time purchase on top.
    |
    | Elite is offered Maximum only: the top membership has nothing lower to
    | choose from.
    |
    | Clients have no membership, so they may buy either tier.
    |
    */

    'purchasable_by_plan' => [
        'starter'      => ['manual'],
        'professional' => ['manual', 'semi', 'maximum'],   // Semi, upgradable to Maximum
        'enterprise'   => ['manual', 'maximum'],           // Elite — Maximum only
    ],

];
