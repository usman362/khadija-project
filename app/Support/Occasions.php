<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Maps a browse-by-occasion label (Weddings, Corporate Events, Catering Events…)
 * onto real Package rows. Packages rarely have the optional `event_types` tag
 * set, so we also match the occasion against the package title, its category
 * name, and its structured `services` — giving real, non-empty results and
 * counts today, while still honouring `event_types` when a pro does set it.
 */
class Occasions
{
    /** label => ['like' => title/category substrings, 'services' => exact service names] */
    public const MAP = [
        'Weddings'            => ['like' => ['wedding', 'bridal'],                                 'services' => []],
        'Wedding Styling'     => ['like' => ['wedding', 'reception', 'styling'],                   'services' => ['Decor & Design']],
        'Engagement Parties'  => ['like' => ['engage'],                                            'services' => []],
        'Anniversaries'       => ['like' => ['anniversar'],                                        'services' => []],
        'Corporate Events'    => ['like' => ['corporate', 'conference', 'gala', 'business'],       'services' => []],
        'Birthday Parties'    => ['like' => ['birthday'],                                          'services' => []],
        'Baby Showers'        => ['like' => ['baby', 'shower'],                                     'services' => []],
        'Graduations'         => ['like' => ['graduation', 'school', 'prom'],                       'services' => []],
        'Holiday Parties'     => ['like' => ['holiday', 'christmas', 'festive'],                    'services' => []],
        'Festivals & Fairs'   => ['like' => ['festival', 'fair'],                                   'services' => []],
        'Catering Events'     => ['like' => ['cater', 'buffet', 'food'],                            'services' => ['Catering / Food']],
        'Floral Design'       => ['like' => ['floral', 'flower', 'bouquet'],                        'services' => ['Floral Design']],
        'Event Planning'      => ['like' => ['planning', 'coordinat'],                              'services' => ['Planning / Coordination']],
        'Venue & Decor'       => ['like' => ['venue', 'decor'],                                     'services' => ['Decor & Design']],
        'Cakes & Desserts'    => ['like' => ['cake', 'dessert', 'bakery'],                          'services' => []],
        'Balloon Styling'     => ['like' => ['balloon'],                                            'services' => ['Decor & Design']],
        'Entertainment'       => ['like' => ['dj', 'entertain', 'band', 'music', 'performer'],      'services' => ['DJ / Entertainment']],
    ];

    /** Constrain a Package query to those matching the given occasion label. */
    public static function apply(Builder $query, string $label): Builder
    {
        $m = self::MAP[$label] ?? null;

        return $query->where(function (Builder $w) use ($label, $m) {
            $w->whereJsonContains('event_types', $label);
            foreach (($m['like'] ?? []) as $kw) {
                $w->orWhere('title', 'like', "%{$kw}%")
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$kw}%"));
            }
            foreach (($m['services'] ?? []) as $svc) {
                $w->orWhereJsonContains('services', $svc);
            }
        });
    }

    /** Is this a label we know how to match? (avoids empty filters for unknowns) */
    public static function known(string $label): bool
    {
        return isset(self::MAP[$label]);
    }
}
