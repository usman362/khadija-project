<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Live category tree
    |--------------------------------------------------------------------------
    |
    | Which category tree the site reads. Two exist side by side in the same
    | table:
    |
    |   v1  the 360-row tree imported from the old live site
    |   v2  Sir Peter's rebuild — 106 Event Types, 27 Service Categories,
    |       241 Services (source: Category Taxonomy V2, 2026-08-02)
    |
    | Every Category query is filtered to this version by a global scope, so
    | pages that never call ->active() are covered too. Switching this is what
    | takes v2 live; import it first with `php artisan taxonomy:import-v2`.
    |
    | Do not switch until the professional and event links have been re-homed
    | onto v2 rows — `taxonomy:switch` checks that for you and refuses if they
    | would be orphaned.
    |
    */

    'version' => env('TAXONOMY_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Tiers
    |--------------------------------------------------------------------------
    |
    | 'relevance' — how strongly a service category applies to an archetype.
    | 'popularity' — how commonly a service itself is booked.
    |
    */

    'relevance_tiers'  => ['Essential', 'Common', 'Occasional'],
    'popularity_tiers' => ['Essential', 'Popular', 'Niche'],

];
