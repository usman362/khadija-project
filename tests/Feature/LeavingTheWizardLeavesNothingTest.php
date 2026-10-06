<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ali, 6 October: "yeh already selected q araha hai, mene exit button bhi
 * press kia tha aur clear all bhi, to faida kia in buttons ka."
 *
 * Exit was a link to My Events and nothing more, so the half-built request
 * stayed in the session and coming back silently resumed it, with services
 * the client had deliberately cleared still ticked. Two buttons that both
 * looked like they undid something, and neither did.
 *
 * Exit discards it now. Keeping a part-built request is what Save draft is
 * for, and that button is right beside it.
 */
class LeavingTheWizardLeavesNothingTest extends TestCase
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

    /** Leaving throws the half-built request away. */
    public function test_exit_discards_the_request(): void
    {
        $this->actingAs($this->client)
            ->withSession(['bsr_wizard' => ['services' => [665], 'title' => 'Half a request']])
            ->post(route('client.bsr.exit'))
            ->assertRedirect(route('client.events.index'))
            ->assertSessionMissing('bsr_wizard');
    }

    /** And says so, because silently throwing work away is worse. */
    public function test_it_says_the_request_was_discarded(): void
    {
        $this->actingAs($this->client)
            ->withSession(['bsr_wizard' => ['services' => [665]]])
            ->post(route('client.bsr.exit'))
            ->assertSessionHas('status', 'Request discarded. Nothing was saved, and nothing was sent.');
    }

    /** Leaving an empty wizard is not an event worth announcing. */
    public function test_leaving_with_nothing_entered_says_nothing(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.bsr.exit'))
            ->assertRedirect(route('client.events.index'))
            ->assertSessionMissing('bsr_wizard');
    }

    /** Coming back afterwards starts clean. */
    public function test_the_wizard_starts_empty_after_exit(): void
    {
        $this->actingAs($this->client)
            ->withSession(['bsr_wizard' => ['services' => [665], 'title' => 'Half a request']])
            ->post(route('client.bsr.exit'));

        $this->actingAs($this->client)
            ->get('/client/bsr/service')
            ->assertOk()
            ->assertDontSee('Half a request');
    }

    /** Exit is a button that does something, not a link that leaves. */
    public function test_exit_is_wired_to_the_action(): void
    {
        $markup = file_get_contents(base_path('resources/views/client/bsr/wizard.blade.php'));

        $this->assertStringContainsString("route('client.bsr.exit')", $markup,
            'Exit is a plain link again, so it leaves the request behind.');
    }

    /**
     * The per-service timeline is on the availability step, and it is there
     * for one service as well as for several.
     *
     * Asked for twice as missing, both times from the budget step, which is
     * three steps earlier. It was never on that step: Sir Peter put it on the
     * one that already asks when the event runs, and said so himself.
     */
    public function test_the_timeline_is_on_the_availability_step_even_for_one_service(): void
    {
        $cat = \App\Models\Category::create([
            'name' => 'Uplighting & Ambient Lighting',
            'slug' => 'uplighting-timeline-test',
        ]);

        $this->actingAs($this->client)
            ->withSession(['bsr_wizard' => [
                'services'          => [$cat->id],
                'organization_type' => 'Individual',
                'title'             => 'One service request',
                'description'       => 'A description long enough to pass the step guard.',
            ]])
            ->get('/client/bsr/availability')
            ->assertOk()
            ->assertSee('Service schedule / Timeline', false)
            ->assertSee('Uplighting &amp; Ambient Lighting', false);
    }
}
