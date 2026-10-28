<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the Verification tab promises has to match what the site shows.
 *
 * OA-165 (Sir Peter, 23 September) took every check-mark off the live site
 * until a verification feature is defined. The professional's own Verification
 * tab was never told. It went on saying that a verified badge "appears on your
 * public profile and in search results", so a professional was being asked for
 * licence and insurance documents in exchange for a mark that nobody can see.
 *
 * The same line named a competitor and claimed what clients would feel. Both
 * were dev notes that reached a real screen.
 *
 * The page now reads the one switch that decides it, so the promise and the
 * site cannot drift apart again, whichever way that switch is set.
 */
class TheVerificationPageDoesNotPromiseAHiddenBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->professional = User::findOrFail($u->id);
    }

    private function verificationTab(): string
    {
        return $this->actingAs($this->professional)
            ->get(route('professional.profile.index', ['tab' => 'verification']))
            ->assertSuccessful()
            ->getContent();
    }

    /** With the marks off, the page does not claim a client will see one. */
    public function test_it_does_not_promise_a_badge_that_is_switched_off(): void
    {
        config(['badges.verification_marks' => false]);

        $html = $this->verificationTab();

        $this->assertStringNotContainsString('appear on your public profile', $html);
        $this->assertStringContainsString('not shown anywhere on the site', $html);
    }

    /** With them on, it says so. The promise follows the switch, not a guess. */
    public function test_it_says_the_badge_is_shown_once_it_is(): void
    {
        config(['badges.verification_marks' => true]);

        $html = $this->verificationTab();

        $this->assertStringContainsString('shown on your public profile', $html);
        $this->assertStringNotContainsString('not shown anywhere on the site', $html);
    }

    /** Our own copy does not advertise somebody else's platform. */
    public function test_it_does_not_name_a_competitor(): void
    {
        $this->assertStringNotContainsString('BestPickPro', $this->verificationTab());
    }

    /**
     * Nor does it tell a professional what clients will feel.
     *
     * Sir Peter's compliance rule: no claims about outcomes we cannot stand
     * behind.
     */
    public function test_it_does_not_claim_what_clients_will_feel(): void
    {
        $this->assertStringNotContainsString('trust you faster', $this->verificationTab());
    }

    /**
     * A lapsed policy still has a consequence, and it is the true one.
     *
     * The old line said clients no longer see you as insured. They see no
     * verification marks at all, so that was not what had happened. What has
     * happened is that the cover the work requires is no longer in force.
     */
    public function test_a_lapsed_policy_names_the_real_consequence(): void
    {
        config(['badges.verification_marks' => false]);

        $this->professional->profile->update([
            'liability_insurance_doc'         => 'docs/policy.pdf',
            'liability_insurance_verified_at' => now()->subYear(),
            'liability_insurance_expires_on'  => now()->subDay(),
        ]);

        $html = $this->verificationTab();

        $this->assertStringContainsString('This policy has expired.', $html);
        $this->assertStringNotContainsString('Clients no longer see you as insured', $html);
        $this->assertStringContainsString('no longer count as insured', $html);
    }
}
