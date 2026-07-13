<?php

namespace App\Domain\AiFeatures;

use App\Models\User;

/**
 * GigResource IQ™ — AI level resolver.
 *
 * Answers the core question of Peter's 3-layer architecture:
 *   "For THIS user and THIS tool, what AI level do they get?"
 *   → one of: 'none' | 'manual' | 'semi' | 'maximum'
 *
 * It composes the three layers:
 *   Layer 1  admin master switch  (AiToolCatalog::disabledKeys)  → 'none' if off
 *   Layer 2  membership           (config('ai-levels.plan_levels')) → unlocked levels
 *   Layer 3  tool capability      (config('ai-levels.tool_modes'))  → what the tool supports
 *
 * The returned level is the HIGHEST the user can use for that tool. Quota/usage
 * limits (the free-beta cap) are enforced separately by AiFeatureGate.
 */
final class AiAccess
{
    /** Level ranking — higher wins. */
    public const ORDER = ['none' => 0, 'manual' => 1, 'semi' => 2, 'maximum' => 3];

    /** feature_code prefix used to record + count free-beta AI actions. */
    public const BETA_ACTION_PREFIX = 'ai_action:';

    private const ALL = ['manual', 'semi', 'maximum'];

    /**
     * The AI levels a user's role/membership unlocks, independent of any tool.
     *   - admin / AI_FEATURES_FREE_FOR_ALL → all levels
     *   - acting as professional (supplier) → their plan's levels (default Manual)
     *   - clients, influencers, everyone else → all levels free (launch phase)
     */
    public static function unlockedLevels(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if (filter_var(env('AI_FEATURES_FREE_FOR_ALL', false), FILTER_VALIDATE_BOOLEAN) || $user->isAdmin()) {
            return self::ALL;
        }

        // Membership tiers only apply while the user is acting as a professional.
        if ($user->activeRole() === 'supplier') {
            $slug   = $user->activeSubscription()?->plan?->slug;
            $levels = $slug ? config("ai-levels.plan_levels.{$slug}") : null;

            return $levels ?? config('ai-levels.professional_default', ['manual']);
        }

        // Clients + influencers (and dual-role users acting as client): free at launch.
        return self::ALL;
    }

    /**
     * The effective AI level for a user on a specific tool.
     * Returns 'none' when the admin disabled the tool or the user's plan
     * doesn't reach any level the tool offers.
     */
    public static function level(?User $user, string $toolKey): string
    {
        if (in_array($toolKey, AiToolCatalog::disabledKeys(), true)) {
            return 'none';
        }

        $supported = config("ai-levels.tool_modes.{$toolKey}", self::ALL);
        $available = array_values(array_intersect($supported, self::unlockedLevels($user)));

        if (empty($available)) {
            return 'none';
        }

        usort($available, fn ($a, $b) => self::ORDER[$b] <=> self::ORDER[$a]);

        return $available[0];
    }

    /** Does the user reach at least $minLevel on this tool? */
    public static function can(?User $user, string $toolKey, string $minLevel = 'manual'): bool
    {
        return self::ORDER[self::level($user, $toolKey)] >= (self::ORDER[$minLevel] ?? 99);
    }

    /** Display label for a level, e.g. 'semi' → 'Semi-Assisted'. */
    public static function label(string $level): string
    {
        return config("ai-levels.labels.{$level}", ucfirst($level));
    }

    // ── Phase 4: free-beta usage cap ───────────────────────────────────────

    /** The monthly free-beta action cap (0 = disabled). */
    public static function freeBetaCap(): int
    {
        return (int) config('ai-levels.free_beta.monthly_actions', 0);
    }

    /**
     * Whether the free-beta monthly cap applies to this user.
     * It covers exactly the users who get every level free at launch —
     * clients & influencers acting outside the professional portal — while
     * professionals (their own per-plan quotas) and admins are exempt.
     */
    public static function isFreeBetaUser(?User $user): bool
    {
        if (! $user || $user->isAdmin()) {
            return false;
        }

        return $user->activeRole() !== 'supplier';
    }

    // ── GigResource IQ™ credit economy ─────────────────────────────────────

    /** Master switch for the visible AI-credit metering. */
    public static function creditsEnabled(): bool
    {
        return (bool) config('ai-levels.credits.enabled', false);
    }

    /** Credit cost of one action on a tool (weighted; unlisted = standard). */
    public static function creditCost(string $toolKey): int
    {
        $weights = (array) config('ai-levels.credits.weights', []);
        $class   = (string) config("ai-levels.credits.tool_weight.{$toolKey}", 'standard');

        return (int) ($weights[$class] ?? $weights['standard'] ?? 2);
    }

    /**
     * The user's monthly AI-credit grant. Admins are effectively unlimited;
     * professionals get their plan's grant; clients & influencers get the
     * free-role grant during beta.
     */
    public static function monthlyCreditGrant(?User $user): int
    {
        if (! $user) {
            return 0;
        }
        if ($user->isAdmin()) {
            return PHP_INT_MAX;
        }

        if ($user->activeRole() === 'supplier') {
            $slug   = $user->activeSubscription()?->plan?->slug;
            $grants = (array) config('ai-levels.credits.plan_grants', []);

            return (int) ($grants[$slug] ?? config('ai-levels.credits.professional_default_grant', 0));
        }

        return (int) config('ai-levels.credits.free_role_grant', 0);
    }
}
