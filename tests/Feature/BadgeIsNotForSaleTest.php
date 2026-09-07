<?php

namespace Tests\Feature;

use App\Models\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-136 / DIR-13 — the Verified Professional badge is not a paid feature.
 *
 * Pro and Elite both advertised "Sealed gigs · Verified Professional badge".
 * DIR-13 is explicit: the badge is tied to submitting a licence and having it
 * approved, never to a plan.
 *
 * It is ISSUE-1 from the other end. The badge now requires a document and an
 * approval before it will render at all — so a professional could have paid
 * for something payment cannot produce.
 *
 * Checked against the seeder as well as the rows, because the rows were right
 * once before and a seeder run put the claim back.
 */
class BadgeIsNotForSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_plan_advertises_the_badge(): void
    {
        $this->seed(\Database\Seeders\MembershipPlanSeeder::class);

        $selling = PlanFeature::where('feature', 'like', '%Verified Professional badge%')
            ->orWhere('feature', 'like', '%Verified Pro badge%')
            ->pluck('feature');

        $this->assertCount(0, $selling, "a plan still sells the badge:\n".$selling->join("\n"));
    }

    /** And the real feature on that line survived. */
    public function test_sealed_gigs_is_still_a_plan_feature(): void
    {
        $this->seed(\Database\Seeders\MembershipPlanSeeder::class);

        $this->assertGreaterThan(
            0,
            PlanFeature::where('feature', 'like', '%Sealed gigs%')->count(),
            'removing the badge took a real feature with it',
        );
    }

    /**
     * The seeder itself. The database can be corrected; a seeder that still
     * carries the claim puts it back the next time anybody runs it, which is
     * how the FAQ's platform name survived two corrections.
     */
    public function test_the_seeder_does_not_carry_the_claim(): void
    {
        $source = file_get_contents(database_path('seeders/MembershipPlanSeeder.php'));

        // The comment explaining the removal names the badge, so look only at
        // what is actually written into a feature string.
        $source = preg_replace('#^\s*//.*$#m', '', $source);

        $this->assertStringNotContainsString('Verified Professional badge', $source);
    }
}
