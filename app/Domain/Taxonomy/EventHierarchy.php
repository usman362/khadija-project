<?php

namespace App\Domain\Taxonomy;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * The four levels of Peter's Event Hierarchy:
 *
 *   1. Main Event               e.g. Wedding
 *   2. Main Service             e.g. Catering & Beverage
 *   3. Sub-Main Service         e.g. Guest & Attendee Experience
 *   4. Specific Service Component
 *
 * Two trees can answer this, and they answer it differently.
 *
 * v1 — the 360-row import from the old live site — is genuinely four levels of
 * parent_id: 49 main events, 49 main services, 55 sub-main services, 207
 * components. It is the tree the workflow diagram was drawn from.
 *
 * v2 — the live tree, 106 event types + 27 service categories + 241 services —
 * is two levels of parent_id. An event type is not the parent of a service
 * category there; the link is the Category Masterlist's Archetype Relevance
 * Matrix, which ranks all 27 categories for each of the 13 archetypes. And it
 * has no fourth level at all: no service has children.
 *
 * So this reads whichever tree is switched on and reports honestly how deep it
 * goes. The page draws four dropdowns either way, and a level with nothing
 * behind it says so rather than being filled with something invented — which
 * is the diagram's own closing rule: the system only shows data that exists in
 * the source.
 */
class EventHierarchy
{
    public const LEVELS = [
        1 => ['label' => 'Main Event',                'prompt' => 'Select a Main Event'],
        2 => ['label' => 'Main Service',              'prompt' => 'Select a Main Service'],
        3 => ['label' => 'Sub-Main Service',          'prompt' => 'Select a Sub-Main Service'],
        4 => ['label' => 'Specific Service Component', 'prompt' => 'Select a Specific Component'],
    ];

    /** Which tree is answering. */
    public static function version(): string
    {
        return (string) config('taxonomy.version', 'v1');
    }

    /** Level 1 — every main event, alphabetically. */
    public static function mainEvents(): Collection
    {
        return Category::query()
            ->when(self::version() === 'v2', fn ($q) => $q->where('kind', Category::EVENT_TYPE))
            ->when(self::version() !== 'v2', fn ($q) => $q->whereNull('parent_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'archetype']);
    }

    /**
     * Level 2 — the main services that belong to one main event.
     *
     * On v2 that is the relevance matrix, ordered Essential → Common →
     * Occasional. A tier is a ranking, not a permission: a category the matrix
     * calls Occasional for weddings is still a thing you can have at a wedding,
     * so it is ordered last, never hidden.
     */
    public static function mainServices(int $eventId): Collection
    {
        $event = self::find($eventId);

        if ($event === null) {
            return collect();
        }

        if (self::version() !== 'v2') {
            return self::childrenOf($event->id);
        }

        $tiers = ServiceRelevance::tiersByArchetype()[$event->archetype] ?? [];

        return Category::query()
            ->where('kind', Category::SERVICE_CATEGORY)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->map(fn ($c) => tap($c, fn ($c) => $c->tier = $tiers[$c->id] ?? null))
            // Ranked, not filtered. Anything the matrix does not rank for this
            // archetype sorts after the three tiers rather than disappearing.
            ->sortBy([
                fn ($a, $b) => self::rank($a->tier) <=> self::rank($b->tier),
                fn ($a, $b) => strcasecmp($a->name, $b->name),
            ])
            ->values();
    }

    /** Level 3 — what sits under one main service. */
    public static function subServices(int $serviceId): Collection
    {
        return self::childrenOf($serviceId);
    }

    /** Level 4 — what sits under one sub-main service. Empty on v2. */
    public static function components(int $subServiceId): Collection
    {
        return self::childrenOf($subServiceId);
    }

    /** The options for one level, given what was chosen above it. */
    public static function optionsFor(int $level, ?int $parentId): Collection
    {
        return match ($level) {
            1 => self::mainEvents(),
            2 => $parentId ? self::mainServices($parentId) : collect(),
            3 => $parentId ? self::subServices($parentId) : collect(),
            4 => $parentId ? self::components($parentId) : collect(),
            default => collect(),
        };
    }

    /**
     * How many levels the live tree can actually fill.
     *
     * Reported rather than assumed, because the answer is different for the two
     * trees and the page has to be able to say so out loud.
     */
    public static function depth(): int
    {
        if (self::version() !== 'v2') {
            return 4;
        }

        // v2: event type → service category → service, and nothing under that.
        return Category::query()->where('kind', Category::SERVICE)->whereHas('children')->exists() ? 4 : 3;
    }

    private static function rank(?string $tier): int
    {
        return match ($tier) {
            'Essential'  => 0,
            'Common'     => 1,
            'Occasional' => 2,
            default      => 3,
        };
    }

    private static function childrenOf(int $parentId): Collection
    {
        return Category::query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private static function find(int $id): ?Category
    {
        return Category::query()->whereKey($id)->first(['id', 'name', 'archetype', 'parent_id']);
    }
}
