<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Find Professionals offers services, and only services.
 *
 * Its "All Services" dropdown was every active row of every kind: the
 * categories, the services, the specialties and the event types, 1,234
 * options in one flat list. Two things were wrong with that beyond the
 * length. The specialties repeat by design, so "Attendant" appeared seven
 * times under seven different services with nothing to tell them apart. And
 * an event type is an occasion, not something anybody offers, so a client
 * could pick "Baby Shower" from a list of services.
 */
class TheServicesDropdownOffersServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** Browsing is behind a sign-in, so the test has to be somebody. */
    private function client(): User
    {
        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function tree(): void
    {
        $category = Category::create([
            'name' => 'Catering & Food Services', 'slug' => 'catering-food-dd',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $service = Category::create([
            'name' => 'Buffet Catering', 'slug' => 'buffet-catering-dd',
            'kind' => Category::SERVICE, 'parent_id' => $category->id, 'is_active' => true,
        ]);

        // A specialty, which repeats under other services and is not pickable.
        Category::create([
            'name' => 'Attendant', 'slug' => 'attendant-dd',
            'kind' => Category::SERVICE_SPECIALTY, 'parent_id' => $service->id, 'is_active' => true,
        ]);

        // An occasion. Nobody offers one.
        Category::create([
            'name' => 'Baby Shower', 'slug' => 'baby-shower-dd',
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);
    }

    public function test_it_lists_services_under_their_category(): void
    {
        $this->tree();

        $this->actingAs($this->client())->get(route('public.browse'))
            ->assertOk()
            ->assertSee('<optgroup label="Catering &amp; Food Services">', false)
            ->assertSee('Buffet Catering');
    }

    public function test_an_occasion_is_not_offered_as_a_service(): void
    {
        $this->tree();

        $html = $this->actingAs($this->client())->get(route('public.browse'))->assertOk()->getContent();

        // Narrowed to the dropdown: the page may legitimately name an event
        // type elsewhere, and it is this control the rule is about.
        $start = strpos($html, 'name="category"');
        $select = substr($html, $start, strpos($html, '</select>', $start) - $start);

        $this->assertStringNotContainsString('Baby Shower', $select);
        $this->assertStringNotContainsString('Attendant', $select);
    }
}
