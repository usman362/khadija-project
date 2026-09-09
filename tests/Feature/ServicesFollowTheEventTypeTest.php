<?php

namespace Tests\Feature;

use App\Models\{Category, CategoryRelevance, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The services really are ordered by what the event usually needs.
 *
 * Sir Peter, 2026-09-09: "if the user goes on the BR webpage then selects an
 * event, will or shouldn't the L3 categories or options list change with it?"
 *
 * The line under that dropdown has said they are ordered that way all along.
 * They were not — the catalogue came back alphabetical whatever was chosen, so
 * the page was making a claim it did not keep.
 *
 * Ordered, not filtered. A wedding can still want something the matrix calls
 * occasional, and the matrix says occasional, not forbidden — hiding it would
 * mean a client planning something unusual concluding we do not offer it.
 */
class ServicesFollowTheEventTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $eventType;

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

        $this->eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-order',
            'kind' => Category::EVENT_TYPE, 'archetype' => 'Wedding', 'is_active' => true,
        ]);

        // Two categories: one the matrix calls essential for a wedding, one it
        // does not rank at all. Their services are named so that alphabetical
        // order and relevance order disagree — otherwise the test passes
        // whichever the page is doing.
        $essential = Category::create([
            'name' => 'Photography', 'slug' => 'photography-order',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);
        $unranked = Category::create([
            'name' => 'Waste', 'slug' => 'waste-order',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        Category::create([
            'name' => 'Zebra Photography', 'slug' => 'zebra-photography',
            'kind' => Category::SERVICE, 'parent_id' => $essential->id, 'is_active' => true,
        ]);
        Category::create([
            'name' => 'Abbey Waste Removal', 'slug' => 'abbey-waste',
            'kind' => Category::SERVICE, 'parent_id' => $unranked->id, 'is_active' => true,
        ]);

        CategoryRelevance::create([
            'archetype' => 'Wedding', 'category_id' => $essential->id, 'tier' => 'Essential',
        ]);

        \App\Domain\Taxonomy\ServiceRelevance::forget();
    }

    private function step(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();
    }

    private function order(string $html): array
    {
        preg_match_all('/data-name="([^"]+)"/', $html, $m);

        return $m[1];
    }

    /** With no event type chosen there is nothing to rank by, so A to Z. */
    public function test_without_an_event_type_the_list_is_alphabetical(): void
    {
        $order = $this->order($this->step());

        $this->assertLessThan(
            array_search('Zebra Photography', $order, true),
            array_search('Abbey Waste Removal', $order, true),
        );
    }

    /** Once one is chosen, what that event needs comes first. */
    public function test_choosing_an_event_type_puts_what_it_needs_first(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [Category::where('slug', 'zebra-photography')->value('id')],
            'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);

        $order = $this->order($this->step());

        $this->assertLessThan(
            array_search('Abbey Waste Removal', $order, true),
            array_search('Zebra Photography', $order, true),
            'The list is still alphabetical, so the line under the dropdown is still untrue.',
        );
    }

    /** Ordered, never filtered — everything is still on the page. */
    public function test_nothing_is_hidden_by_the_ordering(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [Category::where('slug', 'zebra-photography')->value('id')],
            'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);

        $order = $this->order($this->step());

        $this->assertContains('Abbey Waste Removal', $order,
            'A service the matrix calls unranked was hidden. Occasional is not forbidden.');
    }

    /** And it follows the dropdown without waiting for the step to be saved. */
    public function test_the_list_reorders_when_the_dropdown_changes(): void
    {
        $html = $this->step();

        $this->assertStringContainsString('data-parent=', $html,
            'Rows carry no category, so the browser cannot rank them.');
        $this->assertStringContainsString("typeEl.addEventListener('change', reorder)", $html,
            'Changing the event type does not reorder anything until the step is saved.');
    }
}
