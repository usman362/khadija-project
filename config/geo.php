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
    // Pennsylvania — 150–196
    array_fill_keys(range(150, 196), 'PA'),
    // Delaware — 197–199
    array_fill_keys(range(197, 199), 'DE'),
    // Washington D.C. — 200 + 202–205
    array_fill_keys([200, 202, 203, 204, 205], 'DC'),
    // Virginia — 201 + 220–246
    [201 => 'VA'],
    array_fill_keys(range(220, 246), 'VA'),
    // West Virginia — 247–268
    array_fill_keys(range(247, 268), 'WV'),
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
        'WV' => 'West Virginia',
    ],

    // 3-digit ZIP prefix → state (deterministic USPS allocations).
    'zip_prefixes' => $zipPrefixes,

    /*
    |--------------------------------------------------------------------------
    | Every state the registration form offers (Peter, 2026-07-30)
    |--------------------------------------------------------------------------
    | Registration no longer limits the dropdown to the launch area, and never
    | tells the visitor which states those are. Anyone may register; whether we
    | operate where they live is worked out afterwards and shown on the
    | post-registration screen.
    |
    | `allowed_states` above stays the single source of truth for that decision
    | — it is just no longer used to build the form.
    */
    'us_states' => [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'DC' => 'Washington D.C.', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
        'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
        'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
        'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
        'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
        'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
        'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
        'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
        'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        'PR' => 'Puerto Rico', 'VI' => 'U.S. Virgin Islands', 'GU' => 'Guam',
    ],

    // Countries offered at registration. US first because that is where the
    // launch area is; the rest register as "coming soon" like anywhere else.
    'countries' => [
        'US' => 'United States',
        'CA' => 'Canada',
        'GB' => 'United Kingdom',
        'AU' => 'Australia',
        'OTHER' => 'Other',
    ],

    /*
    | Dialling codes offered beside the phone field at registration.
    |
    | The form used to print a fixed "🇺🇸 +1" in a <div> — it looked like a
    | dropdown but could not be changed, so somebody registering from the UK
    | typed their number next to the wrong code and it was stored that way.
    | These cover the countries above; "Other" is why the list ends open.
    */
    'dial_codes' => [
        '+1'  => '🇺🇸🇨🇦 +1',   // Canada shares +1; one flag would be wrong for half of them
        '+44' => '🇬🇧 +44',
        '+61' => '🇦🇺 +61',
        '+92' => '🇵🇰 +92',
        '+91' => '🇮🇳 +91',
        '+971' => '🇦🇪 +971',
    ],

    /*
    | Radius matching (Q2 Option B, Q6 geodesic). A Professional without a
    | placed Service Origin keeps today's same-state list until they save one.
    */
    'radius_matching' => env('GEO_RADIUS_MATCHING', true),

    /*
    | Home-state matching. OFF since 2026-08-31.
    |
    | Sir Peter: "what matters is whether a professional can get to the venue —
    | not which state their business address is in. A photographer based in New
    | Jersey shooting a wedding in Philadelphia is standard practice. Home-state
    | matching would arbitrarily block that."
    |
    | Distance from the event decides — ONCE THERE IS A DISTANCE TO MEASURE.
    |
    | It is still ON, and that is deliberate. Checked on 2026-08-31: of 17
    | professionals, ZERO have placed a service origin or set a travel radius,
    | and no client has coordinates. With nothing to measure:
    |
    |   - turning this off removes the only filter there is, and a Maryland
    |     client browses professionals across the country who cannot reach them
    |   - turning radius matching on in its place matches NOBODY, because
    |     RadiusMatching refuses a professional whose origin is not placed
    |
    | Neither is what was asked for. The order has to be: professionals set a
    | service origin and a travel radius, that is backfilled, and then this
    | becomes GEO_STATE_MATCHING=false — one environment variable, no rewrite.
    |
    | The rule also stays as a switch rather than being deleted because a
    | service category that one day needs in-state licensing is a compliance
    | flag on THAT category, not a reason to rebuild this.
    */
    'state_matching' => env('GEO_STATE_MATCHING', true),

    // census = street geocode on save (free). none = ZIP table only (tests).
    'geocoder' => env('GEOCODER_DRIVER', 'census'),

    // Q7-A proposed cutoff until the PM stamps a different number.
    'zip_max_land_sq_mi' => (int) env('GEO_ZIP_MAX_SQ_MI', 150),

    // Q8 approved 5 as an admin setting; the live homepage is 2 (Khadijah
    // 2026-08-13). Default stays 2 so the page does not jump; admin can
    // raise it via directory.city_min_professionals without a deploy.
    'directory_city_min' => (int) env('GEO_DIRECTORY_CITY_MIN', 2),
];
