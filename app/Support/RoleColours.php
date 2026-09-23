<?php

namespace App\Support;

use App\Models\User;

/**
 * One colour per account type, set in stone (OA-164, Sir Peter 2026-09-23).
 *
 * The values are his, and the older ones they replace (#F97316, #2563EB,
 * #7C3AED, #F3E8FF, #EDE9FE, #5B21B6) must not appear anywhere a role is
 * being coloured. Anything that tints by role reads them from here, so a
 * change of mind is one edit rather than a hunt through the views.
 */
final class RoleColours
{
    /** role => [light tint, strong colour] */
    public const ROLES = [
        'client'       => ['#FFF7ED', '#EA580C'],
        'professional' => ['#EFF6FF', '#1D4ED8'],
        'influencer'   => ['#F5F3FF', '#6D28D9'],
        'admin'        => ['#F0FDF4', '#15803D'],
        'affiliate'    => ['#FDF2F8', '#DB2777'],
    ];

    /**
     * The account type this user reads as, for colour only.
     *
     * Professional mode lives in the signed-in person's session, so it may
     * only change their own colour. Asking it about somebody else mixed the
     * viewer's session into the other person's row: a professional who also
     * holds a client role came out orange and badged "Client" next to their
     * own blue avatar, because the mode being read was never theirs.
     */
    public static function roleOf(?User $user): string
    {
        if (! $user) {
            return 'client';
        }

        if (auth()->id() === $user->id && $user->hasRole('professional') && $user->isProfessionalMode()) {
            return 'professional';
        }

        return self::accountRole($user);
    }

    /**
     * The role the account registered as, without asking the database.
     *
     * roleOf() reads the assigned roles, which is right when the colour has to
     * follow what someone is doing right now — a client in professional mode
     * works in blue. An avatar is drawn for every person in a list, and asking
     * for each one's roles there is a query per row for an answer the account
     * already carries, the same one its CL- / PRO- reference is built from.
     */
    public static function accountRole(?User $user): string
    {
        if (! $user) {
            return 'client';
        }

        // Admin is system-controlled and outranks whatever the account
        // registered as, so it is settled before primary_role is read.
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        $role = strtolower(trim((string) $user->primary_role));

        if (isset(self::ROLES[$role])) {
            return $role;
        }

        return match (true) {
            $user->hasRole('influencer') => 'influencer',
            $user->hasRole('professional') => 'professional',
            default => 'client',
        };
    }

    /** The strong colour: bubbles, buttons, the accent on a row. */
    public static function strong(?User $user): string
    {
        return self::ROLES[self::roleOf($user)][1];
    }

    /** The light tint behind a card or a chip. */
    public static function tint(?User $user): string
    {
        return self::ROLES[self::roleOf($user)][0];
    }

    /** By name, for a row that is about somebody else. */
    public static function strongFor(string $role): string
    {
        return self::ROLES[$role][1] ?? self::ROLES['client'][1];
    }

    public static function tintFor(string $role): string
    {
        return self::ROLES[$role][0] ?? self::ROLES['client'][0];
    }
}
