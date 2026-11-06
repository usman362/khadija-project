<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Create a Package, step 1, against Khadijah's 28 October screen.
 *
 * Her step 1 is a grid of this professional's own services with the picked
 * ones gathered in a panel beside it. The tests here pin what was different:
 *
 *   • the page called itself "Create a Package for Clients"
 *   • the picked services sat under the grid, not beside it
 *   • the third filter offered "All services" instead of "Active Services Only"
 *   • three cards at the foot repeated the header, and one of them clicked a
 *     button that had been removed
 */
class TheCreateAPackageStepOneMatchesTheDesignTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00'));

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->pro = User::findOrFail($this->pro->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** One of this professional's own services, priced or not, on or off. */
    private function service(string $name, ?float $price = null, bool $active = true): Category
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'dj-and-music'],
            ['name' => 'DJ & Music', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        $service = Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->attach($service->id, [
            'price' => $price, 'price_unit' => 'per event', 'is_active' => $active,
        ]);

        return $service;
    }

    private function page(): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.packages.create'))
            ->assertOk()
            ->getContent();
    }

    // ---------------------------------------------------------------- shell

    /** Her title and her sentence, not a longer one. */
    public function test_the_page_calls_itself_what_her_screen_calls_it(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Create a Package', $html);
        $this->assertStringNotContainsString('Create a Package for Clients', $html);
        $this->assertStringContainsString(
            'Bundle your services into a package to attract more clients.',
            $html,
        );
    }

    /** Her six steps, each with the line she wrote under it. */
    public function test_the_stepper_names_all_six_steps(): void
    {
        $html = $this->page();

        foreach ([
            'Select Services', 'Choose from your services',
            'Package Details', 'Name, description, photos',
            'Pricing &amp; Discount', 'Set package price',
            'Options &amp; Add-ons', 'Enhance your package',
            'Availability', 'Set date and service rules',
            'Review &amp; Publish', 'Confirm and publish',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The stepper is missing \"{$copy}\".");
        }
    }

    /** The help button, where her screen has it. */
    public function test_the_header_offers_the_help_guide(): void
    {
        $this->assertStringContainsString('View Help Guide', $this->page());
    }

    /**
     * The three cards that used to close the page are gone.
     *
     * Every one of them was a second doorway to something already on the
     * page, and one clicked a button that no longer exists.
     */
    public function test_the_page_ends_at_the_action_bar(): void
    {
        $html = $this->page();

        $this->assertStringNotContainsString('Save as Draft Anytime', $html);
        $this->assertStringNotContainsString('Need Assistance?', $html);
        $this->assertStringNotContainsString('pcDraftBtn', $html);

        // And the bar itself carries all three of her buttons.
        $this->assertStringContainsString('Save as Draft', $html);
        $this->assertStringContainsString('Next Step', $html);
        $this->assertStringContainsString('Cancel', $html);
    }

    // ------------------------------------------------------------ the grid

    /** The grid is this professional's own services, with their own prices. */
    public function test_the_grid_shows_this_professionals_services(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertStringContainsString('Wedding DJ', $html);
        $this->assertStringContainsString('DJ &amp; Music', $html);
        $this->assertStringContainsString('$1,200', $html);
        $this->assertStringContainsString('per event', $html);
    }

    /** A service with no price says so rather than reading as free. */
    public function test_a_service_with_no_price_says_so(): void
    {
        $this->service('Karaoke DJ');

        $html = $this->page();

        $this->assertStringContainsString('No price set', $html);
        $this->assertStringNotContainsString('$0 <i>per event', $html);
    }

    /** Her third dropdown, and it names a switch that really exists. */
    public function test_the_third_filter_is_active_services_only(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertStringContainsString('Active Services Only', $html);
        $this->assertStringNotContainsString('<option value="">All services</option>', $html);
    }

    /** A service switched off on My Services is marked, not hidden away. */
    public function test_an_inactive_service_is_labelled_inactive(): void
    {
        $this->service('Wedding DJ', 1200, true);
        $this->service('Retired Karaoke', 600, false);

        $html = $this->page();

        $this->assertStringContainsString('Retired Karaoke', $html);
        $this->assertStringContainsString('>Inactive<', $html);
        $this->assertStringContainsString('data-live="0"', $html);
        $this->assertStringContainsString('data-live="1"', $html);
    }

    /**
     * A filtered-out card is hidden by the attribute, and the stylesheet has
     * to let it be.
     *
     * `display` on the element itself beats the hidden attribute. That has now
     * cost this project three separate bugs, so the rule that undoes it is
     * pinned here rather than left to be rediscovered.
     */
    public function test_the_stylesheet_does_not_override_the_hidden_attribute(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertStringContainsString('.pc-svccard:not([hidden]) { display: block; }', $html);
        $this->assertStringNotContainsString(
            '.pc-svccard { position: relative; border: 2px solid var(--border-color); border-radius: 13px;
        cursor: pointer; display: block;',
            $html,
        );
    }

    // ----------------------------------------------------------- the panel

    /** Her panel sits in the rail, beside the grid, not under it. */
    public function test_the_picked_services_sit_in_the_rail(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertLessThan(
            strpos($html, 'id="pcPicked"'),
            strpos($html, 'class="pc-side"'),
            'Selected Services belongs inside the rail, which has to open before it.',
        );
    }

    /** The panel carries her words, down to the total under it. */
    public function test_the_panel_carries_her_copy(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        foreach ([
            'Selected Services', 'Clear All',
            'You can change the order of services by dragging.',
            'These services will be bundled together in this package.',
            'Estimated Individual Total',
            'Total if each service is booked separately at base price.',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The panel is missing \"{$copy}\".");
        }
    }

    /** Nothing picked yet says so, rather than showing a total of nothing. */
    public function test_an_empty_panel_says_a_package_needs_two(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertStringContainsString('Nothing selected yet. A package needs at least two.', $html);
        $this->assertStringContainsString('Minimum 2 required to appear in Package Search', $html);
    }

    /** Each card hands the panel what it needs to draw its own row. */
    public function test_each_card_carries_what_the_panel_prints(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        $this->assertStringContainsString('data-price="1200"', $html);
        $this->assertStringContainsString('data-unit="per event"', $html);
        $this->assertStringContainsString('data-cat="DJ &amp; Music"', $html);
    }

    /** Only the picked panel shows on step one, as her screen has it. */
    public function test_step_one_shows_only_the_picked_panel_in_the_rail(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page();

        // Everything else in the rail is marked to stay out of the way until
        // there is something for it to describe.
        $this->assertSame(
            5,
            substr_count($html, 'pc-scard pc-afterone') + substr_count($html, 'pc-scard pc-help pc-afterone'),
            'Every rail card except Selected Services waits for step two.',
        );
        $this->assertStringContainsString('.pc-afterone[hidden] { display: none; }', $html);
    }
}
