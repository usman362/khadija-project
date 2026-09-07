<?php

namespace App\Support;

use App\Models\UserProfile;

/**
 * Whether a professional may be shown as verified — one answer, one place.
 *
 * ISSUE-1, Khadijah, 2026-09-03, marked CRITICAL: "the 'License Verified'
 * badge is live with no real verification behind it".
 *
 * She was right about what a client sees, and it is worth stating exactly what
 * was found, because it changes the fix. The verification mechanism is real: a
 * professional uploads a document, it lands in the admin queue, a person
 * approves it, and the timestamp is stamped. Nothing about that is fake.
 *
 * What was fake was every badge on the site. All ten verified profiles were
 * demo accounts, none of them had uploaded a document, and a seeder had
 * stamped the timestamp directly. So the badge was telling the truth about a
 * column and lying about a professional.
 *
 * Removing the badge would have thrown away a working feature to fix bad data.
 * Instead the claim now requires the evidence behind it: a document AND an
 * approval. A stamp with no document cannot produce a badge, so a seeder — or
 * a stray UPDATE — can never again put a verification on screen that nobody
 * performed.
 *
 * If the Owner still wants the badge gone entirely, this class is the single
 * place to make it say false.
 */
class VerifiedBadge
{
    /**
     * The badges a professional can hold, each with its own document.
     *
     * Same list the admin verification queue works from — kept in step with it
     * deliberately, because a badge the queue cannot grant is a badge that can
     * only arrive by accident.
     */
    public const BADGES = ['trade_license', 'workers_comp'];

    /**
     * May this profile be shown as licence-verified?
     *
     * Both halves are required. The approval alone is what was on screen, and
     * it is the half a seeder can write.
     */
    public static function licenceVerified(?UserProfile $profile): bool
    {
        return self::holds($profile, 'trade_license');
    }

    /** The same question for any badge in the list. */
    public static function holds(?UserProfile $profile, string $badge): bool
    {
        if (! $profile || ! in_array($badge, self::BADGES, true)) {
            return false;
        }

        return filled($profile->{$badge.'_doc'})
            && filled($profile->{$badge.'_verified_at'});
    }

    /**
     * The SQL form, for list pages that filter or sort on it.
     *
     * Written out rather than left to each caller: the browse page, the
     * category page and the professional search each had their own version of
     * "verified", and a client who filtered by verified and then opened a
     * profile without the badge would be right to distrust both screens.
     */
    public static function scopeSql(string $badge = 'trade_license'): string
    {
        return "({$badge}_doc IS NOT NULL AND {$badge}_verified_at IS NOT NULL)";
    }
}
