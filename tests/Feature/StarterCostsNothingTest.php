<?php

namespace Tests\Feature;

use App\Domain\Payments\Services\PaymentService;
use App\Models\{MembershipPlan, Payment, User, UserSubscription};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Starter costs nothing, from 20 October 2026.
 *
 * Khadijah's revised Founding Starter Plan, and Peter's own membership guide the
 * day before: "Starter is FREE every month for Professionals and Influencers.
 * GigResource keeps 5% of their completed paid jobs. This is not just a
 * six-month special."
 *
 * The price was the easy half. `initiate()` saw a free plan and REFUSED it with
 * "Free plans do not require payment", which was harmless while every plan cost
 * something. Setting Starter to $0 would have turned that into a wall: the
 * Subscribe button telling people there is nothing to pay and then doing
 * nothing, so nobody could join the free plan at all.
 */
class StarterCostsNothingTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\MembershipPlanSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro = User::findOrFail($this->pro->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function starter(): MembershipPlan
    {
        return MembershipPlan::where('slug', 'starter')->firstOrFail();
    }

    // ── The prices ───────────────────────────────────────────

    public function test_starter_is_free(): void
    {
        $this->assertSame('0.00', (string) $this->starter()->price);
        $this->assertTrue($this->starter()->isFree());
    }

    /**
     * And the other two are deliberately untouched.
     *
     * The same document: "Do not raise Pro or Elite prices right now.
     * GigResource has no customers yet. We first need people to sign up, get
     * hired, and see that the site helps them."
     */
    public function test_pro_and_elite_are_not_touched(): void
    {
        $this->assertSame('39.99', (string) MembershipPlan::where('slug', 'professional')->first()->price);
        $this->assertSame('59.99', (string) MembershipPlan::where('slug', 'enterprise')->first()->price);
    }

    // ── Joining it ───────────────────────────────────────────

    /** The wall this change would have built, if the price were the only part. */
    public function test_a_professional_can_actually_join_the_free_plan(): void
    {
        $this->actingAs($this->pro)
            ->post(route('app.payments.initiate', $this->starter()))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'nothing to pay'));

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id'            => $this->pro->id,
            'membership_plan_id' => $this->starter()->id,
            'status'             => 'active',
        ]);
    }

    /**
     * No payment row, and no gateway call.
     *
     * "Do not make a monthly Stripe subscription for Starter." A $0 charge
     * through a gateway is a real API call, a fee in some configurations, and a
     * row in the money record that says nothing happened.
     */
    public function test_joining_the_free_plan_creates_no_payment(): void
    {
        $this->actingAs($this->pro)
            ->post(route('app.payments.initiate', $this->starter()));

        $this->assertSame(0, Payment::count());
    }

    /** A free plan that expires is a free plan that quietly stops working. */
    public function test_the_free_subscription_does_not_expire(): void
    {
        app(PaymentService::class)->activateFree($this->pro, $this->starter());

        $this->assertNull(
            UserSubscription::where('user_id', $this->pro->id)->first()->expires_at,
        );
    }

    /** Joining twice does not collect a second subscription. */
    public function test_joining_twice_is_the_same_subscription(): void
    {
        $service = app(PaymentService::class);

        $first  = $service->activateFree($this->pro, $this->starter());
        $second = $service->activateFree($this->pro->fresh(), $this->starter());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, UserSubscription::where('user_id', $this->pro->id)->count());
    }

    /** Switching off a paid plan ends the paid one, same as a paid switch. */
    public function test_switching_from_a_paid_plan_ends_it(): void
    {
        $pro = MembershipPlan::where('slug', 'professional')->firstOrFail();

        $paid = UserSubscription::create([
            'user_id'            => $this->pro->id,
            'membership_plan_id' => $pro->id,
            'status'             => 'active',
            'starts_at'          => now()->subMonth(),
            'expires_at'         => now()->addMonth(),
            'amount_paid'        => 39.99,
        ]);

        app(PaymentService::class)->activateFree($this->pro, $this->starter());

        $this->assertNotSame('active', $paid->fresh()->status);
    }

    /** A paid plan cannot be taken for free by calling the free path. */
    public function test_a_paid_plan_cannot_be_activated_for_free(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->activateFree(
            $this->pro,
            MembershipPlan::where('slug', 'professional')->firstOrFail(),
        );
    }
}
