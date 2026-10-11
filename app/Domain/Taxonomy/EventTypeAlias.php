<?php

namespace App\Domain\Taxonomy;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * A bridge between the two event-type lists, because they are not one list.
 *
 * `config/event-types.php` holds the 47 occasions a client can pick from on
 * Post an Event, and the navbar, footer and landing pages read the same file.
 * The `categories` table holds 106 rows of `kind = event_type`, and that is
 * what carries the archetype, the service coverage and Peter's relevance
 * matrix. Only 21 names appear in both.
 *
 * So for 26 of the 47 occasions a client can choose, the service picker looked
 * up the event by name, found nothing, and fell back to an unsorted wall of
 * services with none of the level 1 to level 3 guidance behind it. Sir Peter
 * reported it as "ANY searches needing the L1-L3 have the data ... i seen
 * where the data is not full there", and he was right.
 *
 * This maps the wording in the config list onto the wording in the table. It
 * does NOT guess. An earlier attempt at fuzzy matching paired "New Year Party"
 * with "Chinese New Year Party" and "Graduation" with "Bootcamp Graduation",
 * which is worse than showing nothing: the client would have been handed the
 * wrong event's services and never known. Every pair below was read and
 * chosen by hand.
 *
 * It is a bridge, not a cure. The cure is one list, and which list wins is
 * Peter's call. Until he makes it, `unresolved()` names the occasions that
 * still have no event behind them, and a test holds that list still so the
 * gap cannot quietly grow.
 */
final class EventTypeAlias
{
    /**
     * The config list's wording => the categories table's wording.
     *
     * Lowercased on both sides, because every key the picker and the relevance
     * matrix use is lowercased.
     */
    private const ALIASES = [
        'anniversary'                   => 'anniversary party',
        'bachelor / bachelorette party' => 'bachelor party',
        'bar / bat mitzvah'             => 'bar/bat mitzvah',
        'conference'                    => 'business conference',
        'graduation'                    => 'graduation party',
        'housewarming'                  => 'housewarming party',
        'new year party'                => "new year's eve party",
        'prom'                          => 'prom night',
        'seminar'                       => 'workshop / seminar',
        'sports event'                  => 'sporting event / watch party',
        'sweet sixteen'                 => 'sweet 16',
        'workshop'                      => 'workshop / seminar',
    ];

    /**
     * Deliberately left alone.
     *
     * Each of these is either an occasion the table has no row for at all, or
     * one where the table offers several and none of them is the obvious
     * answer: "Festival" could be the food truck one or the music one, and
     * "Reunion" could be college, family or high school. Choosing for the
     * client would be a guess wearing a straight face, so the picker simply
     * sorts A to Z and says nothing about relevance.
     */
    private const NO_EVENT_BEHIND_THEM = [
        'art exhibition',
        'comedy show',
        'concert',
        'fashion show',
        'festival',
        'gala',
        'live performance',
        'memorial / celebration of life',
        'networking event',
        'pop-up event',
        'quinceañera',
        'reunion',
        'school event',
        'team building',
    ];

    /** The table's name for an occasion, or the occasion unchanged. */
    public static function resolve(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return $name;
        }

        return self::ALIASES[mb_strtolower(trim($name))] ?? $name;
    }

    /**
     * The pairs, for the picker's own script.
     *
     * The live picker changes the event without a reload, so the browser needs
     * the same answer the server would have given.
     */
    public static function map(): array
    {
        return self::ALIASES;
    }

    /**
     * Occasions a client can pick that no event type stands behind.
     *
     * Read from the config list against the table rather than written down, so
     * that adding a row to either one is reflected here without anybody
     * remembering to come back.
     */
    public static function unresolved(): array
    {
        return Cache::rememberForever('taxonomy.event_types_unresolved', function () {
            $known = Category::query()
                ->where('kind', Category::EVENT_TYPE)
                ->pluck('name')
                ->map(fn ($n) => mb_strtolower($n))
                ->flip();

            return collect(config('event-types.types', []))
                ->reject(fn ($t) => $known->has(mb_strtolower(self::resolve($t) ?? '')))
                ->values()
                ->all();
        });
    }

    /** The ones above that are a known gap rather than a new one. */
    public static function knownGaps(): array
    {
        return self::NO_EVENT_BEHIND_THEM;
    }
}
