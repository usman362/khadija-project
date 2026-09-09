<?php

namespace Tests\Feature;

use App\Models\{Category, Event, EventServiceBudget, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-08: a page an MSR touched shows the MSR's own data.
 *
 * The client breaks the budget down service by service on the request form,
 * and that breakdown is written and shown to the professional bidding on each
 * service. Their own event page listed the services as one comma-separated
 * line under a single total — so a client could enter the split and then never
 * see it again anywhere in their own portal.
 */
class MsrBreakdownIsShownToTheClientTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();
    }

    private function multiServiceEvent(): Event
    {
        $event = Event::create([
            'title' => 'Garden Reception', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addMonths(3), 'budget' => 9000,
        ]);

        foreach ([['Catering', 5000], ['Photography', 4000]] as $i => [$name, $amount]) {
            $cat = Category::create([
                'name' => $name, 'slug' => 'msr-'.$i,
                'kind' => Category::SERVICE, 'is_active' => true,
            ]);
            $event->categories()->attach($cat->id);
            EventServiceBudget::create([
                'event_id' => $event->id, 'category_id' => $cat->id, 'amount' => $amount,
            ]);
        }

        return $event->fresh();
    }

    public function test_the_client_sees_what_each_service_was_worth(): void
    {
        $event = $this->multiServiceEvent();

        $html = $this->actingAs($this->client)
            ->get(route('client.events.show', $event) . '?tab=requirements')
            ->assertSuccessful()
            ->getContent();

        // Each service, with its own figure — not one line and one total.
        $this->assertStringContainsString('Catering', $html);
        $this->assertStringContainsString('$5,000', $html);
        $this->assertStringContainsString('Photography', $html);
        $this->assertStringContainsString('$4,000', $html);

        // And the total of the split, so it can be checked against the budget.
        $this->assertStringContainsString('Breakdown total', $html);
        $this->assertStringContainsString('$9,000', $html);
    }

    /** A single-service request has nothing to break down and gets no table. */
    public function test_a_request_with_no_split_reads_as_before(): void
    {
        $event = Event::create([
            'title' => 'One thing', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addMonths(3), 'budget' => 1200,
        ]);
        $cat = Category::create([
            'name' => 'Photography Only', 'slug' => 'ssr-only',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
        $event->categories()->attach($cat->id);

        $html = $this->actingAs($this->client)
            ->get(route('client.events.show', $event->fresh()) . '?tab=requirements')
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Photography Only', $html);
        $this->assertStringNotContainsString('Breakdown total', $html);
    }
}
