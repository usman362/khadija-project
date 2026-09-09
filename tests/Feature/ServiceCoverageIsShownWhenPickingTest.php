<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use App\Support\ServiceAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Whether anybody in the client's state offers a service is said at step 1,
 * where the service is chosen.
 *
 * It was said at step 7 — five screens after the choice, with the whole form
 * already filled in — and the only way to act on it was "go back to step 1",
 * which meant pressing Continue through every screen again.
 */
class ServiceCoverageIsShownWhenPickingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $covered;

    private Category $bare;

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

        $this->covered = Category::create([
            'name' => 'Covered Catering', 'slug' => 'covered-catering',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
        $this->bare = Category::create([
            'name' => 'Costumed Characters', 'slug' => 'costumed-characters',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        // Two in Maryland offering one service, one out of state offering it,
        // and nobody at all offering the other.
        foreach ([['MD', $this->covered], ['MD', $this->covered], ['DE', $this->bare]] as $i => [$state, $service]) {
            $pro = User::factory()->create(['primary_role' => 'professional']);
            $pro->assignRole('professional');
            $pro->getOrCreateProfile()->update([
                'country' => 'US', 'state' => $state, 'city' => 'Somewhere',
                'service_area_status' => ServiceArea::SUPPORTED,
            ]);
            $pro->serviceCategories()->sync([$service->id]);
        }
    }

    public function test_it_counts_only_professionals_in_the_clients_state(): void
    {
        $counts = ServiceAvailability::countsByService(
            [$this->covered->id, $this->bare->id],
            'MD',
        );

        $this->assertSame(2, $counts[$this->covered->id] ?? 0);

        // The one who offers it is in Delaware, and a Maryland client cannot
        // hire them — so for this client the answer is none.
        $this->assertSame(0, $counts[$this->bare->id] ?? 0);
    }

    public function test_the_count_is_beside_each_service_on_step_one(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('data-pros="2"', $html);
        $this->assertStringContainsString('data-pros="0"', $html);
        $this->assertStringContainsString('bw-svc-pros', $html);
    }

    /** The warning is on the step that has the choice, not five steps later. */
    public function test_step_one_carries_the_warning(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->getContent();

        $this->assertStringContainsString('bwNoPros', $html);
        $this->assertStringContainsString('No professional in your state offers', $html);
    }

    /** Sent back to fix one thing, returned to where you were. */
    public function test_change_services_makes_a_round_trip(): void
    {
        $eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-cov',
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);

        // Step 7 offers the way back, and says where it goes.
        $step = $this->actingAs($this->client)
            ->get(route('client.bsr.step', ['step' => 'service', 'return' => 'availability']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Continue takes you straight back to Availability Match', $step);
        $this->assertStringContainsString('name="return" value="availability"', $step);

        // And saving that step goes there rather than to step 2.
        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'service'), [
                'services' => [$this->covered->id],
                'event_type' => $eventType->name,
                'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
                'return' => 'availability',
            ])
            ->assertRedirect(route('client.bsr.step', 'availability'));
    }

    /** A return value that is not a step of this form is ignored. */
    public function test_a_made_up_return_step_is_ignored(): void
    {
        $eventType = Category::create([
            'name' => 'Gala', 'slug' => 'gala-cov',
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'service'), [
                'services' => [$this->covered->id],
                'event_type' => $eventType->name,
                'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
                'return' => 'https://example.com/elsewhere',
            ])
            ->assertRedirect(route('client.bsr.step', 'event'));
    }
}
