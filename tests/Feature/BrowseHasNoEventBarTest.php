<?php

namespace Tests\Feature;

use App\Models\{Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Find Professionals does not carry the event-context bar.
 *
 * "Browsing — pick an event to keep it with you" is gone at the Owner's
 * request. The query behind it goes too: it ran on every page load of this
 * page to fill a control nothing else on the page read.
 *
 * This replaces BrowseEventBarSpacingTest, which asserted the bar was there
 * and correctly spaced — a true claim about a thing that no longer exists.
 */
class BrowseHasNoEventBarTest extends TestCase
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

    /** Even for the client it used to appear for — one with events. */
    public function test_a_client_with_events_does_not_see_it(): void
    {
        Event::create([
            'title' => 'Garden Reception', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addMonths(2),
        ]);

        $html = $this->actingAs($this->client)
            ->get(route('public.browse'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('pick an event to keep it with you', $html);
        $this->assertStringNotContainsString('Just browsing', $html);
        $this->assertStringNotContainsString('br-forevent', $html);
    }

    /** And an ?event= in the address is simply ignored rather than breaking. */
    public function test_the_old_event_parameter_still_loads_the_page(): void
    {
        $event = Event::create([
            'title' => 'Old Link', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addMonths(2),
        ]);

        $this->actingAs($this->client)
            ->get(route('public.browse', ['event' => $event->id]))
            ->assertSuccessful();
    }
}
