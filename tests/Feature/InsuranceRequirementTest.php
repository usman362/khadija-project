<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\InsuranceRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Proof of insurance is required for Alcohol service, Catering, Security and
 * Pyrotechnics (Khadijah, 2026-08-04).
 *
 * The other half of this is expiry. Before now the "Insured" badge was the
 * verified stamp alone, which never stopped being true — a policy verified in
 * 2025 still read as insured in 2027, on the pages a client uses to decide who
 * to hand their event to.
 */
class InsuranceRequirementTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('professional');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function category(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_active' => true,
        ]);
    }

    public function test_a_caterer_is_asked_for_insurance(): void
    {
        $pro = $this->pro();
        $pro->serviceCategories()->attach($this->category('Catering Services'));

        $this->assertTrue(InsuranceRequirement::appliesTo($pro->fresh()));
    }

    public function test_a_photographer_is_not(): void
    {
        $pro = $this->pro();
        $pro->serviceCategories()->attach($this->category('Photography Services'));

        $this->assertFalse(InsuranceRequirement::appliesTo($pro->fresh()));
    }

    public function test_a_bar_mitzvah_does_not_count_as_a_bar(): void
    {
        // A keyword list would have matched "bar" here and demanded a
        // certificate of insurance from anyone offering Bar/Bat Mitzvahs.
        $pro = $this->pro();
        $pro->serviceCategories()->attach($this->category('Bar/Bat Mitzvah'));

        $this->assertFalse(InsuranceRequirement::appliesTo($pro->fresh()));
    }

    public function test_the_notice_names_the_category_that_triggered_it(): void
    {
        $pro = $this->pro();
        $pro->serviceCategories()->attach($this->category('Beverage Services'));
        $pro->serviceCategories()->attach($this->category('Photography Services'));

        $this->assertSame(['Beverage Services'], InsuranceRequirement::triggeringCategories($pro->fresh()));
    }

    public function test_an_expired_policy_no_longer_counts_as_insured(): void
    {
        $pro = $this->pro();
        $pro->profile->update([
            'liability_insurance_verified_at' => now()->subYear(),
            'liability_insurance_expires_on'  => now()->subDay(),
        ]);

        $profile = $pro->fresh()->profile;

        $this->assertFalse(InsuranceRequirement::isCovered($profile));
        $this->assertTrue(InsuranceRequirement::hasLapsed($profile));
    }

    public function test_a_certificate_with_no_expiry_on_file_still_counts(): void
    {
        // Professionals verified before the expiry field existed must not be
        // marked uninsured overnight.
        $pro = $this->pro();
        $pro->profile->update([
            'liability_insurance_verified_at' => now()->subYear(),
            'liability_insurance_expires_on'  => null,
        ]);

        $this->assertTrue(InsuranceRequirement::isCovered($pro->fresh()->profile));
    }

    public function test_a_policy_close_to_running_out_is_flagged(): void
    {
        $pro = $this->pro();
        $pro->profile->update([
            'liability_insurance_verified_at' => now()->subMonths(11),
            'liability_insurance_expires_on'  => now()->addDays(10),
        ]);

        $profile = $pro->fresh()->profile;

        $this->assertTrue(InsuranceRequirement::isCovered($profile));
        $this->assertTrue(InsuranceRequirement::isExpiringSoon($profile));
        $this->assertSame(10, InsuranceRequirement::daysRemaining($profile));
    }

    public function test_the_scope_leaves_out_professionals_whose_cover_has_run_out(): void
    {
        $current = $this->pro();
        $current->profile->update([
            'liability_insurance_verified_at' => now(),
            'liability_insurance_expires_on'  => now()->addYear(),
        ]);

        $lapsed = $this->pro();
        $lapsed->profile->update([
            'liability_insurance_verified_at' => now()->subYear(),
            'liability_insurance_expires_on'  => now()->subWeek(),
        ]);

        $ids = \App\Models\UserProfile::insuranceCurrent()->pluck('user_id');

        $this->assertContains($current->id, $ids->all());
        $this->assertNotContains($lapsed->id, $ids->all());
    }

    public function test_submitting_a_certificate_records_the_policy_details(): void
    {
        Storage::fake('public');
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.profile.verification.submit'), [
            'badge'          => 'liability_insurance',
            'number'         => 'POL-99',
            'insurer'        => 'Hartford',
            'coverage'       => 1000000,
            'effective_from' => now()->toDateString(),
            'expires_on'     => now()->addYear()->toDateString(),
            'document'       => UploadedFile::fake()->create('coi.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $profile = $pro->fresh()->profile;

        $this->assertSame('Hartford', $profile->liability_insurance_insurer);
        $this->assertSame(1000000, $profile->liability_insurance_coverage);
        $this->assertNotNull($profile->liability_insurance_expires_on);
        $this->assertNull($profile->liability_insurance_verified_at, 'a new submission goes back into the review queue');
    }

    public function test_a_certificate_that_has_already_expired_is_refused(): void
    {
        Storage::fake('public');
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.profile.verification.submit'), [
            'badge'          => 'liability_insurance',
            'insurer'        => 'Hartford',
            'coverage'       => 1000000,
            'effective_from' => now()->subYears(2)->toDateString(),
            'expires_on'     => now()->subDay()->toDateString(),
            'document'       => UploadedFile::fake()->create('coi.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('expires_on');
    }

    public function test_the_other_two_badges_do_not_ask_for_policy_details(): void
    {
        Storage::fake('public');
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.profile.verification.submit'), [
            'badge'    => 'trade_license',
            'number'   => 'TL-1',
            'document' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($pro->fresh()->profile->trade_license_doc);
    }
}
