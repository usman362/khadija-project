<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AgeEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Rule R62, locked 2026-08-07 — every Client and Professional account holder
 * must be 18+.
 *
 * Three boundaries the rule draws, each with a test here, because each is a
 * plausible over-reach:
 *
 *   Influencers are out of scope. R24 governs their age separately and R62
 *   was written as its own rule to keep that scope clean.
 *
 *   Attendees are out of scope. Minors may be at an event booked through the
 *   platform (R55). This governs who holds the account, nothing else.
 *
 *   Existing accounts have no date of birth on file. That is UNKNOWN, not
 *   ineligible — reading it as a failure would shut out everyone who signed
 *   up before the rule existed, which is exactly the trap a default value
 *   caused once already on the service-area column.
 */
class AgeEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registration assigns a role, so the role table has to exist.
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', array_merge([
            'name'                  => 'Jordan Reyes',
            'email'                 => 'jordan@example.com',
            'password'              => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            'role'                  => 'client',
            'state'                 => 'MD',
            'country'               => 'US',
            'date_of_birth'         => '1990-04-02',
            'agree'                 => '1',
        ], $overrides));
    }

    public function test_an_adult_can_register(): void
    {
        $this->register()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'jordan@example.com']);
    }

    public function test_someone_under_eighteen_cannot_register(): void
    {
        $this->register(['date_of_birth' => Carbon::today()->subYears(17)->toDateString()])
            ->assertSessionHasErrors('date_of_birth');

        $this->assertDatabaseMissing('users', ['email' => 'jordan@example.com']);
    }

    public function test_the_day_before_the_eighteenth_birthday_is_refused(): void
    {
        // The boundary, from both sides — an off-by-one here is a rule that
        // quietly admits or excludes a whole day's worth of people.
        $this->register(['date_of_birth' => Carbon::today()->subYears(18)->addDay()->toDateString()])
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_the_eighteenth_birthday_itself_is_allowed(): void
    {
        $this->register(['date_of_birth' => Carbon::today()->subYears(18)->toDateString()])
            ->assertSessionHasNoErrors();
    }

    public function test_a_professional_is_held_to_the_same_rule(): void
    {
        $this->register(['role' => 'professional', 'date_of_birth' => Carbon::today()->subYears(16)->toDateString()])
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_a_missing_date_of_birth_is_refused_at_registration(): void
    {
        $this->register(['date_of_birth' => null])->assertSessionHasErrors('date_of_birth');
    }

    public function test_an_influencer_is_not_asked_for_it_here(): void
    {
        // R24's business, not R62's. Requiring it here would fold the two
        // rules together, which is the thing R62 was written to avoid.
        $this->assertFalse(AgeEligibility::appliesTo('influencer'));

        $this->register(['role' => 'influencer', 'date_of_birth' => null, 'state' => null])
            ->assertSessionHasNoErrors();
    }

    public function test_the_date_of_birth_is_kept_on_the_account(): void
    {
        $this->register(['date_of_birth' => '1990-04-02']);

        $profile = User::where('email', 'jordan@example.com')->first()->profile;

        $this->assertSame('1990-04-02', $profile->date_of_birth->toDateString());
    }

    public function test_an_account_with_no_date_on_file_is_unknown_not_underage(): void
    {
        // Every account that predates the rule lands here. Eligibility is not
        // established, but they are not treated as a minor either.
        $user = User::factory()->create(['primary_role' => 'client']);
        $user->getOrCreateProfile();
        $user = $user->fresh();

        $this->assertSame('unknown', AgeEligibility::statusFor($user));
        $this->assertFalse(AgeEligibility::isEligible($user));
        $this->assertFalse(AgeEligibility::isUnderage($user), 'unknown must not be read as underage');
    }

    public function test_a_known_minor_is_underage(): void
    {
        $user = User::factory()->create(['primary_role' => 'professional']);
        $user->getOrCreateProfile()->update(['date_of_birth' => Carbon::today()->subYears(15)]);

        $this->assertSame('underage', AgeEligibility::statusFor($user->fresh()));
        $this->assertTrue(AgeEligibility::isUnderage($user->fresh()));
    }

    public function test_the_rule_does_not_reach_an_influencer_account(): void
    {
        $user = User::factory()->create(['primary_role' => 'influencer']);
        $user->getOrCreateProfile();

        $this->assertSame('not_applicable', AgeEligibility::statusFor($user->fresh()));
        $this->assertTrue(AgeEligibility::isEligible($user->fresh()));
        $this->assertFalse(AgeEligibility::isUnderage($user->fresh()));
    }
}
