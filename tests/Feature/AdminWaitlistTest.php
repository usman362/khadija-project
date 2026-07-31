<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWaitlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed once, up front: the helpers below assign roles in whatever order
        // a test needs them, and seeding inside one of them made that order
        // matter.
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin->fresh();
    }

    private function waiting(string $state, string $city, bool $notify = false): void
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update([
            'country'          => 'US',
            'state'            => $state,
            'city'             => $city,
            'expansion_opt_in' => $notify,
        ]);
    }

    public function test_it_groups_by_state_largest_first_and_counts_opt_ins(): void
    {
        $this->waiting('OH', 'Columbus', true);
        $this->waiting('OH', 'Cleveland', true);
        $this->waiting('OH', 'Akron');
        $this->waiting('NY', 'Buffalo', true);

        $response = $this->actingAs($this->admin())->get(route('app.admin.waitlist.index'));

        $response->assertSuccessful();

        $byState = $response->viewData('byState');

        $this->assertSame('OH', $byState[0]['state'], 'largest state should lead');
        $this->assertSame(3, $byState[0]['people']);
        $this->assertSame(2, $byState[0]['notify']);
        $this->assertSame('Ohio', $byState[0]['label']);

        $this->assertSame(4, $response->viewData('totalWaiting'));
        $this->assertSame(3, $response->viewData('totalNotify'));
    }

    public function test_in_area_accounts_never_appear(): void
    {
        $this->waiting('OH', 'Columbus');

        $inArea = User::factory()->create();
        $inArea->assignRole('client');
        $inArea->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $this->assertSame(ServiceArea::SUPPORTED, $inArea->fresh()->profile->service_area_status);

        $response = $this->actingAs($this->admin())->get(route('app.admin.waitlist.index'));

        $this->assertSame(1, $response->viewData('totalWaiting'));
    }

    public function test_it_can_be_filtered_to_one_state(): void
    {
        $this->waiting('OH', 'Columbus');
        $this->waiting('NY', 'Buffalo');

        $response = $this->actingAs($this->admin())
            ->get(route('app.admin.waitlist.index', ['state' => 'OH']));

        $response->assertSuccessful();

        $people = $response->viewData('people');
        $this->assertCount(1, $people);
        $this->assertSame('Columbus', $people->first()->city);
    }

    public function test_a_non_admin_cannot_reach_it(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client->fresh())
            ->get(route('app.admin.waitlist.index'))
            ->assertForbidden();
    }
}
