<?php

namespace App\Domain\AiFeatures\Services;

use App\Models\AiFeatureUsage;
use App\Models\User;
use RuntimeException;

/**
 * Gatekeeper for plan-gated AI features.
 *
 * Responsibilities:
 *  - Verify the user's active plan includes a given feature code
 *  - Enforce monthly quotas (0 = unlimited)
 *  - Record usage for auditing and quota counting
 */
class AiFeatureGate
{
    /**
     * Ensure user can use the feature; throw with a clean message if not.
     */
    public function authorize(User $user, string $featureCode): void
    {
        $access = $user->aiFeatureAccess($featureCode);

        if (!$access['enabled']) {
            throw new RuntimeException('This feature is not included in your current plan. Please upgrade to unlock it.');
        }

        if ($access['quota'] > 0) {
            $remaining = $user->aiFeatureRemaining($featureCode);
            if ($remaining <= 0) {
                throw new RuntimeException("You've reached your monthly limit for this feature. Resets on the 1st of next month.");
            }
        }
    }

    /**
     * Record a successful feature usage (counts toward quota).
     */
    public function recordUsage(User $user, string $featureCode, int $tokensUsed = 0, array $metadata = []): AiFeatureUsage
    {
        return AiFeatureUsage::create([
            'user_id'      => $user->id,
            'feature_code' => $featureCode,
            'tokens_used'  => $tokensUsed,
            'metadata'     => $metadata ?: null,
            'created_at'   => now(),
        ]);
    }

    /**
     * Quick status summary for UI (used in views to show badges, counters).
     */
    public function status(User $user, string $featureCode): array
    {
        $access    = $user->aiFeatureAccess($featureCode);
        $usage     = $user->aiFeatureUsageThisMonth($featureCode);
        $remaining = $user->aiFeatureRemaining($featureCode);

        return [
            'enabled'   => $access['enabled'],
            'quota'     => $access['quota'],
            'used'      => $usage,
            'remaining' => $remaining,
            'unlimited' => $access['enabled'] && $access['quota'] === 0,
        ];
    }
}
