<?php

namespace App\Support;

use App\Models\User;

/**
 * What to call the person on the other side of a proposal, and whether there
 * is a profile page to send the client to.
 *
 * Sir Peter, 7 October: "change the red marked area from 'View Event' to
 * 'View Event Details' plus this other one... 'View profile' to 'View
 * Professional Profile' (unless its an 'Influencer')."
 *
 * The "unless" is the whole reason this is a class and not four edited
 * strings. The word has to come from the account, and four menus across the
 * client's pages had drifted into saying it four ways — "View profile",
 * "View professional", "Message professional" — each hard-coded to the
 * commoner case.
 *
 * It also answers where the link goes, because those are the same question.
 * /pro/{user} refuses anyone without the professional role, so a menu item
 * reading "View influencer profile" and pointing there is a 404 with a
 * reassuring name. Today there is no public influencer profile at all, so
 * url() returns null for one and the caller leaves the item out; the label
 * is written so it needs no second edit if that page is ever built.
 */
final class SupplierProfile
{
    /**
     * The account's own role, settled by its column wherever possible.
     *
     * These labels are drawn once per row of a list. RoleColours::accountRole
     * asks whether the account is an admin before it reads anything, and that
     * is a roles query for every supplier on the page — so the column it would
     * reach for anyway is read first, and the resolver is kept for accounts
     * that never had one set.
     */
    private static function roleOf(?User $user): string
    {
        if (! $user) {
            return 'client';
        }

        $role = strtolower(trim((string) $user->primary_role));

        return $role !== '' ? $role : RoleColours::accountRole($user);
    }

    /** "Professional" or "Influencer", for a sentence being built around it. */
    public static function noun(?User $user): string
    {
        return self::roleOf($user) === 'influencer' ? 'influencer' : 'professional';
    }

    /** The menu item: "View professional profile" / "View influencer profile". */
    public static function viewLabel(?User $user): string
    {
        return 'View ' . self::noun($user) . ' profile';
    }

    /** "Message professional" / "Message influencer". */
    public static function messageLabel(?User $user): string
    {
        return 'Message ' . self::noun($user);
    }

    /**
     * The public profile page, or null when the account has none.
     *
     * The test is the same one ProfessionalProfileShowController makes, so
     * the link and the page it points at cannot disagree.
     */
    public static function url(?User $user): ?string
    {
        // noun() falls back to "professional" so a label always reads; the
        // link must not. An account that is neither — a client who somehow
        // appears as a supplier, an admin — has no page either.
        if (! $user || self::roleOf($user) !== 'professional') {
            return null;
        }

        return route('public.professional.show', $user->id);
    }
}
