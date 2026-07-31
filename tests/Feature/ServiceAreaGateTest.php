<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The service-area gate: an out-of-area account is real and usable, it just
 * cannot commit to anything. Both halves matter — over-blocking turns Peter's
 * "register everyone" rule into a dead account, and under-blocking books a job
 * in a state we do not operate in.
 */
class ServiceAreaGateTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $status, string $role = 'client'): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);

        // The portal routes sit behind permission:dashboard.view. Granting it
        // directly keeps this test about the service-area gate and nothing else
        // — otherwise a 403 from Spatie reads exactly like a 403 from us.
        $user->givePermissionTo('dashboard.view');

        $user->getOrCreateProfile()->update([
            'service_area_status' => $status,
            'state'               => $status === ServiceArea::SUPPORTED ? 'MD' : 'OH',
            'city'                => $status === ServiceArea::SUPPORTED ? 'Baltimore' : 'Columbus',
            'country'             => 'US',
        ]);

        return $user->fresh();
    }

    public function test_coming_soon_account_keeps_its_own_account_working(): void
    {
        $user = $this->user(ServiceArea::COMING_SOON);

        $this->actingAs($user)
            ->patch(route('client.profile.update.general'), [
                'name'  => 'Renamed Person',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Person', $user->fresh()->name);
    }

    public function test_coming_soon_account_can_still_reach_the_waitlist_optin(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON))
            ->post(route('register.welcome.opt-in'), ['expansion_opt_in' => 1])
            ->assertSessionHas('status');
    }

    public function test_coming_soon_account_can_still_log_out(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON))
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_coming_soon_client_is_stopped_at_the_door_of_a_request_wizard(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON))
            ->get(route('client.bsr.step', 'service'))
            ->assertStatus(403)
            ->assertSee('We haven\'t opened here yet', false);
    }

    public function test_coming_soon_client_cannot_post_a_request(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON))
            ->post(route('client.esr.store'), [])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_coming_soon_professional_cannot_bid(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON, 'professional'))
            ->post(route('professional.bidding-board.bid'), [])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_supported_account_is_not_gated(): void
    {
        $this->actingAs($this->user(ServiceArea::SUPPORTED))
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful();
    }

    public function test_country_stored_as_a_label_still_counts_as_in_area(): void
    {
        // Seeders and older profile forms wrote "United States" rather than
        // "US". Comparing the raw value put every demo professional out of
        // area while they sat in launch states.
        $this->assertSame(
            ServiceArea::SUPPORTED,
            ServiceArea::statusFor('United States', 'MD'),
        );
    }

    public function test_moving_into_a_launch_state_un_gates_the_account(): void
    {
        $user = $this->user(ServiceArea::COMING_SOON);

        $user->profile->update(['state' => 'MD', 'city' => 'Baltimore']);

        $this->assertSame(ServiceArea::SUPPORTED, $user->fresh()->profile->service_area_status);

        $this->actingAs($user->fresh())
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful();
    }

    public function test_moving_out_of_a_launch_state_gates_the_account(): void
    {
        $user = $this->user(ServiceArea::SUPPORTED);

        $user->profile->update(['state' => 'OH']);

        $this->assertSame(ServiceArea::COMING_SOON, $user->fresh()->profile->service_area_status);
    }

    public function test_admin_is_never_gated_by_where_they_live(): void
    {
        $this->actingAs($this->user(ServiceArea::COMING_SOON, 'admin'))
            ->post(route('client.esr.store'), [])
            ->assertSessionMissing('error');
    }
}
