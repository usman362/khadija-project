<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direct Request — choosing the professional.
 *
 * Ali reported the picker as "not working". Three separate faults sat on top
 * of each other:
 *
 *  1. `$selectedPro` fell back to `$pros->first()`. Pick a service and the
 *     form quietly addressed itself to whoever the database returned first —
 *     the client could send a request to somebody they never chose.
 *
 *  2. The Service section renders `@unless($selectedPro)`. Because (1) always
 *     set one, choosing a service made the service picker vanish: a one-way
 *     door with no way back to change it.
 *
 *  3. When nobody matched — or before any service was chosen — the page still
 *     rendered a focusable "Send to" select containing no options at all,
 *     directly beneath a sentence explaining that there was nobody. An empty
 *     select is not an empty state.
 *
 * A fourth, smaller one: the control carried `aria-label="id }}' > —"`, a
 * botched find/replace read aloud verbatim by a screen reader.
 */
class DirectRequestPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(string $state = 'MD'): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function pro(string $name, ?Category $service = null, string $state = 'MD'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('professional');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        if ($service) {
            $user->serviceCategories()->attach($service->id);
        }

        return $user->fresh();
    }

    private function service(string $name = 'Full-Service Catering'): Category
    {
        $v2 = config('taxonomy.version', 'v1') === 'v2';

        $group = Category::create([
            'name' => 'Catering & Food Services', 'slug' => 'catering-'.uniqid(), 'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE_CATEGORY] : []));

        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'parent_id' => $group->id, 'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE] : []));
    }

    /** Nobody is chosen for the client. */
    public function test_no_professional_is_preselected_when_only_a_service_is_chosen(): void
    {
        $service = $this->service();
        $this->pro('Saffron Table Catering', $service);
        $this->pro('Jordan Lee Photography', $service);

        $response = $this->actingAs($this->client())
            ->get(route('client.direct-offers.create', ['service' => $service->id]));

        $response->assertSuccessful();
        $response->assertSee('Choose who this goes to');
        // Neither option carries `selected` — the client has not chosen yet.
        $response->assertDontSee('selected>Saffron Table Catering', false);
        $response->assertDontSee('selected>Jordan Lee Photography', false);
    }

    /** Choosing a service must not remove the way to change it. */
    public function test_the_service_picker_stays_on_screen_after_a_service_is_chosen(): void
    {
        $service = $this->service();
        $this->pro('Saffron Table Catering', $service);

        $this->actingAs($this->client())
            ->get(route('client.direct-offers.create', ['service' => $service->id]))
            ->assertSuccessful()
            ->assertSee('What do you need?');
    }

    /** Arriving from a profile is a real choice, and still fixes the form. */
    public function test_a_professional_named_in_the_url_is_preselected_and_hides_the_service_picker(): void
    {
        $service = $this->service();
        $pro     = $this->pro('Saffron Table Catering', $service);

        $response = $this->actingAs($this->client())
            ->get(route('client.direct-offers.create', ['pro' => $pro->id]));

        $response->assertSuccessful();
        $response->assertSee('selected>Saffron Table Catering', false);
        $response->assertDontSee('What do you need?');
    }

    /** R38 — a link is not a bypass. */
    public function test_an_out_of_state_professional_in_the_url_is_not_preselected(): void
    {
        $service = $this->service();
        $pro     = $this->pro('Philly Catering', $service, 'PA');

        $response = $this->actingAs($this->client('MD'))
            ->get(route('client.direct-offers.create', ['pro' => $pro->id]));

        $response->assertSuccessful();
        $response->assertDontSee('selected>Philly Catering', false);
    }

    public function test_no_matching_professional_shows_an_empty_state_not_an_empty_dropdown(): void
    {
        $service = $this->service('LED Wall & Video Display');   // nobody offers it

        $response = $this->actingAs($this->client())
            ->get(route('client.direct-offers.create', ['service' => $service->id]));

        $response->assertSuccessful();
        $response->assertSee('No professional in your state offers this yet');
        $response->assertSee('Post it to the board instead');
        // The control itself is gone, not merely empty.
        $response->assertDontSee('name="professional_id"', false);
    }

    public function test_before_a_service_is_chosen_the_page_says_so_rather_than_listing_everyone(): void
    {
        $service = $this->service();
        $this->pro('Saffron Table Catering', $service);

        $response = $this->actingAs($this->client())
            ->get(route('client.direct-offers.create'));

        $response->assertSuccessful();
        $response->assertSee('Choose a service first');
        // "We only show professionals who offer this" — so none, until they say.
        $response->assertDontSee('name="professional_id"', false);
    }

    public function test_sending_without_choosing_a_professional_is_rejected(): void
    {
        $response = $this->actingAs($this->client())->post(route('client.direct-offers.store'), [
            'organization_type' => 'individual',
            'event_name'        => 'Rooftop reception',
        ]);

        $response->assertSessionHasErrors('professional_id');
    }

    /** The mangled aria-label is gone. */
    public function test_the_picker_has_a_real_accessible_name(): void
    {
        $service = $this->service();
        $this->pro('Saffron Table Catering', $service);

        $response = $this->actingAs($this->client())
            ->get(route('client.direct-offers.create', ['service' => $service->id]));

        $response->assertDontSee("aria-label=\"id }}", false);
        $response->assertSee('for="doPro"', false);
        $response->assertSee('id="doPro"', false);
    }
}
