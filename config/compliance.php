<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Categories that require proof of insurance
    |--------------------------------------------------------------------------
    |
    | Khadijah, 2026-08-04: insurance/COI is required for Alcohol service,
    | Catering, Security, and Pyrotechnics/Fireworks.
    |
    | Matched on the exact category name, case-insensitively, not on keywords.
    | A keyword list looked tempting and would have demanded a certificate of
    | insurance from anyone offering "Bar/Bat Mitzvah".
    |
    | Both category trees are listed because the v1 → v2 switch has not
    | happened yet (see docs/TAXONOMY_V2.md). v1 carries the same name several
    | times over; matching by name catches every copy, which is what we want.
    |
    | NOTE: V2 has no pyrotechnics or fireworks category. The nearest is "Fire
    | Performers", a service under Entertainment, which is a performer rather
    | than a pyrotechnician. Flagged for Khadijah — the fourth regulated
    | category has nowhere to attach until the taxonomy adds one.
    |
    | The 241-row Required / Conditional / Not Required matrix lives on
    | categories.insurance_* columns as a DRAFT for the broker conversation.
    | Those columns do not gate anything until insurance_matrix_signed_off
    | is true (broker + attorney). Do not flip that from a filled spreadsheet.
    |
    */

    'insurance_required_categories' => [

        'v1' => [
            // Catering / food
            'Catering Services',
            'Catering Coordination',
            'Catering & Food Trucks Services',
            'Food Services',
            'Food Truck & Mobile Kitchen Services',
            'Food Booth Setup',
            'BBQ Smokehouse Catering',
            'Dessert & Sweet Table Catering',
            'Seasonal Menu Catering',
            'Specialty Cuisine Catering',
            // Alcohol / bar
            'Beverage Services',
            'Beverage Service',
            'Beverage & Bar Services',
            'Beverage Stations',
            'Beverage Packages',
            'Bartenders & Mixology Services',
            'Full Bar Setup',
            'Mobile Bar Rentals',
        ],

        'v2' => [
            'Catering & Food Services',
            'Bar, Beverage & Mixology Services',
            'Security & Crowd Management',
            // Closest thing V2 has to pyrotechnics — see the note above.
            'Fire Performers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate expiry
    |--------------------------------------------------------------------------
    |
    | A certificate stops counting the day it expires. Professionals are warned
    | this many days ahead so cover does not lapse mid-booking.
    |
    */

    'insurance_expiry_warning_days' => 30,

    /*
    | Broker + attorney have not signed the matrix. Leave false.
    */
    'insurance_matrix_signed_off' => false,

    'insurance_requirement_values' => ['required', 'conditional', 'not_required'],
    'insurance_tiers' => ['A', 'B', 'C'],

];
