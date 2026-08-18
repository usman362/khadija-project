<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R46 — user-facing labels are BR / ER / DR.
 *
 * Routes, class names, config keys and stored `source` values stay as they
 * are. SSR and MSR stay as the scope inside a request, not as types.
 */
class RequestTypeLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->givePermissionTo(['dashboard.view', 'events.create']);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_the_chooser_names_br_er_and_dr(): void
    {
        $page = $this->actingAs($this->client())->get(route('client.post-event.choose'));

        $page->assertOk()
            ->assertSee('Bidding Request (BR)', false)
            ->assertSee('Emergency Request (ER)', false)
            ->assertSee('Direct Request (DR)', false)
            ->assertDontSee('Direct Offer', false)
            ->assertDontSee('Emergency Service Request', false);
    }

    public function test_the_direct_request_form_does_not_say_offer(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.direct-offers.create'))
            ->assertOk()
            ->assertSee('Send a Direct Request', false)
            ->assertSee('How Direct Requests work', false)
            ->assertDontSee('Direct Offer', false);
    }

    public function test_the_emergency_form_is_labelled_er(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.esr.create'))
            ->assertOk()
            ->assertSee('Emergency Request (ER)', false)
            ->assertDontSee('Emergency Service Request', false);
    }

    public function test_the_prototype_uses_the_new_names(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.prototype.tool-to-request', ['tool' => 'budget-allocator']))
            ->assertOk()
            ->assertSee('Post as BR', false)
            ->assertSee('Post as ER', false)
            ->assertSee('Send Direct Request', false)
            ->assertDontSee('Direct Offer', false)
            ->assertDontSee('Post as ESR', false);
    }

    public function test_membership_copy_does_not_revive_the_old_type_names(): void
    {
        $this->actingAs($this->client())
            ->get(route('membership.plans'))
            ->assertOk()
            ->assertSee('Early access — new BRs (multi-service)', false)
            ->assertSee('Early access — new ERs', false)
            ->assertDontSee('new SSRs', false)
            ->assertDontSee('new ESRs', false)
            ->assertDontSee('Direct Offer', false);
    }
}
