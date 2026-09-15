<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah's four levels, and the one picker that reads them.
 *
 * Level 1 event type → level 2 category → level 3 service → optional level 4
 * detail. Two things about level 4 decide the shape of everything here
 * (Khadijah, 2026-09-15): the chosen detail is saved as a detail ON the
 * request, and professional matching stays at level 3. So a request carries
 * "Buffet Catering, Breakfast" and still reaches every buffet caterer.
 *
 * The other half of her answer was that the three request forms share one
 * picker, "so we maintain one taxonomy/search implementation rather than three
 * separate versions".
 */
class ServicePickerLevelFourTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $category;

    private Category $service;

    private Category $detail;

    private Category $eventType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD']);
        $this->client = $this->client->fresh();

        $this->category = Category::create([
            'name' => 'Catering & Food Services', 'slug' => 'catering-food-services-t',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $this->service = Category::create([
            'name' => 'Buffet Catering', 'slug' => 'buffet-catering-t', 'parent_id' => $this->category->id,
            'kind' => Category::SERVICE, 'is_active' => true,
            'search_terms' => 'Self-Serve Food Line For Wedding; All You Can Eat Party Buffet',
        ]);

        $this->detail = Category::create([
            'name' => 'Breakfast', 'slug' => 'buffet-catering-breakfast-t', 'parent_id' => $this->service->id,
            'kind' => Category::SERVICE_SPECIALTY, 'is_active' => true,
        ]);

        $this->eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-t', 'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);
    }

    private function saveServiceStep(array $override = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), $override + [
            'services'          => [$this->service->id],
            'service_details'   => [$this->service->id => $this->detail->id],
            'event_type'        => $this->eventType->name,
            'organization_type' => 'individual',
        ]);
    }

    /** The services are grouped under their category, with the detail beneath. */
    public function test_the_picker_shows_categories_services_and_their_details(): void
    {
        $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()
            ->assertSee('Catering &amp; Food Services', false)
            ->assertSee('Buffet Catering')
            ->assertSee('Breakfast')
            ->assertSee('service_details[' . $this->service->id . ']', false);
    }

    /**
     * The words a client types, not the name the taxonomy uses. "Self-serve
     * food line" has to reach Buffet Catering, and matching on the service
     * name alone never would.
     */
    public function test_the_search_terms_are_on_the_page(): void
    {
        $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()
            ->assertSee('self-serve food line for wedding', false);
    }

    /** The detail is saved on the request, against the service it belongs to. */
    public function test_the_chosen_detail_is_saved_on_the_request(): void
    {
        $this->saveServiceStep()->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->service->id => $this->detail->id],
            session('bsr_wizard')['service_details'],
        );
    }

    /**
     * Matching is unchanged. The request is still filed under the level 3
     * service, which is what a professional offers and what the board reads.
     */
    public function test_matching_stays_on_the_service(): void
    {
        $this->saveServiceStep();
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$this->service->id],
            'service_details'   => [$this->service->id => $this->detail->id],
            'event_type'        => $this->eventType->name,
            'organization_type' => 'individual',
            'action'            => 'draft',
        ]);

        $event = \App\Models\Event::where('client_id', $this->client->id)->firstOrFail();

        $this->assertSame([$this->service->id], $event->categories->pluck('id')->all());
        $this->assertSame($this->detail->id, (int) $event->categories->first()->pivot->specialty_id);
    }

    /** A detail filed under a different service is refused, not stored. */
    public function test_a_detail_from_another_service_is_refused(): void
    {
        $other = Category::create([
            'name' => 'Food Truck Booking', 'slug' => 'food-truck-booking-t',
            'parent_id' => $this->category->id, 'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->saveServiceStep([
            'services'        => [$other->id],
            'service_details' => [$other->id => $this->detail->id],
        ])->assertSessionHasErrors('service_details.' . $other->id);
    }

    /**
     * Unticking the service takes its detail with it. Otherwise a request that
     * no longer asks for catering still carries "Breakfast".
     */
    public function test_dropping_the_service_drops_its_detail(): void
    {
        $this->saveServiceStep();

        $other = Category::create([
            'name' => 'Live Bands', 'slug' => 'live-bands-t',
            'parent_id' => $this->category->id, 'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$other->id],
            'event_type'        => $this->eventType->name,
            'organization_type' => 'individual',
        ])->assertSessionHasNoErrors();

        $this->assertSame([], session('bsr_wizard')['service_details']);
    }

    /** The client's own words when the list has no name for what they need. */
    public function test_what_the_client_could_not_find_is_kept(): void
    {
        $this->saveServiceStep([
            'service_missing' => 'vintage car for the entrance',
            'action'          => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'vintage car for the entrance',
            \App\Models\Event::where('client_id', $this->client->id)->firstOrFail()->service_missing,
        );
    }

    /** One picker, three forms: bidding, direct and emergency. */
    public function test_all_three_request_forms_use_the_same_picker(): void
    {
        foreach ([
            'resources/views/client/bsr/wizard.blade.php',
            'resources/views/client/esr/create.blade.php',
            'resources/views/client/direct-offers/create.blade.php',
        ] as $view) {
            $this->assertStringContainsString(
                '<x-service-picker', file_get_contents(base_path($view)),
                "{$view} should use the shared picker.",
            );
        }
    }
}
