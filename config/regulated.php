<?php

/*
 * Regulated service categories (PM answers of 6 September, OA-104).
 *
 * Every category is open to bidding, and anyone can bid. In these, a client
 * can only ACCEPT a bid from a professional whose license and insurance are
 * verified (User::isVerified(): licence, liability insurance and workers'
 * comp all approved).
 *
 * Level 2 names from the locked taxonomy. "Alcohol Service" is Bar, Beverage
 * & Mixology Services. The PM also names Pyrotechnics / Fireworks, which is
 * not a category in the locked taxonomy yet; it takes effect here if one is
 * added under any of these names.
 */
return [
    'categories' => [
        'Bar, Beverage & Mixology Services',
        'Catering & Food Services',
        'Security & Crowd Management',
        'Pyrotechnics & Fireworks',
        'Pyrotechnics',
        'Fireworks',
    ],
];
