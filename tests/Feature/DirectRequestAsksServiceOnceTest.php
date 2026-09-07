<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-144 — the Direct Request form asked which service twice.
 *
 * A client picks a service at the top of the page to find professionals who
 * offer it. Further down, in single-service mode, they were asked again — a
 * second dropdown, no indication the two were connected, and free to
 * disagree with the first.
 *
 * When the choice has already been made, it is now shown as settled. The
 * dropdown survives for the other way onto this page: arriving from a
 * professional's own profile, where no service was chosen.
 */
class DirectRequestAsksServiceOnceTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    private function service(string $name): Category
    {
        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    /**
     * How many times the page ASKS for a service — dropdowns only.
     *
     * A hidden field carrying an answer already given is not a question, and
     * counting it as one made the corrected page look like the broken one.
     */
    private function timesAsked(string $html): int
    {
        return substr_count($html, '<select class="do-input" name="service_single"')
            + substr_count($html, "?service=' + this.value");
    }

    public function test_choosing_a_service_first_is_not_asked_again(): void
    {
        $photo = $this->service('Wedding Photography');
        $this->service('Catering');

        $html = $this->actingAs($this->client)
            ->get('/client/direct-offers/create?service='.$photo->id)
            ->assertOk()->getContent();

        // The choice is carried, not re-asked.
        $this->assertStringContainsString('name="service_single" value="Wedding Photography"', $html);
        $this->assertStringNotContainsString('<select class="do-input" name="service_single"', $html);
    }

    /**
     * And the other way in still works. A client who arrives from a
     * professional's profile has chosen nobody's service yet, so the question
     * has to be asked once — here.
     */
    public function test_arriving_with_no_service_is_asked_once_at_the_top(): void
    {
        $this->service('Wedding Photography');
        $this->service('Catering');

        $html = $this->actingAs($this->client)
            ->get('/client/direct-offers/create')
            ->assertOk()->getContent();

        // The top selector is the one that asks, because it also does
        // something: it finds the professionals who offer that service.
        $this->assertStringContainsString("?service=' + this.value", $html);

        // And the lower one does not ask the same thing beside it.
        $this->assertStringNotContainsString('<select class="do-input" name="service_single"', $html);
    }

    /** Whichever way in, the service is asked for exactly once. */
    public function test_the_page_never_asks_twice(): void
    {
        $photo = $this->service('Wedding Photography');

        foreach (['', '?service='.$photo->id] as $query) {
            $html = $this->actingAs($this->client)
                ->get('/client/direct-offers/create'.$query)
                ->assertOk()->getContent();

            $this->assertSame(
                1,
                $this->timesAsked($html),
                "asked more than once with query '{$query}'",
            );
        }
    }
}
