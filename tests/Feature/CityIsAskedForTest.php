<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 27 September: "data for the city and state will need to be input
 * by the user, so that the user doesn't give just the street address, which i
 * did and saw it went thru... the street names might overlap."
 *
 * He typed "682 kirkcaldy way" and the request was accepted. From there every
 * step had to work out which town that was from a name several towns share,
 * which is how a request ends up placed in the wrong place, or nowhere.
 *
 * The state is a different answer and is shown rather than asked: it was a
 * field once and came out on 2026-08-25, because every request is matched by
 * the client's own state and choosing another changed nothing.
 */
class CityIsAskedForTest extends TestCase
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
        $this->client = User::findOrFail($this->client->id);
    }

    /** The wizard only lets you reach a step once the ones before it are done. */
    private function startWizard(): void
    {
        $service = \App\Models\Category::create([
            'name' => 'DJ Services', 'slug' => 'city-asked-dj',
            'kind' => \App\Models\Category::SERVICE, 'is_active' => true,
        ]);
        $eventType = \App\Models\Category::create([
            'name' => 'Wedding', 'slug' => 'city-asked-wedding',
            'kind' => \App\Models\Category::EVENT_TYPE, 'is_active' => true,
        ]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$service->id],
            'event_type'        => $eventType->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);
    }

    private function saveVenueStep(array $fields)
    {
        return $this->actingAs($this->client)
            ->from(route('client.bsr.step', 'event'))
            ->post(route('client.bsr.save', 'event'), $fields + ['location_kind' => 'exact']);
    }

    public function test_a_street_on_its_own_is_refused(): void
    {
        $this->saveVenueStep(['location' => '682 kirkcaldy way'])
            ->assertSessionHasErrors('city');
    }

    public function test_the_street_with_its_town_goes_through(): void
    {
        $this->saveVenueStep(['location' => '682 kirkcaldy way', 'city' => 'Bel Air'])
            ->assertSessionHasNoErrors();
    }

    /** Leaving the whole address for later still asks for nothing. */
    public function test_no_address_at_all_asks_for_no_city(): void
    {
        $this->saveVenueStep(['location' => ''])->assertSessionHasNoErrors();
    }

    public function test_the_step_asks_for_the_city_and_shows_the_state(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))->assertOk()->getContent();

        $this->assertStringContainsString('name="city"', $html);
        $this->assertStringContainsString('Maryland', $html, 'The state is shown.');

        // Shown, not chosen: a control that changed nothing was taken off this
        // step once already.
        $this->assertStringNotContainsString('name="event_state"', $html);
        $this->assertStringContainsString('id="bw_state" value="Maryland" readonly', $html);
    }

    /** The town goes to the geocoder with the street, instead of being parsed out of it. */
    public function test_the_city_is_part_of_what_is_placed(): void
    {
        $event = Event::create([
            'title' => 'Garden Reception', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'draft', 'is_published' => false,
            'location' => '682 Kirkcaldy Way', 'city' => 'Bel Air', 'state' => 'MD',
        ]);

        $this->assertSame('Bel Air', $event->fresh()->city);

        $model = new \ReflectionMethod($event, 'applyLocationGeocode');
        $this->assertTrue($model->isPublic(), 'The placement is still done on the model.');

        $source = file_get_contents(base_path('app/Models/Event.php'));
        $this->assertStringContainsString("trim((string) \$this->city) ?: null,", $source);
    }
}
