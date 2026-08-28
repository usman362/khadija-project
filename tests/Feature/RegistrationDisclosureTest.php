<?php

namespace Tests\Feature;

use App\Models\RegistrationDisclosure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The location / state disclosure at sign-up.
 *
 * Sir Peter's document, 26 Aug 2026. What it asks for, in his words:
 *
 *   - it appears on the final step, before the account is submitted;
 *   - all three boxes must be ticked before the button becomes active,
 *     and there is to be no bypass;
 *   - acceptance is recorded: user, timestamp (UTC), version, IP;
 *   - it applies to Clients and Professionals. Influencers are separate.
 *
 * The three boxes are separate rather than one combined tick because they are
 * three different things to understand — and what is being agreed to is a
 * legal limit on who the person may work with.
 *
 * Everything here is asserted against the SERVER. The button disabling itself
 * is a courtesy; the validator is the gate.
 */
class RegistrationDisclosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'role'                  => 'client',
            'name'                  => 'Dana Whitfield',
            'email'                 => 'dana'.uniqid().'@example.com',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
            'state'                 => 'MD',
            'date_of_birth'         => now()->subYears(30)->toDateString(),
            'agree'                 => 1,
            'disclosure_event_location' => 1,
            'disclosure_state_limit'    => 1,
            'disclosure_temporary'      => 1,
        ], $overrides);
    }

    public function test_the_disclosure_is_on_the_registration_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertSuccessful();
        $response->assertSee('Where you can work');
        $response->assertSee('same state', false);
        $response->assertSee('name="disclosure_event_location"', false);
        $response->assertSee('name="disclosure_state_limit"', false);
        $response->assertSee('name="disclosure_temporary"', false);
    }

    /** The example from his document, in the copy people actually read. */
    public function test_it_states_the_rule_in_plain_terms(): void
    {
        $response = $this->get(route('register'));

        $response->assertSee('Your home state decides who you can work with', false);
        $response->assertSee('Maryland', false);
        $response->assertSee('Pennsylvania', false);
        $response->assertSee('temporary', false);
    }

    /** No bypass — and each box on its own. */
    public function test_every_box_is_required_on_its_own(): void
    {
        foreach ([
            'disclosure_event_location',
            'disclosure_state_limit',
            'disclosure_temporary',
        ] as $missing) {
            $this->post(route('register'), $this->payload([$missing => null]))
                ->assertSessionHasErrors($missing);
        }

        $this->assertSame(0, User::count());
    }

    public function test_a_post_with_none_of_them_is_rejected(): void
    {
        $this->post(route('register'), $this->payload([
            'disclosure_event_location' => null,
            'disclosure_state_limit'    => null,
            'disclosure_temporary'      => null,
        ]))->assertSessionHasErrors([
            'disclosure_event_location',
            'disclosure_state_limit',
            'disclosure_temporary',
        ]);

        $this->assertSame(0, User::count());
    }

    /** Who, when, which wording, from where. */
    public function test_acceptance_is_recorded_against_the_account(): void
    {
        $this->post(route('register'), $this->payload(['email' => 'dana@example.com']));

        $user = User::where('email', 'dana@example.com')->firstOrFail();
        $row  = RegistrationDisclosure::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(RegistrationDisclosure::CURRENT_VERSION, $row->version);
        $this->assertSame('location_state_v1', $row->version);
        $this->assertNotNull($row->accepted_at);
        $this->assertNotNull($row->ip_address);
    }

    /** Stored in UTC, because it may be read years later from anywhere. */
    public function test_the_timestamp_is_utc(): void
    {
        $this->post(route('register'), $this->payload(['email' => 'utc@example.com']));

        $row = RegistrationDisclosure::firstOrFail();

        $this->assertSame('UTC', $row->accepted_at->timezone->getName());
    }

    public function test_a_professional_must_accept_it_too(): void
    {
        $this->post(route('register'), $this->payload([
            'role'  => 'professional',
            'email' => 'pro@example.com',
            'disclosure_state_limit' => null,
        ]))->assertSessionHasErrors('disclosure_state_limit');

        $this->post(route('register'), $this->payload([
            'role'  => 'professional',
            'email' => 'pro@example.com',
        ]));

        $user = User::where('email', 'pro@example.com')->firstOrFail();
        $this->assertSame(1, RegistrationDisclosure::where('user_id', $user->id)->count());
    }

    /**
     * An influencer is neither a client nor a professional, and is never
     * matched by state — so the rule does not govern them.
     */
    public function test_an_influencer_is_not_asked_and_not_recorded(): void
    {
        $this->post(route('register'), [
            'role'                  => 'influencer',
            'name'                  => 'Riley Ash',
            'email'                 => 'riley@example.com',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
            'date_of_birth'         => now()->subYears(30)->toDateString(),
            'agree'                 => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'riley@example.com')->firstOrFail();

        $this->assertSame(0, RegistrationDisclosure::where('user_id', $user->id)->count());
    }

    /** Re-accepting the same version updates the row rather than stacking. */
    public function test_one_row_per_user_per_version(): void
    {
        $this->post(route('register'), $this->payload(['email' => 'once@example.com']));

        $user = User::where('email', 'once@example.com')->firstOrFail();

        RegistrationDisclosure::record($user, '10.0.0.1');
        RegistrationDisclosure::record($user, '10.0.0.2');

        $this->assertSame(1, RegistrationDisclosure::where('user_id', $user->id)->count());
        $this->assertSame('10.0.0.2', RegistrationDisclosure::where('user_id', $user->id)->value('ip_address'));
    }
}
