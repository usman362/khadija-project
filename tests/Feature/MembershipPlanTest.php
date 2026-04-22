<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $supplier;
    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->client = User::factory()->create();
        $this->client->assignRole(RoleName::CLIENT->value);

        $this->supplier = User::factory()->create();
        $this->supplier->assignRole(RoleName::SUPPLIER->value);

        $this->plan = MembershipPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'price' => 19.99,
            'billing_cycle' => '12_month',
            'duration_days' => 30,
            'max_events' => 10,
            'max_bookings' => 20,
            'has_chat' => true,
            'has_priority_support' => false,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $this->plan->features()->create([
            'feature' => 'Test feature 1',
            'is_included' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_plans_page_loads_for_client(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/membership-plans');

        $response->assertOk();
        $response->assertViewIs('dashboard.membership-plans.index');
        $response->assertSee('Test Plan');
    }

    public function test_plans_page_loads_for_supplier(): void
    {
        $response = $this->actingAs($this->supplier)
            ->get('/app/membership-plans');

        $response->assertOk();
        $response->assertSee('Test Plan');
    }

    public function test_client_can_subscribe_to_plan(): void
    {
        $response = $this->actingAs($this->client)
            ->post("/app/membership-plans/{$this->plan->id}/subscribe");

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);
    }

    public function test_subscribing_cancels_previous_subscription(): void
    {
        // First subscription
        $this->actingAs($this->client)
            ->post("/app/membership-plans/{$this->plan->id}/subscribe");

        // Create a second plan
        $plan2 = MembershipPlan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium-plan',
            'price' => 49.99,
            'billing_cycle' => '12_month',
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Subscribe to second plan
        $this->actingAs($this->client)
            ->post("/app/membership-plans/{$plan2->id}/subscribe");

        // First subscription should be cancelled
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->plan->id,
            'status' => 'cancelled',
        ]);

        // Second should be active
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->client->id,
            'membership_plan_id' => $plan2->id,
            'status' => 'active',
        ]);
    }

    public function test_client_can_cancel_subscription(): void
    {
        // Subscribe first
        $this->actingAs($this->client)
            ->post("/app/membership-plans/{$this->plan->id}/subscribe");

        // Cancel
        $response = $this->actingAs($this->client)
            ->post('/app/membership-plans/cancel');

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->client->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_create_plan(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/app/admin/membership-plans', [
                'name' => 'New Plan',
                'price' => 9.99,
                'billing_cycle' => '12_month',
                'has_chat' => true,
                'is_active' => true,
                'features' => ['Feature A', 'Feature B'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('membership_plans', [
            'name' => 'New Plan',
            'slug' => 'new-plan',
        ]);

        $plan = MembershipPlan::where('slug', 'new-plan')->first();
        $this->assertEquals(2, $plan->features()->count());
    }

    public function test_admin_can_update_plan(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch("/app/admin/membership-plans/{$this->plan->id}", [
                'name' => 'Updated Plan',
                'price' => 29.99,
                'billing_cycle' => '18_month',
                'has_chat' => true,
                'is_active' => true,
                'features' => ['Updated Feature'],
            ]);

        $response->assertRedirect();

        $this->plan->refresh();
        $this->assertEquals('Updated Plan', $this->plan->name);
        $this->assertEquals(29.99, $this->plan->price);
        $this->assertEquals('18_month', $this->plan->billing_cycle);
        $this->assertEquals(1, $this->plan->features()->count());
    }

    public function test_admin_can_delete_plan_without_subscribers(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/app/admin/membership-plans/{$this->plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('membership_plans', ['id' => $this->plan->id]);
    }

    public function test_cannot_delete_plan_with_active_subscribers(): void
    {
        UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'amount_paid' => $this->plan->price,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/app/admin/membership-plans/{$this->plan->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('membership_plans', ['id' => $this->plan->id]);
    }

    public function test_client_cannot_access_admin_management(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/admin/membership-plans');

        $response->assertForbidden();
    }

    public function test_admin_management_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/app/admin/membership-plans');

        $response->assertOk();
        $response->assertViewIs('dashboard.membership-plans.admin');
        $response->assertSee('Test Plan');
    }

    public function test_subscription_history_page_loads(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/membership-plans/history');

        $response->assertOk();
        $response->assertViewIs('dashboard.membership-plans.history');
    }

    public function test_inactive_plan_cannot_be_subscribed(): void
    {
        $this->plan->update(['is_active' => false]);

        $response = $this->actingAs($this->client)
            ->post("/app/membership-plans/{$this->plan->id}/subscribe");

        $response->assertForbidden();
    }

    public function test_user_has_active_subscription_helper(): void
    {
        $this->assertFalse($this->client->hasActiveSubscription());

        UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'amount_paid' => $this->plan->price,
        ]);

        // Refresh to clear any cached state
        $this->client->refresh();
        $this->assertTrue($this->client->hasActiveSubscription());
    }
}
