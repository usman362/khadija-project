<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;

/**
 * Who has to show a certificate of insurance, and whether theirs still counts.
 *
 * Khadijah, 2026-08-04: required for Alcohol service, Catering, Security and
 * Pyrotechnics/Fireworks. The category names sit in config/compliance.php.
 *
 * 2026-08-19: categories.insurance_requirement / insurance_type / insurance_tier
 * are the broker's draft matrix. They are NOT read here. Flip
 * compliance.insurance_matrix_signed_off only after broker and attorney
 * sign off — until then a "Required" cell is a note, not a gate.
 */
class InsuranceRequirement
{
    /** Does this professional work in a category that requires cover? */
    public static function appliesTo(User $user): bool
    {
        return self::triggeringCategories($user) !== [];
    }

    /** The categories that put them in scope — so the notice can say why. */
    public static function triggeringCategories(User $user): array
    {
        $required = collect(config('compliance.insurance_required_categories', []))
            ->flatten()
            ->map(fn (string $name) => mb_strtolower($name));

        if ($required->isEmpty()) {
            return [];
        }

        return $user->serviceCategories
            ->filter(fn ($category) => $required->contains(mb_strtolower($category->name)))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Verified AND not expired. The old check was the verified stamp alone,
     * which never stopped being true.
     */
    public static function isCovered(?UserProfile $profile): bool
    {
        if (! $profile?->liability_insurance_verified_at) {
            return false;
        }

        $expires = $profile->liability_insurance_expires_on;

        // A certificate with no expiry on file predates this form. Treat it as
        // covered rather than retroactively marking existing professionals
        // uninsured, but the admin queue lists them for a re-submission.
        return $expires === null || Carbon::parse($expires)->endOfDay()->isFuture();
    }

    /** Verified once, but the policy has since run out. */
    public static function hasLapsed(?UserProfile $profile): bool
    {
        return (bool) $profile?->liability_insurance_verified_at
            && $profile->liability_insurance_expires_on !== null
            && ! self::isCovered($profile);
    }

    /** Days until the policy runs out; null when there is no date on file. */
    public static function daysRemaining(?UserProfile $profile): ?int
    {
        if (! $profile?->liability_insurance_expires_on) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(
            Carbon::parse($profile->liability_insurance_expires_on)->endOfDay(),
            absolute: false,
        );
    }

    /** Close enough to expiry that the professional should be told. */
    public static function isExpiringSoon(?UserProfile $profile): bool
    {
        $days = self::daysRemaining($profile);

        return $days !== null
            && $days >= 0
            && $days <= (int) config('compliance.insurance_expiry_warning_days', 30);
    }

    /**
     * One word for the whole situation, for a badge or an admin column:
     * not_required · missing · lapsed · expiring · covered
     */
    public static function statusFor(User $user): string
    {
        $profile = $user->profile;

        if (self::hasLapsed($profile)) {
            return 'lapsed';
        }

        if (! self::isCovered($profile)) {
            return self::appliesTo($user) ? 'missing' : 'not_required';
        }

        return self::isExpiringSoon($profile) ? 'expiring' : 'covered';
    }
}
