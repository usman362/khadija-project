<?php

namespace App\Domain\Taxonomy;

use App\Models\Category;
use App\Models\CategoryRelevance;
use Illuminate\Support\Facades\Cache;

/**
 * Peter's Category Masterlist, put to work.
 *
 * The masterlist (given to the PM on 2 August, imported on the 5th) carries a
 * 139-row Archetype Relevance Matrix: every one of the 106 event types belongs
 * to one of 13 archetypes, and each archetype ranks the 27 service categories
 * Essential / Common / Occasional. All of it imported correctly and then
 * nothing read it — no controller, no view, no query.
 *
 * What WAS reading was config/event-service-map.php: a hand-written keyword
 * guess covering 48 names, of which only 22 match a live event type. So for 84
 * of 106 event types the cascade did nothing at all, while the approved matrix
 * that covers all 106 sat unused.
 *
 * A tier is a RANKING, not a permission. The keyword map hid every service it
 * did not recognise; this orders them instead. Hiding "Occasional" would throw
 * away the distinction the file was written to make — a client planning a
 * wedding can still want a service that is merely occasional for weddings, and
 * the file says it is occasional, not forbidden.
 */
class ServiceRelevance
{
    public const TIERS = ['Essential', 'Common', 'Occasional'];

    /** Event type name (lowercased) => archetype. */
    public static function archetypeByEventType(): array
    {
        return Cache::rememberForever('taxonomy.archetype_by_event_type', fn () => Category::query()
            ->where('kind', Category::EVENT_TYPE)
            ->whereNotNull('archetype')
            ->pluck('archetype', 'name')
            ->mapWithKeys(fn ($a, $n) => [mb_strtolower($n) => $a])
            ->all());
    }

    /**
     * Archetype => [service category id => tier].
     *
     * Keyed by id rather than name because the picker's items carry the id of
     * the category they sit under, and names are not unique in the legacy tree.
     */
    public static function tiersByArchetype(): array
    {
        return Cache::rememberForever('taxonomy.tiers_by_archetype', function () {
            $out = [];

            foreach (CategoryRelevance::all() as $row) {
                $out[$row->archetype][(int) $row->category_id] = $row->tier;
            }

            return $out;
        });
    }

    /** Lower is more relevant; anything the matrix does not rank sorts last. */
    public static function rank(?string $tier): int
    {
        $i = array_search($tier, self::TIERS, true);

        return $i === false ? count(self::TIERS) : $i;
    }

    /** Both maps, shaped for the browser. Small: 106 + 139 entries. */
    public static function forBrowser(): array
    {
        return [
            'archetypeOf' => self::archetypeByEventType(),
            'tiers'       => self::tiersByArchetype(),
            'order'       => self::TIERS,
        ];
    }

    public static function forget(): void
    {
        Cache::forget('taxonomy.archetype_by_event_type');
        Cache::forget('taxonomy.tiers_by_archetype');
    }
}
