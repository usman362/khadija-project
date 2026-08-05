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
     * @return Collection<int, array{title:string, suite:?string, purpose:?string, included:bool}>
     */
    public static function table(string $tier, string $audience): Collection
    {
        return self::toolsFor($audience)->map(fn (array $tool) => [
            'title'    => $name = $tool['name'] ?? '',
            'route'    => $tool['route'] ?? null,
            'purpose'  => $tool['purpose'] ?? null,
            'included' => self::unlocks($tier, $name, $audience),
        ]);
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
}
