<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ISSUE-1 — clear every verification that has no document behind it.
 *
 * Khadijah, 2026-09-03, CRITICAL: the "License Verified" badge is live with no
 * real verification behind it. The mechanism was fine — a professional uploads
 * a document, an admin approves it in the verification queue — but all ten
 * verified profiles were demo accounts with no document at all, stamped
 * directly by a seeder. Every badge a client could see was false.
 *
 * The code now requires the document as well as the approval, so no such badge
 * can render again. This clears the stamps themselves, so the admin queue and
 * any report reading the column agree with what is on screen.
 *
 * Deliberately narrow: only rows with an approval and NO document. A real
 * verification — document present, approved by a person — is untouched, and
 * that is the difference between fixing bad data and undoing somebody's work.
 */
return new class extends Migration
{
    /** Kept in step with AdminVerificationController::BADGES. */
    private const BADGES = ['trade_license', 'workers_comp', 'liability_insurance'];

    public function up(): void
    {
        foreach (self::BADGES as $badge) {
            $doc = "{$badge}_doc";
            $at = "{$badge}_verified_at";

            if (! Schema::hasColumn('user_profiles', $doc) || ! Schema::hasColumn('user_profiles', $at)) {
                continue;
            }

            DB::table('user_profiles')
                ->whereNotNull($at)
                ->whereNull($doc)
                ->update([$at => null]);
        }
    }

    /**
     * Not reversible, and should not be.
     *
     * Putting the stamps back would restore a claim nobody ever made about a
     * professional. If a verification was genuine, the document is still there
     * and an admin can approve it again in one click.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
