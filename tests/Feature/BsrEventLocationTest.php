<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The request wizard asked "Location" with one free-text box and "Baltimore, MD"
 * in it, so every request stored a city and nothing else.
 *
 * The database has carried location_lat, location_lng, location_zip and
 * location_precision all along, and the Event model geocodes on save — but a
 * city name places as 'unresolved'. A distance from a professional cannot be
 * worked out from a city, so it never could be. Peter asked for two options:
 * the area, or the exact address.
 */
class BsrEventLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'address' => '12 Harbour Row', 'zip_code' => '21201',
        ]);
        $this->client = $this->client->fresh();
    }

    /**
     * The wizard will not open a later step until the first is answered, so the
     * service step is filled in before anything here looks at Event Details.
     */
    private function startWizard(): void
    {
        $service = \App\Models\Category::where('kind', \App\Models\Category::SERVICE)->first()
            ?? \App\Models\Category::create([
                'name' => 'DJ Services', 'slug' => 'dj-services-test',
                'kind' => \App\Models\Category::SERVICE, 'is_active' => true,
            ]);

        $eventType = \App\Models\Category::where('kind', \App\Models\Category::EVENT_TYPE)->first()
            ?? \App\Models\Category::create([
                'name' => 'Wedding', 'slug' => 'wedding-test',
                'kind' => \App\Models\Category::EVENT_TYPE, 'is_active' => true,
            ]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$service->id],
            'event_type'        => $eventType->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);
    }

    private function step(array $payload)
    {
        return $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'event'), $payload);
    }

    public function test_the_form_offers_both_ways_of_answering(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('name="location_kind"', $html);
        $this->assertStringContainsString('I know the address', $html);
        $this->assertStringContainsString('Only the area so far', $html);
    }

    /** Peter: ask whether it is their own address rather than making them retype it. */
    public function test_their_own_address_is_offered(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('12 Harbour Row', $html);
        $this->assertStringContainsString('Is it at your own address?', $html);
    }

    /** Claiming an exact address and typing a city is the silent version of the old bug. */
    public function test_a_city_is_refused_when_they_said_they_knew_the_address(): void
    {
        $this->step([
            'title' => 'Harbour Gala', 'location_kind' => 'exact', 'location' => 'Baltimore, MD',
        ])->assertSessionHasErrors('location');
    }

    public function test_a_real_address_is_accepted(): void
    {
        $this->step([
            'title' => 'Harbour Gala', 'location_kind' => 'exact',
            'location' => '1234 Garden Way, Baltimore, MD 21201',
        ])->assertSessionHasNoErrors();
    }

    /** Still looking for a venue is a real answer, not a mistake. */
    public function test_an_area_is_accepted_when_that_is_what_they_chose(): void
    {
        $this->step([
            'title' => 'Harbour Gala', 'location_kind' => 'area', 'location' => 'Baltimore, MD',
        ])->assertSessionHasNoErrors();
    }

    public function test_an_empty_location_is_still_allowed(): void
    {
        $this->step(['title' => 'Harbour Gala', 'location_kind' => 'exact', 'location' => ''])
            ->assertSessionHasNoErrors();
    }

    /**
     * A rejected step comes back with what they typed still in the box.
     *
     * The "that looks like an area" check refuses the step, so nothing is
     * saved — and the form read only the saved state. The client picked
     * "I know the address", typed a city, got the error, and found the box
     * empty and the choice reset to whatever the empty box implied. Nothing
     * they did appeared to have any effect, which is exactly how it was
     * reported: clicking "I know the address" does nothing.
     */
    public function test_a_rejected_address_is_still_in_the_box(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->from(route('client.bsr.step', 'event'))
            ->post(route('client.bsr.save', 'event'), [
                'title' => 'Annual Company Picnic',
                'location_kind' => 'exact',
                'location' => 'Baltimore, MD',
            ])
            ->assertSessionHasErrors('location')
            ->assertRedirect(route('client.bsr.step', 'event'))
            ->getTargetUrl();

        $back = $this->actingAs($this->client)->get($html)->getContent();

        $this->assertStringContainsString('value="Baltimore, MD"', $back,
            'The rejected address was dropped, so the client cannot see what to correct.');

        $this->assertMatchesRegularExpression(
            '/value="exact"[^>]*checked/',
            $back,
            'The choice they made was reset, so picking it again looks like it does nothing.',
        );
    }
}
