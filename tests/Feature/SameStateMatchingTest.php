<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Package;
use App\Models\User;
use App\Support\StateMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R38, locked 2026-07-27 and ratified 2026-08-07 — a client and a
 * professional only match inside the SAME state. Not merely the same
 * 7-jurisdiction service area: no interstate bidding or booking at all.
 *
 * The distinction that matters, and the reason this is a separate class from
 * ServiceArea: both a Maryland client and a Delaware professional pass the
 * service-area gate, because both states are in the launch area. R38 is the
 * second question, about the PAIR, and they fail it.
 *
 * Ratified alongside it: enforcement is server-side authoritative and
 * re-checked at the point of transacting; search HIDES the ineligible; and
 * influencers are carved out (R26).
 */
class SameStateMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role, string $state, string $city = 'Baltimore'): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo(['dashboard.view', 'bookings.view_any', 'bookings.update', 'events.create']);
        $user->getOrCreateProfile()->update([
            'country'             => 'US',
            'state'               => $state,
            'city'                => $city,
            'service_area_status' => 'supported',
        ]);

        return $user->fresh();
    }

    private function gigFrom(User $client): Event
    {
        return Event::create([
            'title'        => 'Anniversary Dinner',
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'is_published' => true,
            'status'       => 'published',
            'starts_at'    => now()->addMonth(),
        ]);
    }

    public function test_both_sides_can_be_in_the_service_area_and_still_not_match(): void
    {
        // The whole rule in one assertion. Maryland and Delaware are both
        // launch states, so ServiceArea passes each of them — and R38 still
        // says no, because they are not the same state as each other.
        $client = $this->user('client', 'MD');
        $pro    = $this->user('professional', 'DE', 'Dover');

        $this->assertTrue(\App\Support\ServiceArea::allows($client));
        $this->assertTrue(\App\Support\ServiceArea::allows($pro));
        $this->assertFalse(StateMatching::allows($client, $pro));
    }

    public function test_a_gig_takes_the_state_of_the_client_who_raised_it(): void
    {
        $gig = $this->gigFrom($this->user('client', 'PA', 'Philadelphia'));

        $this->assertSame('PA', $gig->fresh()->state);
    }

    public function test_a_package_takes_the_state_of_the_professional_who_owns_it(): void
    {
        $pro = $this->user('professional', 'VA', 'Richmond');

        $package = Package::create([
            'user_id' => $pro->id,
            'title'   => 'Full Day Coverage',
            'slug'    => 'full-day-coverage',
            'price'   => 1200,
            'status'  => 'active',
            'is_active' => true,
        ]);

        $this->assertSame('VA', $package->fresh()->state);
    }

    public function test_the_bidding_board_hides_a_gig_from_another_state(): void
    {
        $mine     = $this->gigFrom($this->user('client', 'MD'));
        $elsewhere = $this->gigFrom($this->user('client', 'DE', 'Dover'));
        $pro      = $this->user('professional', 'MD');

        $page = $this->actingAs($pro)->get(route('professional.bidding-board.index'));

        $page->assertSuccessful();
        $page->assertSee($mine->title);
        $ids = collect($page->viewData('gigs'))->pluck('event_id');
        $this->assertContains($mine->id, $ids->all());
        $this->assertNotContains($elsewhere->id, $ids->all());
    }

    public function test_a_professional_cannot_bid_on_an_out_of_state_gig_by_url(): void
    {
        // Search hiding it is a courtesy; this is the rule. The ratification
        // is explicit that enforcement is server-side authoritative.
        $gig = $this->gigFrom($this->user('client', 'DE', 'Dover'));
        $pro = $this->user('professional', 'MD');

        $this->actingAs($pro)
            ->get(route('professional.bid.step', ['event' => $gig->id]))
            ->assertForbidden();
    }

    public function test_a_professional_can_still_bid_in_their_own_state(): void
    {
        $gig = $this->gigFrom($this->user('client', 'MD'));
        $pro = $this->user('professional', 'MD');

        $this->actingAs($pro)
            ->get(route('professional.bid.step', ['event' => $gig->id]))
            ->assertSuccessful();
    }

    public function test_a_client_cannot_send_a_direct_offer_across_a_state_line(): void
    {
        // Direct Offer has no board in front of it, so this check is the only
        // thing standing between the two accounts.
        $client = $this->user('client', 'MD');
        $pro    = $this->user('professional', 'DE', 'Dover');

        $this->actingAs($client)
            ->post(route('client.direct-offers.store'), [
                'professional_id' => $pro->id,
                'event_name'      => 'Garden Party',
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('events', ['title' => 'Garden Party']);
    }

    public function test_browse_only_lists_professionals_in_the_clients_state(): void
    {
        $near = $this->user('professional', 'MD');
        $far  = $this->user('professional', 'DE', 'Dover');
        $client = $this->user('client', 'MD');

        $page = $this->actingAs($client)->get(route('public.browse'));

        $page->assertSuccessful();
        $page->assertSee($near->name);
        $page->assertDontSee($far->name);
    }

    /**
     * The count on a tile has to be the count the click produces.
     *
     * The "Trending" strip above the results counted every professional in the
     * category platform-wide, and then filtered the page by R38. A Maryland
     * client read "3 professionals", clicked, and met one. Promising three and
     * showing one is the same fault the homepage's city section shipped with,
     * which is why it is worth a test of its own rather than a quiet edit.
     */
    public function test_a_trending_tile_counts_only_professionals_the_viewer_can_hire(): void
    {
        $category = \App\Models\Category::create([
            'name' => 'DJs & Sound Services', 'slug' => 'djs-sound',
            'kind' => \App\Models\Category::SERVICE_CATEGORY,
            'is_active' => true, 'thumbnail' => 'categories/djs.jpg',
        ]);

        $category->professionals()->attach([
            $this->user('professional', 'MD')->id,
            $this->user('professional', 'DE', 'Dover')->id,
            $this->user('professional', 'VA', 'Arlington')->id,
        ]);

        $client = $this->user('client', 'MD');

        $tile = collect($this->actingAs($client)->get(route('public.browse'))
            ->assertSuccessful()->viewData('trending'))->firstWhere('slug', 'djs-sound');

        $this->assertNotNull($tile, 'the category has a professional this client can hire');
        $this->assertSame(1, $tile->pros_count, 'three exist; one is reachable');
    }

    /** And a category with nobody reachable is not offered at all. */
    public function test_a_trending_tile_with_nobody_reachable_is_withheld(): void
    {
        $category = \App\Models\Category::create([
            'name' => 'Ice Sculpture', 'slug' => 'ice-sculpture',
            'kind' => \App\Models\Category::SERVICE_CATEGORY,
            'is_active' => true, 'thumbnail' => 'categories/ice.jpg',
        ]);

        $category->professionals()->attach($this->user('professional', 'DE', 'Dover')->id);

        $trending = collect($this->actingAs($this->user('client', 'MD'))
            ->get(route('public.browse'))->assertSuccessful()->viewData('trending'));

        $this->assertNull($trending->firstWhere('slug', 'ice-sculpture'));
    }

    /* ── The packages page: the numbers around a scoped list ── */

    private function package(User $pro, string $service = 'Photography', string $occasion = 'Wedding'): \App\Models\Package
    {
        return \App\Models\Package::create([
            'user_id' => $pro->id, 'title' => 'Package ' . uniqid(), 'slug' => 'pkg-' . uniqid(),
            'type' => 'solo', 'price' => 1000, 'services' => [$service],
            'event_types' => [$occasion], 'is_active' => true, 'status' => 'active',
        ]);
    }

    /**
     * The list on /packages was scoped to the viewer's state; the numbers
     * printed AROUND it were not. Every one of them is a promise about what
     * the click returns, so counted platform-wide they overstated it.
     */
    public function test_the_service_filter_counts_what_the_filter_will_return(): void
    {
        $client = $this->user('client', 'MD');
        $this->package($this->user('professional', 'MD'));
        $this->package($this->user('professional', 'DE', 'Dover'));
        $this->package($this->user('professional', 'VA', 'Arlington'));

        $page = $this->actingAs($client)->get(route('public.packages'))->assertSuccessful();

        $this->assertSame(1, $page->viewData('serviceCounts')['Photography']);
        $this->assertCount(1, $page->viewData('packages'), 'the count and the list are one number');
    }

    /**
     * "Where Packages Are Available" is a claim about availability, so naming
     * a city whose packages this client cannot book answers it wrongly.
     */
    public function test_the_availability_panel_names_only_cities_the_client_can_book_in(): void
    {
        $client = $this->user('client', 'MD');
        $this->package($this->user('professional', 'MD', 'Baltimore'));
        $this->package($this->user('professional', 'DE', 'Dover'));

        $availability = $this->actingAs($client)->get(route('public.packages'))
            ->assertSuccessful()->viewData('availability');

        $this->assertSame(['Baltimore'], array_keys($availability->all()));
        $this->assertSame(1, $availability->sum(), 'the cities add up to the packages on the page');
    }

    /** "Keep browsing" has to lead somewhere bookable. */
    public function test_related_packages_are_not_suggested_across_a_state_line(): void
    {
        $far = $this->user('professional', 'DE', 'Dover');
        $this->package($far);
        $shown = $this->package($this->user('professional', 'MD'));

        $more = $this->actingAs($this->user('client', 'MD'))
            ->get(route('public.package', $shown->slug))->assertSuccessful()->viewData('more');

        $this->assertCount(0, $more, 'a package they cannot book is a dead end, not a recommendation');
    }

    /** And a signed-out visitor still sees the whole catalogue. */
    public function test_a_signed_out_visitor_is_not_narrowed_to_a_state(): void
    {
        $this->package($this->user('professional', 'MD'));
        $this->package($this->user('professional', 'DE', 'Dover'));

        $page = $this->get(route('public.packages'))->assertSuccessful();

        $this->assertSame(2, $page->viewData('serviceCounts')['Photography']);
    }

    public function test_an_influencer_is_carved_out(): void
    {
        // R26 exempts influencers from geo-restriction entirely, and they are
        // never a party to a booking — so there is no pair for R38 to judge.
        $influencer = $this->user('influencer', 'CA');
        $pro        = $this->user('professional', 'MD');

        $this->assertFalse(StateMatching::appliesTo($influencer));
        $this->assertTrue(StateMatching::allows($influencer, $pro));
    }

    public function test_an_unknown_state_matches_nobody(): void
    {
        // "Same state?" with a blank on one side is not a yes. Letting NULL
        // through would make the rule opt-in for every row that predates it.
        $this->assertFalse(StateMatching::matches(null, 'MD'));
        $this->assertFalse(StateMatching::matches('MD', null));
        $this->assertFalse(StateMatching::matches(null, null));
    }

    public function test_the_comparison_ignores_case(): void
    {
        // The column is written uppercase, but profiles have been saved both
        // ways by older forms and seeders — the same trap that stranded
        // "Food services" during the taxonomy remap.
        $this->assertTrue(StateMatching::matches('md', 'MD'));
    }
}
