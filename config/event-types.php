<?php

/**
 * Canonical list of event types / occasions — the single source of truth for
 * the event-type picker (search + A–Z + Popular) used across Post an Event,
 * MSR, ESR, and the Virtual Hub brief. Keep alphabetised; mark the common
 * ones in 'popular'.
 */
return [
    // Shown first as quick-pick pills.
    'popular' => [
        'Wedding', 'Birthday Party', 'Corporate Event', 'Conference',
        'Baby Shower', 'Gala', 'Anniversary', 'Graduation',
    ],

    // Full list (A–Z). The picker groups these by first letter.
    'types' => [
        'Anniversary', 'Art Exhibition', 'Award Ceremony',
        'Baby Shower', 'Bachelor / Bachelorette Party', 'Bar / Bat Mitzvah', 'Birthday Party', 'Bridal Shower',
        'Charity Event', 'Cocktail Party', 'Comedy Show', 'Community Event', 'Concert', 'Conference', 'Corporate Event',
        'Dinner Party',
        'Engagement Party',
        'Family Reunion', 'Fashion Show', 'Festival', 'Fundraiser',
        'Gala', 'Gender Reveal', 'Graduation', 'Grand Opening',
        'Halloween Party', 'Holiday Party', 'Housewarming',
        'Live Performance',
        'Memorial / Celebration of Life',
        'Networking Event', 'New Year Party',
        'Pop-up Event', 'Product Launch', 'Prom',
        'Quinceañera',
        'Religious Ceremony', 'Retirement Party', 'Reunion',
        'School Event', 'Seminar', 'Sports Event', 'Sweet Sixteen',
        'Team Building', 'Trade Show',
        'Wedding', 'Workshop',
    ],
];
