<?php

namespace App\Support;

use App\Models\User;

/**
 * Who can reach a conversation which way.
 *
 * Sir Peter's Idea 3: the pop-up messenger can be a benefit of the highest
 * professional membership — but the messages themselves must not fork. So this
 * decides display and access only. Every conversation, every message and every
 * endpoint behind them is the same for everybody; what changes is whether the
 * dock is on the page.
 *
 * A client never buys anything to answer a professional who has it. That was
 * the Owner's explicit condition, and it is why role comes before plan here.
 */
final class MessengerAccess
{
    public static function dock(?User $user): bool
    {
        return self::allowed($user, 'dock');
    }

    public static function panel(?User $user): bool
    {
        return self::allowed($user, 'panel');
    }

    private static function allowed(?User $user, string $feature): bool
    {
        if (! $user || ! config("messaging.{$feature}.enabled", true)) {
            return false;
        }

        // Roles that are never charged for it.
        if (in_array(self::roleOf($user), (array) config('messaging.free_roles', []), true)) {
            return true;
        }

        $plans = (array) config("messaging.{$feature}.plans", []);

        // No list configured means every professional, so the feature can be
        // seen before it is put behind a tier.
        if ($plans === []) {
            return true;
        }

        return in_array(self::planOf($user), $plans, true);
    }

    /** What this account is acting as right now, not what it once registered as. */
    private static function roleOf(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin';
        }

        return $user->activeRole() ?? $user->primary_role ?? 'client';
    }

    private static function planOf(User $user): ?string
    {
        return $user->activeSubscription()?->plan?->slug;
    }
}
