<?php

/*
|--------------------------------------------------------------------------
| Geographic Restriction (Developer Feedback v1.1 §7.1)
|--------------------------------------------------------------------------
| The platform launches in 7 states. Professionals pick from a hardcoded
| state dropdown; clients are validated by zip code. Both checks are free
| (no API call) — the Layer-1 filter before any paid verification runs.
|
| NOTE: built with array_replace (NOT spread) — spreading renumbers
| integer keys, and "100".."219" prefixes become int keys in PHP.
| Prefixes below "100" keep their leading zero and stay string keys,
| which matches substr($zip, 0, 3) lookups.
*/

$zipPrefixes = array_replace(
    // New Jersey — 070–089 (leading zero ⇒ string keys)
    array_fill_keys(array_map(fn ($n) => str_pad((string) $n, 3, '0', STR_PAD_LEFT), range(70, 89)), 'NJ'),
    // New York — 004/005 + 100–149
    ['004' => 'NY', '005' => 'NY'],
    array_fill_keys(range(100, 149), 'NY'),
    // Pennsylvania — 150–196
    array_fill_keys(range(150, 196), 'PA'),
    // Delaware — 197–199
    array_fill_keys(range(197, 199), 'DE'),
    // Washington D.C. — 200 + 202–205
    array_fill_keys([200, 202, 203, 204, 205], 'DC'),
    // Virginia — 201 + 220–246
    [201 => 'VA'],
    array_fill_keys(range(220, 246), 'VA'),
    // Maryland — 206–219
    array_fill_keys(range(206, 219), 'MD'),
);

return [
    'allowed_states' => [
        'MD' => 'Maryland',
        'VA' => 'Virginia',
        'DC' => 'Washington D.C.',
        'DE' => 'Delaware',
        'PA' => 'Pennsylvania',
        'NJ' => 'New Jersey',
        'NY' => 'New York',
    ],

    // 3-digit ZIP prefix → state (deterministic USPS allocations).
    'zip_prefixes' => $zipPrefixes,
];
