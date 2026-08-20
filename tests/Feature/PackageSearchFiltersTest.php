<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Package;
use App\Models\SavedSearch;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peter's Package Service Search mockup puts six filters on the rail. Five of
 * them were drawn and never built; this is the guard that they keep doing what
 * the label says.
 *
 * The theme running through these is the defect this page has had before: a
 * control that looks like it filters and does not, or a number that counts
 * something other than the list underneath it.
 */
class PackageSearchFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** A signed-in client, in the same state as the professionals below (R38). */
    private function client(string $state = 'MD'): User
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        UserProfile::updateOrCreate(['user_id' => $client->id], [
            'city' => 'Baltimore', 'state' => $state, 'country' => 'US',
        ]);

        return $client->fresh();
    }

    private function pro(array $profile = []): User
    {
        $pro = User::factory()->create();

        UserProfile::updateOrCreate(['user_id' => $pro->id], array_merge([
            'city'    => 'Baltimore',
            'state'   => 'MD',
            'country' => 'US',
        ], $profile));

        return $pro->fresh();
    }

    private function package(User $pro, array $attrs = []): Package
    {
        static $n = 0;
        $n++;

        return Package::create(array_merge([
            'user_id'   => $pro->id,
            'title'     => 'Package ' . $n,
            'slug'      => 'pkg-' . $n,
            'type'      => 'solo',
            'price'     => 3000,
            'services'  => ['Photography'],
            'is_active' => true,
            'status'    => 'active',
        ], $attrs));
    }

    // ── 4. Budget ────────────────────────────────────────────────

    public function test_the_budget_slider_excludes_packages_outside_it(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Cheap one', 'price' => 1500]);
        $this->package($pro, ['title' => 'Dear one', 'price' => 12000]);

        $this->get('/packages?budget_min=1000&budget_max=5000')
            ->assertOk()
            ->assertSee('Cheap one')
            ->assertDontSee('Dear one');
    }

    public function test_the_top_of_the_slider_is_open_ended(): void
    {
        // "$20,000+" has a plus on it, so a package above the ceiling must
        // still appear when the handle is left at the top.
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Very grand', 'price' => 48000]);

        $this->get('/packages')->assertOk()->assertSee('Very grand');
        $this->get('/packages?budget_max=20000')->assertOk()->assertSee('Very grand');
        $this->get('/packages?budget_max=10000')->assertOk()->assertDontSee('Very grand');
    }

    // ── 6. Guest count ───────────────────────────────────────────

    public function test_guest_count_reads_the_number_out_of_the_prose(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Small do', 'guests' => 'Up to 60']);
        $this->package($pro, ['title' => 'Big do', 'guests' => 'Up to 300 seated']);

        $this->get('/packages?guests=200')
            ->assertOk()
            ->assertSee('Big do')
            ->assertDontSee('Small do');
    }

    public function test_a_package_with_no_stated_capacity_is_not_treated_as_zero(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Unstated', 'guests' => null]);

        $this->get('/packages?guests=350')->assertOk()->assertSee('Unstated');
    }

    // ── 3. Location ──────────────────────────────────────────────

    public function test_location_matches_the_city_the_professional_works_from(): void
    {
        $this->package($this->pro(['city' => 'Baltimore', 'state' => 'MD']), ['title' => 'Charm City']);
        $this->package($this->pro(['city' => 'Philadelphia', 'state' => 'PA']), ['title' => 'Philly one']);

        $this->get('/packages?location=Baltimore&scope=city')
            ->assertOk()
            ->assertSee('Charm City')
            ->assertDontSee('Philly one');
    }

    public function test_a_state_name_or_code_widens_to_the_whole_state(): void
    {
        $this->package($this->pro(['city' => 'Baltimore', 'state' => 'MD']), ['title' => 'Charm City']);
        $this->package($this->pro(['city' => 'Rockville', 'state' => 'MD']), ['title' => 'Rockville one']);
        $this->package($this->pro(['city' => 'Philadelphia', 'state' => 'PA']), ['title' => 'Philly one']);

        $both = $this->get('/packages?location=Maryland');
        $both->assertOk()->assertSee('Charm City')->assertSee('Rockville one')->assertDontSee('Philly one');

        $this->get('/packages?location=MD')->assertOk()->assertSee('Rockville one');
    }

    // ── 5. Availability ──────────────────────────────────────────

    public function test_the_date_filter_hides_a_professional_already_committed(): void
    {
        $date = now()->addDays(20)->toDateString();

        $free = $this->pro();
        $busy = $this->pro();
        $this->package($free, ['title' => 'Still free']);
        $this->package($busy, ['title' => 'Already booked']);

        $someoneElse = User::factory()->create();

        $event = Event::create([
            'title'      => 'Someone elses wedding',
            'created_by' => $someoneElse->id,
            'client_id'  => $someoneElse->id,
            'starts_at'  => $date . ' 15:00:00',
            'ends_at'    => $date . ' 23:00:00',
            'status'     => 'published',
        ]);

        Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $event->created_by,
            'supplier_id' => $busy->id,
            'created_by'  => $event->created_by,
            'status'      => 'confirmed',
        ]);

        $this->get('/packages?date=' . $date)
            ->assertOk()
            ->assertSee('Still free')
            ->assertDontSee('Already booked');
    }

    public function test_a_date_in_the_past_is_ignored_rather_than_emptying_the_page(): void
    {
        $this->package($this->pro(), ['title' => 'Perfectly fine']);

        $this->get('/packages?date=' . now()->subYear()->toDateString())
            ->assertOk()
            ->assertSee('Perfectly fine');
    }

    // ── The counting rule (R1/R6): a number must count its own list ──

    public function test_the_rail_counts_honour_the_other_filters(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['price' => 2000, 'services' => ['Photography']]);
        $this->package($pro, ['price' => 40000, 'services' => ['Photography']]);

        // Unfiltered: both photography packages are counted.
        $this->get('/packages')->assertOk()->assertSee('Showing <b>2</b> Package', false);

        // Budget-capped: the count beside "Photography" must drop with the list,
        // or the rail promises two and the filter delivers one.
        $capped = $this->get('/packages?budget_max=5000');
        $capped->assertOk()->assertSee('Showing <b>1</b> Package', false);
        $capped->assertSee('Photography');
        $this->assertStringNotContainsString(
            '>2</span>',
            $this->railFor($capped->getContent()),
            'a service count outran the filtered list',
        );
    }

    /** The service checkbox block only, so an unrelated "2" cannot fail the test. */
    private function railFor(string $html): string
    {
        $start = strpos($html, 'id="pkSvcList"');
        $end = strpos($html, 'id="pkShowMore"');

        return $start !== false && $end !== false ? substr($html, $start, $end - $start) : '';
    }

    // ── Save Search ──────────────────────────────────────────────

    public function test_a_client_can_save_and_re_run_a_search(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->post('/packages/saved-searches', ['services' => ['Photography'], 'budget_max' => 5000])
            ->assertRedirect();

        $saved = SavedSearch::where('user_id', $client->id)->firstOrFail();

        $this->assertSame('packages', $saved->surface);
        $this->assertSame(['Photography'], $saved->params['services']);
        $this->assertSame(5000, $saved->params['budget_max']);
        $this->assertStringContainsString('Photography', $saved->label);
    }

    public function test_a_saved_search_never_stores_the_event_date(): void
    {
        // A saved search is re-run weeks later; a stored date would quietly
        // filter to a day that has been and gone.
        $client = $this->client();

        $this->actingAs($client)->post('/packages/saved-searches', [
            'services' => ['Photography'],
            'date'     => now()->addDays(10)->toDateString(),
        ]);

        $this->assertArrayNotHasKey('date', SavedSearch::firstOrFail()->params);
    }

    public function test_saving_nothing_is_refused(): void
    {
        $client = $this->client();

        $this->actingAs($client)->post('/packages/saved-searches')->assertSessionHas('error');

        $this->assertSame(0, SavedSearch::count());
    }

    public function test_one_client_cannot_delete_anothers_saved_search(): void
    {
        $mine = $this->client();
        $theirs = $this->client();

        $search = SavedSearch::create([
            'user_id' => $mine->id, 'surface' => 'packages',
            'label' => 'Photography', 'params' => ['services' => ['Photography']],
        ]);

        $this->actingAs($theirs)->delete('/packages/saved-searches/' . $search->id)->assertForbidden();

        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }

    // ── The heart ────────────────────────────────────────────────

    public function test_the_heart_actually_saves_the_package(): void
    {
        // It used to only toggle a CSS class, so a client's shortlist was gone
        // on the next page load.
        $client = $this->client();
        $package = $this->package($this->pro());

        $this->actingAs($client)->post('/package/' . $package->id . '/save')->assertRedirect();
        $this->assertTrue($client->fresh()->savedPackages()->where('packages.id', $package->id)->exists());

        $this->actingAs($client)->post('/package/' . $package->id . '/save')->assertRedirect();
        $this->assertFalse($client->fresh()->savedPackages()->where('packages.id', $package->id)->exists());
    }

    public function test_hearted_packages_can_be_listed_again(): void
    {
        // A save nobody can go back to is not a save.
        $client = $this->client();
        $pro = $this->pro();
        $kept = $this->package($pro, ['title' => 'The one I liked']);
        $this->package($pro, ['title' => 'The other one']);

        $this->actingAs($client)->post('/package/' . $kept->id . '/save');

        $this->actingAs($client)->get('/packages?saved=1')
            ->assertOk()
            ->assertSee('The one I liked')
            ->assertDontSee('The other one');
    }

    public function test_the_favourites_flag_does_nothing_for_a_signed_out_visitor(): void
    {
        // Applied blindly it would empty the page with no explanation.
        $this->package($this->pro(), ['title' => 'Still listed']);

        $this->get('/packages?saved=1')->assertOk()->assertSee('Still listed');
    }

    // ── Compare ──────────────────────────────────────────────────

    public function test_compare_shows_a_row_for_every_service_any_of_them_offers(): void
    {
        $pro = $this->pro();
        $a = $this->package($pro, ['title' => 'Photo only', 'services' => ['Photography']]);
        $b = $this->package($pro, ['title' => 'Photo and video', 'services' => ['Photography', 'Videography']]);

        $this->get('/packages/compare?ids=' . $a->id . ',' . $b->id)
            ->assertOk()
            ->assertSee('Photo only')
            ->assertSee('Photo and video')
            // Videography is a row because ONE of them has it — that gap is the
            // reason the screen exists.
            ->assertSee('Videography')
            ->assertSee('Not included');
    }

    public function test_compare_takes_at_most_three(): void
    {
        $pro = $this->pro();
        $ids = collect(range(1, 4))->map(fn ($i) => $this->package($pro, ['title' => "Slot {$i}"])->id);

        $this->get('/packages/compare?ids=' . $ids->implode(','))
            ->assertOk()
            ->assertSee('Slot 3')
            ->assertDontSee('Slot 4');
    }

    public function test_compare_with_nothing_chosen_says_so_rather_than_erroring(): void
    {
        $this->get('/packages/compare')->assertOk()->assertSee('Nothing to compare yet');
    }

    // ── Paging ───────────────────────────────────────────────────

    public function test_the_pager_states_the_range_it_is_showing(): void
    {
        $pro = $this->pro();
        for ($i = 0; $i < 14; $i++) {
            $this->package($pro);
        }

        $this->get('/packages')->assertOk()->assertSee('Showing 1–12 of 14 packages');
        $this->get('/packages?per_page=24')->assertOk()->assertSee('Showing 1–14 of 14 packages');
    }

    public function test_an_invented_per_page_falls_back_rather_than_being_obeyed(): void
    {
        $pro = $this->pro();
        for ($i = 0; $i < 14; $i++) {
            $this->package($pro);
        }

        $this->get('/packages?per_page=500')->assertOk()->assertSee('Showing 1–12 of 14 packages');
    }

    // ── Filters survive the other controls ───────────────────────

    public function test_changing_the_sort_link_keeps_every_other_filter(): void
    {
        $this->package($this->pro(), ['price' => 2000]);

        $html = $this->get('/packages?location=Baltimore&guests=100&budget_max=9000&view=grid')
            ->assertOk()
            ->getContent();

        // The view toggle is the link most likely to drop things — it used to
        // rebuild the query from scratch.
        $this->assertMatchesRegularExpression('/href="[^"]*packages\?[^"]*location=Baltimore[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*packages\?[^"]*guests=100[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*packages\?[^"]*budget_max=9000[^"]*"/', $html);
    }

    // ── The card itself ──────────────────────────────────────────

    public function test_the_card_states_the_service_area_once(): void
    {
        /*
         * The footer carried "Serves MD" directly under a SERVICE AREA cell
         * reading "Baltimore, MD". R38 means those two can never disagree — a
         * package is offered in its professional's own state — so the second
         * one was the same fact printed twice in a card that had no room for it.
         */
        $package = $this->package($this->pro(['city' => 'Baltimore', 'state' => 'MD']));

        $card = $this->cardFor($package->title);

        $this->assertStringContainsString('Baltimore, MD', $card);
        $this->assertStringNotContainsString('Serves', $card);
    }

    public function test_every_link_inside_a_card_carries_a_class(): void
    {
        /*
         * The card is an <article>, and the layout styles
         * `article a:not([class])` for blog prose — so a classless anchor in
         * here renders indigo and underlined, which is what turned every
         * package title blue. Naming the links is the fix; fighting it with
         * specificity would only move the problem.
         */
        $this->package($this->pro(), ['title' => 'Classless check']);

        $card = $this->cardFor('Classless check');

        preg_match_all('/<a\s+href=(?![^>]*class=)[^>]*>/', $card, $m);

        $this->assertSame([], $m[0], 'a link inside a card has no class and will be styled as blog prose');
    }

    /** The markup of one card, so an assertion cannot pass on the rest of the page. */
    private function cardFor(string $title): string
    {
        $html = $this->get('/packages')->assertOk()->getContent();
        $html = preg_replace('/\{\{--.*?--\}\}/s', '', $html);

        $start = strpos($html, '<article class="pk-card">');
        $this->assertNotFalse($start, 'no package card rendered');

        $end = strpos($html, '</article>', $start);

        return substr($html, $start, $end - $start);
    }
}
