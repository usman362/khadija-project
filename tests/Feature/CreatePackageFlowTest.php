<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\PackageProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Create a Package — the six-step flow.
 *
 * The mockup's own annotations ask for two things to be settled before this
 * ships: what the readiness percentage is actually counting, and whether "Save
 * as Draft" is one feature or four. Both are pinned here, along with the fields
 * the flow gained.
 */
class CreatePackageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(): User
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        UserProfile::updateOrCreate(['user_id' => $pro->id], [
            'city' => 'Baltimore', 'state' => 'MD', 'country' => 'US',
        ]);

        return $pro->fresh();
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'title'       => 'Elegant Wedding Photo & Video',
            'purpose'     => 'Complete wedding photography and videography',
            'description' => 'Complete visual storytelling on one timeline.',
            'services'    => ['Photography', 'Videography'],
            'price'       => 3250,
            'price_unit'  => 'from',
            'coverage'    => 'Up to 10 Hours',
            'guest_min'   => 50,
            'guest_max'   => 150,
            'is_active'   => 1,
        ], $over);
    }

    /** Khadijah's 28 October screen: six steps, services chosen first. */
    public function test_the_form_offers_the_six_steps(): void
    {
        $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()
            ->assertSee('Select Services')
            ->assertSee('Package Details')
            ->assertSee('Pricing &amp; Discount', false)
            ->assertSee('Options &amp; Add-ons', false)
            ->assertSee('Availability')
            ->assertSee('Review &amp; Publish', false)
            // Services first is the point of the reorder, not a detail of it.
            ->assertSeeInOrder(['Select Services', 'Package Details', 'Review &amp; Publish'], false);
    }

    /**
     * Step 1 picks from the professional's OWN services, not the site-wide
     * catalogue. Khadijah's screen is explicit about it, and a package built
     * from services this person does not offer is a package they cannot
     * deliver.
     */
    public function test_step_one_offers_this_professionals_own_services(): void
    {
        $pro = $this->pro();

        $parent = \App\Models\Category::create([
            'name' => 'Catering & Food Services',
            'slug' => 'catering-food-services-step-one',
            'kind' => \App\Models\Category::SERVICE_CATEGORY,
        ]);
        $mine = \App\Models\Category::create([
            'name' => 'Buffet Catering',
            'slug' => 'buffet-catering-step-one',
            'kind' => \App\Models\Category::SERVICE,
            'parent_id' => $parent->id,
        ]);
        \App\Models\Category::create([
            'name' => 'Somebody Elses Service',
            'slug' => 'somebody-elses-service-step-one',
            'kind' => \App\Models\Category::SERVICE,
            'parent_id' => $parent->id,
        ]);

        $pro->serviceCategories()->attach($mine->id, ['price' => 1200, 'price_unit' => 'flat rate']);

        $page = $this->actingAs($pro->fresh())->get(route('professional.packages.create'))->assertOk();

        $page->assertSee('Buffet Catering')
            ->assertSee('Catering &amp; Food Services', false)
            // The price they already set is what the card shows, so the
            // discount on step 3 is measured against something real.
            ->assertSee('$1,200');

        // The grid is theirs. A service they have not listed is not in it.
        $this->assertStringNotContainsString(
            'data-name="somebody elses service"',
            $page->getContent()
        );
    }

    /**
     * The Package Category dropdown is the service tree, and only that.
     *
     * It was every root and everything under it. Under v2 the event types are
     * roots as well, so it offered 106 occasions as though a package could be
     * about "Halloween Party", and nothing filtering on a service would ever
     * find one filed that way.
     */
    public function test_the_category_dropdown_is_services_not_occasions(): void
    {
        $category = \App\Models\Category::create([
            'name' => 'Photography & Videography', 'slug' => 'photo-video-cat-drop',
            'kind' => \App\Models\Category::SERVICE_CATEGORY,
        ]);
        $service = \App\Models\Category::create([
            'name' => 'Wedding Photography', 'slug' => 'wedding-photography-cat-drop',
            'kind' => \App\Models\Category::SERVICE, 'parent_id' => $category->id,
        ]);
        \App\Models\Category::create([
            'name' => 'Drone Coverage', 'slug' => 'drone-coverage-cat-drop',
            'kind' => \App\Models\Category::SERVICE_SPECIALTY, 'parent_id' => $service->id,
        ]);
        \App\Models\Category::create([
            'name' => 'Halloween Party', 'slug' => 'halloween-party-cat-drop',
            'kind' => \App\Models\Category::EVENT_TYPE,
        ]);

        $page = $this->actingAs($this->pro())->get(route('professional.packages.create'))->assertOk();

        // The service and its level 4 are both pickable.
        $page->assertSee('Wedding Photography')
             ->assertSee('Drone Coverage')
             // The category is a heading, not an option.
             ->assertSee('<optgroup label="Photography &amp; Videography">', false);

        $this->assertStringNotContainsString('Halloween Party', $page->getContent());
    }

    /** And an occasion is refused even if one is posted by hand. */
    public function test_an_event_type_cannot_be_saved_as_the_package_category(): void
    {
        $occasion = \App\Models\Category::create([
            'name' => 'Halloween Party', 'slug' => 'halloween-party-post',
            'kind' => \App\Models\Category::EVENT_TYPE,
        ]);

        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['category_id' => $occasion->id]))
            ->assertSessionHasErrors('category_id');
    }

    // ── The fields the flow gained ───────────────────────────────

    public function test_the_purpose_line_saves_and_reaches_the_client(): void
    {
        // A field that is stored and never rendered is a field that goes
        // nowhere, which is the defect this project keeps having.
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), $this->payload())
            ->assertRedirect();

        $package = Package::firstOrFail();
        $this->assertSame('Complete wedding photography and videography', $package->purpose);

        $this->get(route('public.package', $package->slug))
            ->assertOk()
            ->assertSee('Complete wedding photography and videography');
    }

    public function test_the_category_chosen_on_the_form_is_the_one_saved(): void
    {
        // The form was handed $categories and never drew them, so category_id
        // could only ever be set by a seeder — and it picks the stand-in hero
        // image and the "more like this" row on the public page.
        $pro = $this->pro();
        $category = \App\Models\Category::create([
            'name' => 'Wedding Photography', 'slug' => 'wedding-photography-test', 'is_active' => true,
        ]);

        $this->actingAs($pro)->post(route('professional.packages.store'),
            $this->payload(['category_id' => $category->id]))->assertRedirect();

        $this->assertSame($category->id, Package::firstOrFail()->category_id);

        $this->actingAs($pro)->get(route('professional.packages.create'))
            ->assertOk()
            ->assertSee('Wedding Photography');
    }

    public function test_the_name_limit_matches_the_counter_the_form_shows(): void
    {
        // The field counts up to 60, so 61 has to be refused — a counter that
        // is not the rule is decoration.
        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['title' => str_repeat('a', 61)]))
            ->assertSessionHasErrors('title');

        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['title' => str_repeat('a', 60)]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_description_takes_the_five_hundred_the_counter_allows(): void
    {
        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['description' => str_repeat('a', 500)]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['description' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('description');
    }

    // ── The readiness number (the mockup's open question #2) ─────

    public function test_the_readiness_ring_is_the_same_calculation_as_the_shelf(): void
    {
        /*
         * Peter's note on the mockup says the completion maths needs defining
         * and that "32%" does not follow from one of eight items. It is now one
         * row per fillable step, and it is PackageProgress — the same function
         * the My Packages progress bar uses — so the two screens cannot quote
         * different numbers for one package.
         */
        $pro = $this->pro();
        $half = Package::create([
            'user_id' => $pro->id, 'title' => 'Half done', 'slug' => 'half-done', 'type' => 'solo',
            'description' => 'Something.', 'services' => ['Photography'], 'price' => 0,
            'status' => 'draft', 'is_active' => false,
        ]);

        $this->assertSame(25, PackageProgress::percent($half));

        $this->actingAs($pro)->get(route('professional.packages.edit', $half))
            ->assertOk()
            ->assertSee('>25%<', false);
    }

    public function test_the_ring_does_not_count_publishing_as_something_to_fill_in(): void
    {
        // If it did, a finished draft could never reach 100% and so could never
        // be "Ready to Publish" on the shelf.
        $pro = $this->pro();
        $done = Package::create([
            'user_id' => $pro->id, 'title' => 'All done', 'slug' => 'all-done', 'type' => 'solo',
            'description' => 'Something.', 'services' => ['Photography', 'Videography'],
            'price' => 2000, 'coverage' => 'Up to 8 Hours', 'status' => 'draft', 'is_active' => false,
        ]);

        $this->assertSame(100, PackageProgress::percent($done));
        $this->assertSame('ready', PackageProgress::shelfState($done));

        $this->actingAs($pro)->get(route('professional.packages.edit', $done))
            ->assertOk()->assertSee('>100%<', false);
    }

    // ── Save as Draft is ONE feature (the mockup's open question #4) ──

    public function test_there_is_exactly_one_save_as_draft_control(): void
    {
        /*
         * The mockup shows "Save as Draft" in four places. The card at the foot
         * of the page presses the header's button rather than being a second
         * submit that might behave differently.
         */
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        $this->assertSame(1, substr_count($body, 'name="is_active" value="0"'),
            'more than one control submits a draft');
    }

    public function test_the_page_never_claims_work_is_being_auto_saved(): void
    {
        // There is no autosave. "Auto-saved 2 min ago" would be a claim about
        // whether the professional's work is safe.
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        foreach (['auto-saved', 'autosave', 'automatically saved'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $body);
        }
    }

    public function test_the_page_states_no_estimated_times(): void
    {
        // The mockup labels each step "Est. time: 15–20 min". Nobody has
        // measured that, and it is a promise about somebody's evening.
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        foreach (['Est. time', 'estimated time', '15-20 min', '15–20 min'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $body);
        }
    }
}
