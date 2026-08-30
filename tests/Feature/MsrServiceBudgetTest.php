<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A multi-service request had one budget and per-service bids.
 *
 * bids.category_id has always named the single service a professional is
 * bidding on, while events.budget was one figure for the whole request. So on
 * five services with a $10,000 budget, five different professionals were each
 * shown $10,000 — and a DJ priced against a number that was never meant for
 * them. Khadijah raised it on 2026-08-30.
 */
class MsrServiceBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function service(string $name): Category
    {
        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    private function event(User $client): Event
    {
        return Event::create([
            'title' => 'Harbour Gala', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'starts_at' => now()->addDays(30), 'budget' => 10000,
        ]);
    }

    public function test_each_service_can_carry_its_own_figure(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');
        $food   = $this->service('Buffet Catering');

        $event->serviceBudgets()->createMany([
            ['category_id' => $dj->id,   'amount' => 1800],
            ['category_id' => $food->id, 'amount' => 6500],
        ]);

        $event->refresh();

        // The number a DJ should be pricing against — not the event total.
        $this->assertSame(1800.0, $event->budgetForService($dj->id));
        $this->assertSame(6500.0, $event->budgetForService($food->id));
        $this->assertSame(10000.0, (float) $event->budget);
    }

    /** A service the client did not break down says so, rather than guessing. */
    public function test_a_service_with_no_figure_returns_null(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $florist = $this->service('Floral Design');

        $this->assertNull($event->budgetForService($florist->id));
    }

    public function test_one_figure_per_service_per_event(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');

        $event->serviceBudgets()->create(['category_id' => $dj->id, 'amount' => 1800]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $event->serviceBudgets()->create(['category_id' => $dj->id, 'amount' => 2500]);
    }

    /** Deleting the request takes its breakdown with it. */
    public function test_the_breakdown_goes_with_the_event(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');

        $event->serviceBudgets()->create(['category_id' => $dj->id, 'amount' => 1800]);
        $id = $event->id;

        $event->forceDelete();

        $this->assertDatabaseMissing('event_service_budgets', ['event_id' => $id]);
    }

    /** The wizard writes only for services the request actually asked for. */
    public function test_a_figure_for_a_service_not_requested_is_ignored(): void
    {
        $client = $this->client();
        $event  = $this->event($client);

        $dj        = $this->service('DJ Services');
        $unrelated = $this->service('Trade Show Booths');

        $controller = app(\App\Http\Controllers\Client\ClientBsrController::class);
        $method = new \ReflectionMethod($controller, 'saveServiceBudgets');
        $method->setAccessible(true);
        $method->invoke($controller, $event, [
            'services'        => [$dj->id, $this->service('Buffet Catering')->id],
            'service_budgets' => [$dj->id => 1800, $unrelated->id => 9999],
        ]);

        $event->refresh();

        $this->assertSame(1800.0, $event->budgetForService($dj->id));
        $this->assertNull($event->budgetForService($unrelated->id),
            'A budget was attached to a service nobody is bidding on.');
    }

    /**
     * Dropping a service must take its money with it. Otherwise the figure sits
     * on the request waiting to be shown to somebody bidding on something else.
     */
    public function test_removing_a_service_removes_its_figure(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');
        $food   = $this->service('Buffet Catering');

        $event->serviceBudgets()->createMany([
            ['category_id' => $dj->id,   'amount' => 1800],
            ['category_id' => $food->id, 'amount' => 6500],
        ]);

        $controller = app(\App\Http\Controllers\Client\ClientBsrController::class);
        $method = new \ReflectionMethod($controller, 'saveServiceBudgets');
        $method->setAccessible(true);

        // Catering dropped on a later pass through the wizard.
        $method->invoke($controller, $event, [
            'services'        => [$dj->id, $this->service('Floral Design')->id],
            'service_budgets' => [$dj->id => 1800],
        ]);

        $event->refresh();

        $this->assertSame(1800.0, $event->budgetForService($dj->id));
        $this->assertNull($event->budgetForService($food->id));
    }

    /** A single-service request has one budget and nothing to divide. */
    public function test_a_single_service_request_gets_no_breakdown(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');

        $controller = app(\App\Http\Controllers\Client\ClientBsrController::class);
        $method = new \ReflectionMethod($controller, 'saveServiceBudgets');
        $method->setAccessible(true);
        $method->invoke($controller, $event, [
            'services'        => [$dj->id],
            'service_budgets' => [$dj->id => 1800],
        ]);

        $this->assertSame(0, $event->fresh()->serviceBudgets()->count());
    }

    /**
     * The point of the whole feature: the professional sees the line for what
     * THEY do, not the request's total.
     */
    public function test_a_professional_sees_the_budget_for_their_own_service(): void
    {
        $client = $this->client();
        $event  = $this->event($client);

        $dj   = $this->service('DJ Services');
        $food = $this->service('Buffet Catering');
        $event->categories()->sync([$dj->id, $food->id]);

        $event->serviceBudgets()->createMany([
            ['category_id' => $dj->id,   'amount' => 1800],
            ['category_id' => $food->id, 'amount' => 6500],
        ]);

        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $pro->serviceCategories()->sync([$dj->id]);

        $html = $this->actingAs($pro->fresh())
            ->get(route('professional.gigs.show', $event))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Budget for your part', $html);
        $this->assertStringContainsString('1,800.00', $html);

        // The caterer's line is not this professional's business.
        $this->assertStringNotContainsString('6,500.00', $html);
    }

    /** With no breakdown, nothing is invented — the total stands as before. */
    public function test_without_a_breakdown_the_overall_budget_is_shown(): void
    {
        $client = $this->client();
        $event  = $this->event($client);
        $dj     = $this->service('DJ Services');
        $event->categories()->sync([$dj->id]);

        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $pro->serviceCategories()->sync([$dj->id]);

        $html = $this->actingAs($pro->fresh())
            ->get(route('professional.gigs.show', $event))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('Budget for your part', $html);
        $this->assertStringContainsString('10,000.00', $html);
    }
}
