<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two things on the registration form that looked finished and were not.
 *
 * The dialling code was a <div> reading "🇺🇸 +1". It was styled to look like a
 * dropdown, but nothing could change it — so somebody registering from the UK
 * typed their number beside +1 and it was stored that way. We accept accounts
 * from five countries.
 *
 * Date of birth was the browser's own mm/dd/yyyy control. This page extends no
 * layout, so it never received the datepicker partial the rest of the site
 * includes, and the one date field on the busiest form looked foreign to it.
 */
class RegistrationPhoneAndDobTest extends TestCase
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
            'name'                      => 'Marguerite Ashfield',
            'email'                     => 'marguerite@example.com',
            'password'                  => 'Password!2345',
            'password_confirmation'     => 'Password!2345',
            'role'                      => 'client',
            'state'                     => 'MD',
            'agree'                     => '1',
            'date_of_birth'             => '1990-04-11',
            'disclosure_event_location' => '1',
            'disclosure_state_limit'    => '1',
            'disclosure_temporary'      => '1',
        ], $overrides);
    }

    public function test_the_dialling_code_is_something_you_can_actually_change(): void
    {
        $html = $this->get('/register')->assertSuccessful()->getContent();

        $this->assertStringContainsString('<select name="phone_country_code"', $html);
        $this->assertStringNotContainsString('<div class="rg-cc">', $html);

        // More than one country's code is on offer.
        foreach (['+44', '+61', '+92'] as $code) {
            $this->assertStringContainsString('value="' . $code . '"', $html);
        }
    }

    public function test_the_chosen_code_is_kept_with_the_number(): void
    {
        $this->post('/register', $this->payload([
            'phone'              => '7700900123',
            'phone_country_code' => '+44',
        ]));

        $user = User::where('email', 'marguerite@example.com')->firstOrFail();

        $this->assertSame('+44 7700900123', $user->phone);
    }

    /** A leading zero is a local-dialling habit; it does not belong after a country code. */
    public function test_a_leading_zero_is_dropped_after_the_code(): void
    {
        $this->post('/register', $this->payload([
            'phone'              => '03353241558',
            'phone_country_code' => '+92',
        ]));

        $this->assertSame('+92 3353241558', User::where('email', 'marguerite@example.com')->firstOrFail()->phone);
    }

    /** Somebody who typed the code themselves is not given a second one. */
    public function test_a_number_that_already_carries_a_code_is_left_alone(): void
    {
        $this->post('/register', $this->payload([
            'phone'              => '+353 861234567',
            'phone_country_code' => '+1',
        ]));

        $this->assertSame('+353 861234567', User::where('email', 'marguerite@example.com')->firstOrFail()->phone);
    }

    public function test_an_invented_dialling_code_is_refused(): void
    {
        $this->post('/register', $this->payload([
            'phone'              => '5551234',
            'phone_country_code' => '+999',
        ]))->assertSessionHasErrors('phone_country_code');
    }

    public function test_the_date_of_birth_field_gets_the_sites_own_picker(): void
    {
        $html = $this->get('/register')->assertSuccessful()->getContent();

        // The partial every other page includes — this page extends no layout,
        // so it has to include it itself.
        $this->assertStringContainsString('flatpickr', $html,
            'Date of birth falls back to the browser control without the datepicker partial.');
    }
}
