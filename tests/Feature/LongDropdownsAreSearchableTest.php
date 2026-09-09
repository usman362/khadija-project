<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A dropdown long enough to need looking through can be typed into.
 *
 * Event type is 106 occasions and the category filter on My Events is 375 —
 * a native <select> gives you no way to search either: you scroll, or you know
 * the first letter and hope.
 *
 * The enhancement upgrades the <select> that is already on the page rather
 * than replacing it, so the element stays the thing that submits and every
 * existing `change` listener still hears it. With the script off, the page is
 * exactly what it was.
 */
class LongDropdownsAreSearchableTest extends TestCase
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

    /** Every client page gets it, because it is on the layout. */
    public function test_client_pages_ship_the_enhancement(): void
    {
        foreach (['client.dashboard', 'client.events.index', 'client.esr.create'] as $route) {
            $html = $this->actingAs($this->client)->get(route($route))
                ->assertSuccessful()->getContent();

            $this->assertStringContainsString('ss-host', $html, "{$route} did not ship it.");
            $this->assertStringContainsString('MIN_OPTIONS', $html);
        }
    }

    /**
     * The select is upgraded, never replaced. If this ever became a rendered
     * widget instead, the form would stop submitting the field.
     */
    public function test_it_upgrades_the_select_rather_than_replacing_it(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();

        // The visible control is built from the select that is already there…
        $this->assertStringContainsString("querySelectorAll('select')", $html);
        // …and the page's own listeners are told, on the select itself.
        $this->assertStringContainsString("select.dispatchEvent(new Event('change'", $html);
    }

    /** Short lists stay native — a panel for four options is worse than a select. */
    public function test_short_lists_are_left_alone(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();

        $this->assertMatchesRegularExpression('/MIN_OPTIONS\s*=\s*[5-9]\b|MIN_OPTIONS\s*=\s*1[0-2]\b/', $html,
            'The threshold is missing or set somewhere unreasonable.');
    }

    /** The long ones on the request wizard really are long. */
    public function test_the_event_type_list_is_long_enough_to_need_this(): void
    {
        foreach (['Wedding', 'Baby Shower', 'Vow Renewal'] as $i => $name) {
            Category::create([
                'name' => $name, 'slug' => 'evt-'.$i,
                'kind' => Category::EVENT_TYPE, 'is_active' => true,
            ]);
        }

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('name="event_type"', $html);
        $this->assertStringContainsString('ss-host', $html);
    }
}
