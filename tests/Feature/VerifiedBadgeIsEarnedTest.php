<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Support\VerifiedBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ISSUE-1 — a verified badge must have a verification behind it.
 *
 * Khadijah, 2026-09-03, CRITICAL: "the 'License Verified' badge is live with
 * no real verification behind it."
 *
 * What was actually found is worth keeping written down, because it decided
 * the fix. The mechanism is real — a professional uploads a document, it
 * reaches the admin queue, a person approves it. But all ten verified profiles
 * on the platform were demo accounts, not one had uploaded a document, and a
 * seeder had written the timestamp straight into the column. So every badge a
 * client could see was false, while the feature behind it worked.
 *
 * Removing the badge would have discarded a working feature to fix bad data.
 * The claim now requires its evidence instead: document AND approval.
 */
class VerifiedBadgeIsEarnedTest extends TestCase
{
    use RefreshDatabase;

    private function profile(?string $doc, ?string $approvedAt): UserProfile
    {
        $u = User::factory()->create(['primary_role' => 'professional']);

        $p = $u->getOrCreateProfile();
        $p->update([
            'trade_license_doc' => $doc,
            'trade_license_verified_at' => $approvedAt,
        ]);

        return $p->fresh();
    }

    /* ── The rule ───────────────────────────────────────────── */

    /**
     * The exact shape of the bad data: approved, nothing uploaded. This is
     * what a seeder writes and what ten profiles looked like.
     */
    public function test_an_approval_with_no_document_is_not_a_badge(): void
    {
        $this->assertFalse(
            VerifiedBadge::licenceVerified($this->profile(null, now()->toDateTimeString())),
        );
    }

    /** A document nobody has approved is not a badge either. */
    public function test_a_document_with_no_approval_is_not_a_badge(): void
    {
        $this->assertFalse(
            VerifiedBadge::licenceVerified($this->profile('licences/real.pdf', null)),
        );
    }

    /** Both halves, and it counts. */
    public function test_a_document_that_was_approved_is_a_badge(): void
    {
        $this->assertTrue(
            VerifiedBadge::licenceVerified($this->profile('licences/real.pdf', now()->toDateTimeString())),
        );
    }

    public function test_a_profile_that_does_not_exist_is_not_verified(): void
    {
        $this->assertFalse(VerifiedBadge::licenceVerified(null));
    }

    /** An unknown badge name is never true, rather than an error. */
    public function test_an_unknown_badge_is_refused(): void
    {
        $p = $this->profile('licences/real.pdf', now()->toDateTimeString());

        $this->assertFalse(VerifiedBadge::holds($p, 'nonsense_badge'));
    }

    /* ── The data that was already there ────────────────────── */

    /**
     * The migration cleared the stamps with nothing behind them, so the admin
     * queue and the reports agree with what is on screen. A real verification
     * — document present — must have survived it.
     */
    public function test_the_cleanup_left_no_approval_without_a_document(): void
    {
        $this->assertSame(
            0,
            UserProfile::whereNotNull('trade_license_verified_at')
                ->whereNull('trade_license_doc')
                ->count(),
        );
    }

    /* ── The SQL used by the list pages ─────────────────────── */

    /**
     * Browse and the category pages filter and sort in SQL rather than in PHP,
     * so they need the same rule expressed the same way. A client who filters
     * by "verified" and then opens a profile with no badge has been told two
     * different things by one site.
     */
    public function test_the_sql_rule_matches_the_php_rule(): void
    {
        $stamped = $this->profile(null, now()->toDateTimeString());
        $earned = $this->profile('licences/real.pdf', now()->toDateTimeString());

        $ids = UserProfile::whereRaw(VerifiedBadge::scopeSql('trade_license'))
            ->pluck('user_id');

        $this->assertContains($earned->user_id, $ids->all());
        $this->assertNotContains($stamped->user_id, $ids->all());
    }
}
