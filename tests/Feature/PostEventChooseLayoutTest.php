<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "How do you want to request?" — rebuilt to Khadijah's design, 27 Aug.
 *
 * It was four cards across with a fifth alone on its own line and the right
 * half of the page empty. Now six cards in two rows of three, with a rail
 * beside them showing what other clients are actually posting.
 *
 * The rail is real published requests, not decoration — and what it leaves
 * out is the point: no client names, and nothing from another state.
 */
class PostEventChooseLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(string $state = 'MD', string $name = 'Dana Whitfield'): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function posting(User $client, string $state, string $type): Event
    {
        return Event::create([
            'client_id' => $client->id, 'created_by' => $client->id,
            'title' => $type, 'event_type' => $type,
            'is_published' => true, 'published_at' => now()->subMinutes(5),
            'status' => 'published', 'state' => $state, 'starts_at' => now()->addMonth(),
        ]);
    }

    /** Six routes, so the grid is two full rows rather than four and a stray. */
    public function test_all_six_routes_are_offered(): void
    {
        $response = $this->actingAs($this->client())->get(route('client.post-event.choose'));

        $response->assertSuccessful();

        foreach ([
            'Shop Packages',
            'Bidding Request (BR)',
            'Direct Request (DR)',
            'Emergency Request (ER)',
            'Virtual &amp; Hybrid Hub',
            'Plan with Toolkit',
        ] as $route) {
            $response->assertSee($route, false);
        }
    }

    public function test_the_rail_shows_real_postings(): void
    {
        $me    = $this->client();
        $other = $this->client('MD', 'Someone Else');

        $this->posting($other, 'MD', 'Wedding Reception');

        $response = $this->actingAs($me)->get(route('client.post-event.choose'));

        $response->assertSuccessful();
        $response->assertSee('New client postings', false);
        $response->assertSee('Wedding Reception');
    }

    /** A client has no reason to know who posted it. */
    public function test_the_rail_never_names_the_client_who_posted(): void
    {
        $me    = $this->client();
        $other = $this->client('MD', 'Zaphod Beeblebrox');

        $this->posting($other, 'MD', 'Corporate Gala');

        $this->actingAs($me)->get(route('client.post-event.choose'))
            ->assertSuccessful()
            ->assertSee('Corporate Gala')
            ->assertDontSee('Zaphod Beeblebrox');
    }

    /** Same-state only — another state's demand is not this client's market. */
    public function test_out_of_state_postings_are_not_shown(): void
    {
        $me = $this->client('MD');
        $pa = $this->client('PA', 'Philly Client');

        $this->posting($pa, 'PA', 'Philadelphia Fundraiser');

        $this->actingAs($me)->get(route('client.post-event.choose'))
            ->assertSuccessful()
            ->assertDontSee('Philadelphia Fundraiser');
    }

    /** Their own request is not news to them. */
    public function test_the_client_does_not_see_their_own_posting(): void
    {
        $me = $this->client();
        $this->posting($me, 'MD', 'My Own Birthday');

        $this->actingAs($me)->get(route('client.post-event.choose'))
            ->assertSuccessful()
            ->assertDontSee('My Own Birthday');
    }

    /** Nothing yet is a sentence, not an empty box. */
    public function test_an_empty_rail_says_so(): void
    {
        $this->actingAs($this->client())->get(route('client.post-event.choose'))
            ->assertSuccessful()
            ->assertSee('Nothing posted in your state yet');
    }

    /**
     * The design says "Updated in real-time". The page is not — it renders
     * once — so it does not say so.
     */
    public function test_it_does_not_claim_to_be_live(): void
    {
        $me    = $this->client();
        $other = $this->client('MD', 'Someone Else');
        $this->posting($other, 'MD', 'Wedding Reception');

        $this->actingAs($me)->get(route('client.post-event.choose'))
            ->assertDontSee('real-time', false)
            ->assertSee('As of now');
    }
}
