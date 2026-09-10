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

        // Sir Peter's three answers, 11 Sep.
        $this->assertStringContainsString('name="location_kind"', $html);
        $this->assertStringContainsString('Use my address', $html);
        $this->assertStringContainsString('Enter a different address', $html);
        $this->assertStringContainsString('know the exact address yet', $html);
    }

    /** Their own address is offered as a choice, shown in full. */
    public function test_their_own_address_is_offered(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('12 Harbour Row', $html);
        $this->assertMatchesRegularExpression('/value="mine"[^>]*checked/', $html,
            'Someone with an address on file should start on "Use my address".');
    }

    /** "Use my address" stores the address on the profile, not what is in the box. */
    public function test_use_my_address_stores_the_profile_address(): void
    {
        $this->startWizard();

        $this->step(['location_kind' => 'mine', 'location' => 'ignored'])->assertSessionHasNoErrors();

        $data = session('bsr_wizard');
        $this->assertStringContainsString('12 Harbour Row', $data['location']);
        $this->assertSame('exact', $data['location_kind']);
    }

    private function wizard(): array
    {
        return (array) session('bsr_wizard');
    }

    /**
     * Sir Peter, 11 Sep: the name is built, not asked for twice. It grows as
     * the area and the date become known.
     */
    public function test_the_name_is_built_from_the_answers(): void
    {
        $this->startWizard();
        $type = $this->wizard()['event_type'];

        // Before any location is given, the town on their profile stands in.
        $this->assertSame("{$type} · Baltimore", $this->wizard()['title']);

        $this->step(['location_kind' => 'area', 'location' => 'Annapolis, MD', 'starts_at' => '2027-10-09T18:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame("{$type} · Annapolis · October 2027", $this->wizard()['title']);
    }

    /** A street address contributes its town, not its street. */
    public function test_a_street_address_names_the_town(): void
    {
        $this->startWizard();

        $this->step(['location_kind' => 'exact', 'location' => '1234 Garden Way, Towson, MD 21204'])
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString('· Towson', $this->wizard()['title']);
    }

    /** Once renamed on the review step, the client's own name sticks. */
    public function test_a_rename_sticks(): void
    {
        $this->startWizard();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'review'), [
            'title' => 'Harbour Gala', 'confirm' => '1', 'action' => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Harbour Gala', $this->wizard()['title']);

        $this->step(['location_kind' => 'area', 'location' => 'Annapolis, MD'])->assertSessionHasNoErrors();

        $this->assertSame('Harbour Gala', $this->wizard()['title']);
    }

    /** Two services, so there is a split to make. Returns their ids. */
    private function startMulti(): array
    {
        $a = \App\Models\Category::create(['name' => 'Split DJ', 'slug' => 'split-dj', 'kind' => \App\Models\Category::SERVICE, 'is_active' => true]);
        $b = \App\Models\Category::create(['name' => 'Split Photo', 'slug' => 'split-photo', 'kind' => \App\Models\Category::SERVICE, 'is_active' => true]);
        $type = \App\Models\Category::where('kind', \App\Models\Category::EVENT_TYPE)->first()
            ?? \App\Models\Category::create(['name' => 'Wedding', 'slug' => 'wedding-split', 'kind' => \App\Models\Category::EVENT_TYPE, 'is_active' => true]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$a->id, $b->id],
            'event_type'        => $type->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ])->assertSessionHasNoErrors();

        return [$a->id, $b->id];
    }

    private function budget(array $payload)
    {
        return $this->actingAs($this->client)->post(route('client.bsr.save', 'budget'), $payload);
    }

    /** Sir Peter, 11 Sep: the split has to come to the top of the range. */
    public function test_a_split_that_does_not_add_up_is_refused(): void
    {
        [$a, $b] = $this->startMulti();

        $this->budget(['budget_min' => 800, 'budget_max' => 1200, 'service_budgets' => [$a => 700, $b => 700]])
            ->assertSessionHasErrors(['service_budgets' => 'The split adds up to $1,400 but your budget is $1,200. It is $200 over.']);
    }

    public function test_a_split_that_adds_up_is_accepted(): void
    {
        [$a, $b] = $this->startMulti();

        $this->budget(['budget_min' => 800, 'budget_max' => 1200, 'service_budgets' => [$a => 500, $b => 700]])
            ->assertSessionHasNoErrors();
    }

    /** Half a split would read as "nothing" for the blank services. */
    public function test_a_half_filled_split_is_refused(): void
    {
        [$a, $b] = $this->startMulti();

        $this->budget(['budget_max' => 1200, 'service_budgets' => [$a => 1200, $b => '']])
            ->assertSessionHasErrors('service_budgets');
    }

    public function test_no_split_at_all_is_fine(): void
    {
        [$a, $b] = $this->startMulti();

        $this->budget(['budget_max' => 1200, 'service_budgets' => [$a => '', $b => '']])
            ->assertSessionHasNoErrors();
    }

    /** With only one figure given, that figure is what the split must reach. */
    public function test_a_single_figure_is_the_target(): void
    {
        [$a, $b] = $this->startMulti();

        $this->budget(['budget_min' => 1000, 'service_budgets' => [$a => 400, $b => 600]])
            ->assertSessionHasNoErrors();
    }

    /** A profile with only a city has no street address to offer. */
    public function test_no_street_address_means_no_use_my_address(): void
    {
        $this->client->profile->update(['address' => null]);
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->getContent();

        $this->assertStringNotContainsString('value="mine"', $html);
        $this->step(['location_kind' => 'mine'])->assertSessionHasErrors('location');
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

    /**
     * The script that drives this field is on the step that has the field.
     *
     * It was pushed from inside the Availability branch, so it was only ever
     * output on step 7 — on Event Details the radios changed nothing, which is
     * how it was reported: "I know the address" does not work.
     */
    public function test_the_location_script_reaches_the_event_step(): void
    {
        $this->startWizard();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('data-bw-loclabel', $html);
        $this->assertStringContainsString("querySelectorAll('input[name=\"location_kind\"]')", $html,
            'The radios have no listener on the step that shows them.');
    }

    /**
     * The budget step's own script reaches the budget step.
     *
     * The running total and "Suggest a split" were pushed from inside the
     * Availability branch too, so on step 4 neither existed: the breakdown
     * never added up and the button did nothing.
     */
    public function test_the_budget_script_reaches_the_budget_step(): void
    {
        $this->startWizard();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'event'), [
            'title' => 'Test Request', 'location_kind' => 'area', 'location' => 'Baltimore, MD',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'requirements'), [
            'description' => 'We need photography and video coverage for a company picnic in Baltimore.',
        ])->assertSessionHasNoErrors();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'budget'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString("querySelector('[data-bw-suggest]')", $html,
            'Suggest a split has no listener on the step that shows the button.');
        $this->assertStringContainsString('function retotal', $html,
            'The breakdown cannot add up: the code that adds it up is not on this step.');
    }
}
