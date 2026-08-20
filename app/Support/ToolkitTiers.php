<?php

namespace App\Support;

use App\Domain\AiFeatures\AiToolCatalog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which of the 12 toolkit tools each paid tier unlocks (Rule R31), settled by
 * Peter on 2026-08-05.
 *
 * A tier is a tool SUBSET, not an automation depth — that second thing is
 * config/ai-levels.php and answers a different question.
 *
 * Three rules do most of the work:
 *   Manual   unlocks nothing, always, on both sides — a preset, not a list.
 *   Semi     unlocks six chosen tools.
 *   Maximum  unlocks everything on that side; it is not a curated set.
 */
class ToolkitTiers
{
    public const CLIENT = 'client';
    public const PROFESSIONAL = 'professional';

    /** The 12 tools one side sees — its own, plus the 4 shared ones. */
    public static function toolsFor(string $audience): Collection
    {
        return collect(AiToolCatalog::all())
            ->filter(fn (array $tool) => in_array($tool['audience'] ?? null, [$audience, 'both'], true))
            ->values();
    }

    /** Does this tier unlock this tool? */
    public static function unlocks(string $tier, string $toolName, string $audience): bool
    {
        if ($tier === 'manual') {
            return ! config('toolkit-tiers.manual_unlocks_nothing', true);
        }

        if ($tier === 'maximum') {
            return (bool) config('toolkit-tiers.maximum_unlocks_everything', true);
        }

        return in_array($toolName, config("toolkit-tiers.semi_tools.{$audience}", []), true);
    }

    /**
     * One row per tool for the given tier — what the tab table renders.
     *
     * Included tools come first, then the rest (Peter, 2026-08-07): the tab
     * answers "what am I buying", so the answer should not be scattered
     * through twelve rows. PHP's sort is stable, so catalog order survives
     * inside each of the two groups.
     *
     * @return Collection<int, array{title:string, suite:?string, purpose:?string, included:bool}>
     */
    public static function table(string $tier, string $audience): Collection
    {
        return self::toolsFor($audience)
            ->map(fn (array $tool) => [
                'title'    => $name = $tool['name'] ?? '',
                'route'    => $tool['route'] ?? null,
                'purpose'  => $tool['purpose'] ?? null,
                'included' => self::unlocks($tier, $name, $audience),
            ])
            ->sortByDesc('included')
            ->values();
    }

    /** What one tier costs, as a one-time purchase. */
    public static function price(string $tier): float
    {
        return (float) config("toolkit-tiers.prices.{$tier}", 0.0);
    }

    /**
     * The tiers this user may actually buy.
     *
     * Professionals are gated by membership: Starter gets Manual only, the mid
     * tier gets Semi with an upgrade to Maximum, and Elite is offered Maximum
     * only — the top membership has nothing lower to choose from. Clients have
     * no membership, so both tiers are open to them.
     */
    public static function purchasableBy(?User $user): array
    {
        if ($user === null || $user->activeRole() !== 'professional') {
            return ['manual', 'semi', 'maximum'];
        }

        $plan = $user->activeSubscription()?->plan?->slug;

        return config("toolkit-tiers.purchasable_by_plan.{$plan}")
            ?? config('toolkit-tiers.purchasable_by_plan.starter', ['manual']);
    }

    /** How many tools each tier unlocks — for the summary line under the table. */
    public static function countFor(string $tier, string $audience): int
    {
        return self::table($tier, $audience)->where('included', true)->count();
    }

    /**
     * The comparison table, grouped by the GigResource IQ suite each tool
     * belongs to — one row per tool, one column per tier.
     *
     * The suites come from AiToolCatalog rather than a second list here. A
     * tool's suite is a fact about the tool, and two lists of it would drift.
     *
     * @return Collection<int, array{key:string, name:string, emoji:string, tagline:string, tools:array}>
     */
    public static function comparison(string $audience): Collection
    {
        $tiers = array_keys(config('toolkit-tiers.tiers', []));

        $rows = self::toolsFor($audience)->map(fn (array $tool) => [
            'key'     => $tool['key'] ?? '',
            'name'    => $name = $tool['name'] ?? '',
            'purpose' => $tool['purpose'] ?? null,
            'suite'   => AiToolCatalog::suiteOf($tool['key'] ?? ''),
            'tiers'   => collect($tiers)->mapWithKeys(
                fn ($tier) => [$tier => self::unlocks($tier, $name, $audience)]
            )->all(),
        ]);

        return collect(AiToolCatalog::suites())
            ->map(fn (array $meta, string $key) => [
                'key'     => $key,
                'name'    => $meta['name'],
                'emoji'   => $meta['emoji'],
                'tagline' => $meta['tagline'],
                'tools'   => $rows->where('suite', $key)->values()->all(),
            ])
            // A suite with none of this audience's tools in it is a heading
            // with nothing under it.
            ->filter(fn (array $suite) => $suite['tools'] !== [])
            ->values();
    }

    /**
     * The tools a tier adds ON TOP of the one below it — what the card lists.
     *
     * Maximum's card says "everything in Semi, plus N more", so it must show
     * the difference rather than all twelve; listing the Semi five again would
     * make the two cards look like they overlap by accident.
     *
     * @return Collection<int, array>
     */
    public static function toolsAddedBy(string $tier, string $audience): Collection
    {
        $below = match ($tier) {
            'maximum' => 'semi',
            'semi'    => 'manual',
            default   => null,
        };

        return self::toolsFor($audience)->filter(function (array $tool) use ($tier, $below, $audience) {
            $name = $tool['name'] ?? '';

            return self::unlocks($tier, $name, $audience)
                && ! ($below !== null && self::unlocks($below, $name, $audience));
        })->values();
    }

    /**
     * What upgrading from Semi to Maximum costs.
     *
     * The difference, not the full price: config says the Semi payment is
     * credited, and quoting $5.99 to somebody who has already paid $2.99 would
     * be quoting them the wrong number.
     */
    public static function upgradeDifference(): float
    {
        return round(max(0, self::price('maximum') - self::price('semi')), 2);
    }
}
