<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private MembershipPlan $freePlan;
    private MembershipPlan $paidPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->client = User::factory()->create();
        $this->client->assignRole(RoleName::CLIENT->value);

        $this->freePlan = MembershipPlan::create([
            'name' => 'Free Plan',
            'slug' => 'free',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->paidPlan = MembershipPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    // ── Setting Model ────────────────────────────────────────

    public function test_setting_can_be_stored_and_retrieved(): void
    {
        Setting::set('test.key', 'test_value', 'test', 'string');

        $this->assertEquals('test_value', Setting::get('test.key'));
    }

    public function test_setting_encrypted_values_are_decrypted(): void
    {
        Setting::set('test.secret', 'my_secret_key', 'test', 'encrypted');

        $value = Setting::get('test.secret');
        $this->assertEquals('my_secret_key', $value);

        // Raw DB value should be encrypted (not plaintext)
        $raw = Setting::byKey('test.secret')->first()->value;
        $this->assertNotEquals('my_secret_key', $raw);
    }

    public function test_setting_boolean_values(): void
    {
        Setting::set('test.flag', 'true', 'test', 'boolean');
        $this->assertTrue(Setting::get('test.flag'));

        Setting::set('test.flag', 'false', 'test', 'boolean');
        $this->assertFalse(Setting::get('test.flag'));
    }

    public function test_setting_returns_default_when_not_found(): void
    {
        $this->assertEquals('fallback', Setting::get('nonexistent.key', 'fallback'));
    }

    public function test_setting_get_group(): void
    {
        Setting::set('group.key1', 'val1', 'mygroup', 'string');
        Setting::set('group.key2', 'val2', 'mygroup', 'string');

        $group = Setting::getGroup('mygroup');
        $this->assertCount(2, $group);
        $this->assertEquals('val1', $group['group.key1']);
        $this->assertEquals('val2', $group['group.key2']);
    }

    // ── Payment Model ────────────────────────────────────────

    public function test_payment_status_helpers(): void
    {
        $payment = new Payment(['status' => 'pending']);
        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isCompleted());

        $payment->status = 'completed';
        $this->assertTrue($payment->isCompleted());

        $payment->status = 'failed';
        $this->assertTrue($payment->isFailed());

        $payment->status = 'refunded';
        $this->assertTrue($payment->isRefunded());
    }

    public function test_payment_status_labels_and_colors(): void
    {
        $payment = new Payment(['status' => 'pending']);
        $this->assertEquals('Pending', $payment->statusLabel());
        $this->assertEquals('warning', $payment->statusColor());

        $payment->status = 'completed';
        $this->assertEquals('Completed', $payment->statusLabel());
        $this->assertEquals('success', $payment->statusColor());

        $payment->status = 'failed';
        $this->assertEquals('Failed', $payment->statusLabel());
        $this->assertEquals('danger', $payment->statusColor());
    }

    public function test_payment_gateway_label(): void
    {
        $payment = new Payment(['gateway' => 'stripe']);
        $this->assertEquals('Stripe', $payment->gatewayLabel());

        $payment->gateway = 'paypal';
        $this->assertEquals('PayPal', $payment->gatewayLabel());
    }

    public function test_payment_mark_completed(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->paidPlan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'amount_paid' => 29.99,
        ]);

        $payment = Payment::create([
            'user_id' => $this->client->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => 'stripe',
            'status' => 'pending',
            'amount' => 29.99,
            'currency' => 'USD',
        ]);

        $payment->markCompleted('pi_123456', 'card');

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals('pi_123456', $payment->gateway_payment_id);
        $this->assertEquals('card', $payment->payment_method);
        $this->assertNotNull($payment->completed_at);
    }

    public function test_payment_mark_failed(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->paidPlan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'amount_paid' => 29.99,
        ]);

        $payment = Payment::create([
            'user_id' => $this->client->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => 'stripe',
            'status' => 'pending',
            'amount' => 29.99,
            'currency' => 'USD',
        ]);

        $payment->markFailed('Card declined');

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
        $this->assertEquals('Card declined', $payment->failure_reason);
    }

    // ── Free Plan Subscribe (instant) ────────────────────────

    public function test_free_plan_subscribes_instantly(): void
    {
        $response = $this->actingAs($this->client)
            ->post(route('app.membership-plans.subscribe', $this->freePlan));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->freePlan->id,
            'status' => 'active',
            'amount_paid' => '0.00',
        ]);
    }

    // ── Paid Plan Redirect ───────────────────────────────────

    public function test_paid_plan_redirects_to_payment_initiate(): void
    {
        $response = $this->actingAs($this->client)
            ->post(route('app.membership-plans.subscribe', $this->paidPlan));

        $response->assertRedirect(route('app.payments.initiate', $this->paidPlan));
    }

    // ── Admin Payment Settings ───────────────────────────────

    public function test_admin_can_view_payment_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('app.admin.settings.payments'));

        $response->assertOk();
        $response->assertViewIs('dashboard.settings.payments');
    }

    public function test_non_admin_cannot_view_payment_settings(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('app.admin.settings.payments'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_payment_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('app.admin.settings.payments.update'), [
                'active_gateway' => 'stripe',
                'mode' => 'test',
                'currency' => 'USD',
                'stripe_public_key' => 'pk_test_123',
                'stripe_secret_key' => 'sk_test_456',
                'stripe_webhook_secret' => 'whsec_789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals('stripe', Setting::get('payment.active_gateway'));
        $this->assertEquals('test', Setting::get('payment.mode'));
        $this->assertEquals('pk_test_123', Setting::get('payment.stripe_public_key'));
    }

    public function test_admin_update_validates_gateway(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('app.admin.settings.payments.update'), [
                'active_gateway' => 'invalid_gateway',
                'mode' => 'test',
                'currency' => 'USD',
            ]);

        $response->assertSessionHasErrors('active_gateway');
    }

    // ── Payment History ──────────────────────────────────────

    public function test_client_can_view_payment_history(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('app.payments.history'));

        $response->assertOk();
        $response->assertViewIs('dashboard.payments.history');
    }

    public function test_admin_can_view_all_payment_history(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('app.payments.history'));

        $response->assertOk();
    }

    // ── Payment Relationships ────────────────────────────────

    public function test_payment_belongs_to_user(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->paidPlan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'amount_paid' => 29.99,
        ]);

        $payment = Payment::create([
            'user_id' => $this->client->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => 'stripe',
            'status' => 'pending',
            'amount' => 29.99,
            'currency' => 'USD',
        ]);

        $this->assertEquals($this->client->id, $payment->user->id);
        $this->assertEquals($subscription->id, $payment->subscription->id);
    }

    public function test_subscription_has_many_payments(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->client->id,
            'membership_plan_id' => $this->paidPlan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'amount_paid' => 29.99,
        ]);

        Payment::create([
            'user_id' => $this->client->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => 'stripe',
            'status' => 'failed',
            'amount' => 29.99,
            'currency' => 'USD',
        ]);

        Payment::create([
            'user_id' => $this->client->id,
            'user_subscription_id' => $subscription->id,
            'gateway' => 'stripe',
            'status' => 'completed',
            'amount' => 29.99,
            'currency' => 'USD',
        ]);

        $this->assertEquals(2, $subscription->payments()->count());
    }

    // ── Webhook Routes Accessible ────────────────────────────

    public function test_stripe_webhook_route_exists(): void
    {
        // Webhook should be accessible without auth (returns 400 for invalid sig)
        $response = $this->postJson('/webhooks/stripe', ['test' => true]);

        // Should not be 404 (route exists) and not 419 (no CSRF required)
        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(419, $response->status());
    }

    public function test_paypal_webhook_route_exists(): void
    {
        $response = $this->postJson('/webhooks/paypal', ['test' => true]);

        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(419, $response->status());
    }
}
