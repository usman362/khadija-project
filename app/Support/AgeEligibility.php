<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Rule R62 — every Client and Professional account holder must be 18+.
 *
 * Three boundaries the rule draws, worth keeping straight:
 *
 *   It is an ACCOUNT-HOLDER rule, not a guest-list rule. Minors may attend an
 *   event booked through the platform (R55) — nothing here touches attendees.
 *
 *   Influencers are OUT of scope. R24 governs their age separately, and R62
 *   was written as a new rule precisely so R24's Influencer-only scope stays
 *   clean. Widening this one to cover them would undo that.
 *
 *   Enforcement is SERVER-SIDE authoritative, to R38's standard. The form
 *   field is a convenience; `isEligible()` is the answer.
 *
 * On accounts that predate the rule: a missing date of birth is UNKNOWN, not
 * eligible and not ineligible. Treating it as a failure would lock out every
 * account that already exists, and treating it as a pass would make the rule
 * decorative — so it is its own state, and the gate that needs certainty asks
 * for `isEligible()` rather than `! isUnderage()`.
 */
final class AgeEligibility
{
    public const MINIMUM_AGE = 18;

    /** Roles the rule covers. Influencers are governed by R24 instead. */
    public const GOVERNED_ROLES = ['client', 'professional'];

    public static function appliesTo(?string $role): bool
    {
        return in_array($role, self::GOVERNED_ROLES, true);
    }

    /** The latest date of birth that is still 18 today. */
    public static function latestEligibleBirthdate(): Carbon
    {
        return Carbon::today()->subYears(self::MINIMUM_AGE);
    }

    /** Old enough, from a date of birth. Null is unknown, so not a pass. */
    public static function isOldEnough(CarbonInterface|string|null $dob): bool
    {
        if ($dob === null || $dob === '') {
            return false;
        }

        $dob = $dob instanceof CarbonInterface ? $dob : Carbon::parse($dob);

        return $dob->lessThanOrEqualTo(self::latestEligibleBirthdate());
    }

    /**
     * May this user hold and transact on their account, as far as R62 is
     * concerned? Anyone the rule does not govern passes trivially.
     */
    public static function isEligible(?User $user): bool
    {
        if ($user === null || ! self::appliesTo($user->primary_role)) {
            return true;
        }

        return self::isOldEnough($user->profile?->date_of_birth);
    }

    /**
     * Known to be under age — a different question from "not known to be over
     * it". Only this one is grounds for refusing an existing account.
     */
    public static function isUnderage(?User $user): bool
    {
        if ($user === null || ! self::appliesTo($user->primary_role)) {
            return false;
        }

        $dob = $user->profile?->date_of_birth;

        return $dob !== null && ! self::isOldEnough($dob);
    }

    /** `supported` / `unknown` / `underage`, for an admin list or a banner. */
    public static function statusFor(?User $user): string
    {
        if ($user === null || ! self::appliesTo($user->primary_role)) {
            return 'not_applicable';
        }

        if ($user->profile?->date_of_birth === null) {
            return 'unknown';
        }

        return self::isOldEnough($user->profile->date_of_birth) ? 'eligible' : 'underage';
    }
}
