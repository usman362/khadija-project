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
 *   Influencers are gated too, but by R24 rather than R62 — same minimum,
 *   different citation. Row 82 caught that R24's own side was never built,
 *   which left the one group whose age rule came first as the only group
 *   with no gate at all.
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

            // Sir Peter's location/state disclosure (26 Aug 2026) — three
            // required boxes on every client and professional sign-up. This
            // file is about age, so it accepts them and gets out of the way;
            // RegistrationDisclosureTest is where they are the subject.
            'disclosure_event_location' => '1',
            'disclosure_state_limit'    => '1',
            'disclosure_temporary'      => '1',
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

    /**
     * Checklist row 82 — an influencer IS age-gated, under R24.
     *
     * This test used to assert the opposite, and it was encoding a gap rather
     * than a rule. R62 was written separately so R24's influencer-only scope
     * stayed clean, and the effect was that the one group whose age rule came
     * FIRST ended up as the only group with no gate at all: an influencer
     * could register with no date of birth.
     *
     * Both rules state the same minimum. Only the citation differs, which is
     * why ruleFor() exists.
     */
    public function test_an_influencer_is_age_gated_too_under_r24(): void
    {
        $this->assertTrue(AgeEligibility::appliesTo('influencer'));
        $this->assertSame('R24', AgeEligibility::ruleFor('influencer'));
        $this->assertSame('R62', AgeEligibility::ruleFor('professional'));

        $this->register(['role' => 'influencer', 'date_of_birth' => null, 'state' => null])
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_an_adult_influencer_registers_normally(): void
    {
        $this->register([
            'role' => 'influencer', 'date_of_birth' => '1995-06-01', 'state' => null,
        ])->assertSessionHasNoErrors();
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

    /**
     * An influencer account with no date of birth is UNKNOWN, exactly like a
     * client or professional account that predates the rule — not eligible
     * and not underage.
     *
     * This asserted 'not_applicable' before, which is what let an influencer
     * through with nothing on file. See the row-82 note above.
     */
    public function test_an_influencer_with_no_date_of_birth_is_unknown_not_exempt(): void
    {
        $user = User::factory()->create(['primary_role' => 'influencer']);
        $user->getOrCreateProfile();

        $this->assertSame('unknown', AgeEligibility::statusFor($user->fresh()));
        $this->assertFalse(AgeEligibility::isEligible($user->fresh()));
        $this->assertFalse(AgeEligibility::isUnderage($user->fresh()));
    }
}
