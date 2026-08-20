<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * From "Plan Your Bridal Shower" into the request.
 *
 * The Owner's report, 2026-08-20: he picked services on the event-type page,
 * pressed Continue, and the first step of the request asked the same question
 * again — "the marked in red selection was already answered".
 *
 * It was answered. The answer was stored as focus_categories and read by
 * nothing.
 */
class EventTypeToRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $eventType;
    private Category $decor;
    private Category $decorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        config(['taxonomy.version' => 'v2']);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        UserProfile::updateOrCreate(['user_id' => $this->client->id], [
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);

        $this->eventType = $this->make('Bridal Shower', Category::EVENT_TYPE);
        $this->decor = $this->make('Decor, Floral & Balloon Design', Category::SERVICE_CATEGORY);
        $catering = $this->make('Catering & Food Services', Category::SERVICE_CATEGORY);

        $this->decorService = $this->make('Balloon Arches & Columns', Category::SERVICE, ['parent_id' => $this->decor->id]);
        $this->make('Full-Service Catering', Category::SERVICE, ['parent_id' => $catering->id]);
        $this->make('Zebra Handlers', Category::SERVICE, ['parent_id' => $catering->id]);
    }

    private function make(string $name, string $kind, array $over = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-etr',
            'kind' => $kind,
            'taxonomy_version' => 'v2',
            'is_active' => true,
        ], $over));
    }

    private function continueWith(array $categoryIds): void
    {
        $this->actingAs($this->client)
            ->post(route('client.bsr.from-event-type'), [
                'event_type' => 'Bridal Shower',
                'categories' => $categoryIds,
            ])
            ->assertRedirect(route('client.bsr.step', 'service'));
    }

    public function test_the_step_opens_on_the_services_inside_what_was_chosen(): void
    {
        /*
         * Not the same question asked twice — the level below it. The client
         * chose a service CATEGORY on the event page; these are the individual
         * services under it. What the step must not do is open on all 241 as if
         * they had said nothing.
         */
        $this->continueWith([$this->decor->id]);

        $names = $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()->viewData('categories')->pluck('name')->all();

        $this->assertSame(['Balloon Arches & Columns'], $names);
        $this->assertNotContains('Zebra Handlers', $names);
    }

    public function test_the_step_says_back_what_the_client_chose(): void
    {
        $this->continueWith([$this->decor->id]);

        $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()
            ->assertSee('You chose Decor, Floral &amp; Balloon Design', false)
            ->assertSee('see every service');
    }

    public function test_everything_is_still_one_click_away(): void
    {
        // Narrowing must not be a cage: a bridal shower can want something the
        // masterlist never filed under decor.
        $this->continueWith([$this->decor->id]);

        $names = $this->actingAs($this->client)
            ->get(route('client.bsr.step', ['step' => 'service', 'all' => 1]))
            ->assertOk()->viewData('categories')->pluck('name')->all();

        $this->assertContains('Zebra Handlers', $names);
        $this->assertContains('Balloon Arches & Columns', $names);
    }

    public function test_an_area_with_nothing_bookable_under_it_shows_everything(): void
    {
        // An empty step is worse than a long one.
        $empty = $this->make('Nothing Here Yet', Category::SERVICE_CATEGORY);

        $this->continueWith([$empty->id]);

        $names = $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()->viewData('categories')->pluck('name')->all();

        $this->assertContains('Zebra Handlers', $names);
    }

    public function test_arriving_without_choosing_anything_leaves_the_step_blank(): void
    {
        $data = $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()->viewData('data');

        $this->assertEmpty($data['services'] ?? []);
    }

    // ── The nav link ─────────────────────────────────────────────

    public function test_post_your_event_opens_post_an_event_for_a_signed_in_client(): void
    {
        /*
         * It pointed at /register. Register bounces anyone already signed in to
         * their dashboard, so the link named "Post Your Event" landed on the
         * dashboard — which is exactly what the Owner reported.
         */
        $html = $this->actingAs($this->client)->get('/')->assertOk()->getContent();

        preg_match('/<a href="([^"]*)"[^>]*>(?:(?!<\/a>).)*Post Your Event/s', $html, $m);

        $this->assertNotEmpty($m, 'the Post Your Event link is missing');
        $this->assertStringContainsString('/client/post-event', $m[1]);

        // And following it must not bounce.
        $this->actingAs($this->client)->get($m[1])->assertOk();
    }

    public function test_a_guest_is_still_asked_to_make_an_account_first(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<a href="([^"]*)"[^>]*>(?:(?!<\/a>).)*Post Your Event/s', $html, $m);

        $this->assertNotEmpty($m);
        $this->assertStringContainsString('/register', $m[1]);
    }
}
