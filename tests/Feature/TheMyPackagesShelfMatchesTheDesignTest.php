<?php

namespace Tests\Feature;

use App\Models\{Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * My Packages, against Khadijah's 28 October screen.
 *
 * Her shelf shows five tiles, one toolbar line, and one row per package that
 * says the same thing three ways: what state it is in, whether a client can
 * see it, and what to do about it. The tests here pin the parts that were
 * wrong or missing when the page was compared against her screen:
 *
 *   • the tiles sat above the right hand rail instead of beside it
 *   • an Archived tile read zero on every account that never archives
 *   • the Filters button went nowhere
 *   • the draft progress bar was in the narrow status column
 *   • "not visible" and "hidden" were the same sentence
 */
class TheMyPackagesShelfMatchesTheDesignTest extends TestCase
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

    private function package(array $attrs = []): Package
    {
        static $n = 0;
        $n++;

        return Package::create(array_merge([
            'user_id'     => $this->pro->id,
            'title'       => "Elegant Wedding Package {$n}",
            'slug'        => "elegant-wedding-package-{$n}",
            'services'    => ['Photography', 'Videography'],
            'description' => 'Complete visual storytelling with premium photography.',
            'price'       => 3250,
            'price_unit'  => 'per event',
            'coverage'    => 'Up to 8 Hours',
            'team'        => 2,
            'status'      => 'active',
            'is_active'   => true,
        ], $attrs));
    }

    /**
     * Just the list of rows.
     *
     * The rail offers "Duplicate a Package" and lists every package on the
     * shelf, filtered or not, so a title found anywhere on the page proves
     * nothing about what the filter did.
     */
    private function rows(array $query = []): string
    {
        $html  = $this->page($query);
        $start = strpos($html, 'class="mp-rows"');

        if ($start === false) {
            return '';
        }

        $end = strpos($html, 'class="mp-pager"', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.packages.index', $query))
            ->assertOk()
            ->getContent();
    }

    // ---------------------------------------------------------------- tiles

    /** Her five tiles, with the words she wrote under each one. */
    public function test_the_tiles_carry_her_labels(): void
    {
        $this->package();

        $html = $this->page();

        foreach ([
            'All Packages', 'Everything you have created',
            'Published', 'Visible in Package Search',
            'Drafts', 'Still being completed',
            'Ready to Publish', 'Finished and ready to go live',
            'Unpublished', 'Completed but hidden',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The tile copy \"{$copy}\" is missing.");
        }
    }

    /**
     * No Archived tile on an account that has never archived anything.
     *
     * Her screen shows five tiles. A sixth reading zero for a state most
     * professionals never use crowds out the four that mean something.
     */
    public function test_the_archived_tile_appears_only_when_something_is_archived(): void
    {
        $this->package();

        $this->assertStringNotContainsString('Kept for your records', $this->page());

        $this->package(['status' => 'archived', 'is_active' => false]);

        $this->assertStringContainsString('Kept for your records', $this->page());
    }

    /** The tiles sit in the left column, beside the rail, not above it. */
    public function test_the_tiles_sit_inside_the_column_the_rail_runs_beside(): void
    {
        $this->package();

        $html = $this->page();

        $this->assertLessThan(
            strpos($html, 'class="mp-stats"'),
            strpos($html, 'class="mp-layout"'),
            'The two column split has to open before the tiles, or the rail starts below them.',
        );
    }

    // -------------------------------------------------------------- the row

    /** A row names its state, whether a client can see it, and when. */
    public function test_a_row_says_what_state_it_is_in(): void
    {
        $this->package();

        $html = $this->page();

        $this->assertStringContainsString('Visible in Package Search', $html);
        $this->assertStringContainsString('Published on', $html);
        $this->assertStringContainsString('Starting at', $html);
    }

    /**
     * "Hidden" and "not visible" are not the same sentence.
     *
     * A package that was live and has been taken down is hidden. One that has
     * never been live was never visible to hide.
     */
    public function test_an_unpublished_package_reads_as_hidden_not_as_never_seen(): void
    {
        $this->package(['status' => 'paused', 'is_active' => false]);

        $html = $this->page();

        $this->assertStringContainsString('Hidden from Clients', $html);
        $this->assertStringNotContainsString('Not visible to Clients', $html);
    }

    /** A draft says which step is stopping it and what that step needs. */
    public function test_a_draft_names_the_step_that_is_stopping_it(): void
    {
        $this->package(['price' => 0, 'status' => 'draft', 'is_active' => false]);

        $html = $this->page();

        $this->assertStringContainsString('Draft', $html);
        // Blade escapes the ampersand, so the step reads "Pricing &amp; Discount".
        $this->assertStringContainsString('Pricing &amp; Discount', $html);
        $this->assertStringContainsString('needs a price', $html);
        $this->assertStringContainsString('Pricing not set', $html);
        $this->assertStringContainsString('75% complete', $html);
    }

    /** The progress bar belongs in the wide column, under the description. */
    public function test_the_progress_bar_sits_under_the_description(): void
    {
        $this->package(['price' => 0, 'status' => 'draft', 'is_active' => false]);

        $html = $this->page();

        $this->assertLessThan(
            strpos($html, 'class="mp-chips"'),
            strpos($html, 'class="mp-prog"'),
            'The bar goes between the description and the chips, as her draft row has it.',
        );
    }

    /** Her third chip: how many people deliver the package. */
    public function test_the_row_says_how_many_professionals_deliver_it(): void
    {
        $this->package(['team' => 2]);

        $this->assertStringContainsString('2 Professionals', $this->page());

        Package::query()->update(['team' => 1]);

        $this->assertStringContainsString('1 Professional', $this->page());
    }

    /** Nothing on the shelf is drawn with an emoji. */
    public function test_the_shelf_uses_drawn_icons_not_the_operating_system_s(): void
    {
        $this->package();

        $html = $this->page();

        // Each of these was on the page before, and each is a different
        // shape, weight and colour on every machine that renders it.
        foreach (['📦', '🌐', '📄', '🚀', '🚫', '🕐', '👥', '📍', '⚡', '🎧'] as $emoji) {
            $this->assertStringNotContainsString($emoji, $html, "An emoji ({$emoji}) is still on the shelf.");
        }
    }

    // ------------------------------------------------------------- toolbar

    /** The Filters button opens filters that actually filter. */
    public function test_the_filters_button_opens_real_filters(): void
    {
        $this->package(['title' => 'Cheap Package', 'slug' => 'cheap-pkg', 'price' => 400]);
        $this->package(['title' => 'Premium Package', 'slug' => 'premium-pkg', 'price' => 5000]);

        $html = $this->page();
        $this->assertStringContainsString('Price from', $html);

        $dear = $this->rows(['min' => 1000]);
        $this->assertStringContainsString('Premium Package', $dear);
        $this->assertStringNotContainsString('Cheap Package', $dear);

        $cheap = $this->rows(['max' => 1000]);
        $this->assertStringContainsString('Cheap Package', $cheap);
        $this->assertStringNotContainsString('Premium Package', $cheap);

        // And it says it is narrowed, rather than looking like the whole shelf.
        $this->assertStringContainsString('Filters (1)', $this->page(['min' => 1000]));
    }

    /** A package with no price is not swept into a price range. */
    public function test_a_package_with_no_price_is_not_counted_in_a_range(): void
    {
        $this->package(['title' => 'Unpriced Package', 'slug' => 'unpriced-pkg',
                        'price' => 0, 'status' => 'draft', 'is_active' => false]);

        $this->assertStringNotContainsString('Unpriced Package', $this->rows(['max' => 1000]));
    }

    /** The occasion filter is offered only when there are occasions to pick. */
    public function test_the_occasion_filter_appears_only_when_it_would_do_something(): void
    {
        $this->package();

        $this->assertStringNotContainsString('All occasions', $this->page());

        $this->package(['title' => 'Wedding Bundle', 'slug' => 'wedding-bundle',
                        'event_types' => ['Weddings']]);

        $html = $this->page();
        $this->assertStringContainsString('All occasions', $html);

        $this->assertStringContainsString('Wedding Bundle', $this->rows(['occasion' => 'Weddings']));
    }

    /** Her tabs use the short name of each state. */
    public function test_the_tabs_use_the_short_state_names(): void
    {
        $this->package();
        $this->package(['price' => 0, 'status' => 'draft', 'is_active' => false, 'slug' => 'd1']);

        $html = $this->page();

        $this->assertStringContainsString('All (2)', $html);
        $this->assertStringContainsString('Published (1)', $html);
        $this->assertStringContainsString('Draft (1)', $html);
    }

    /** The footer counts the shelf in packages. */
    public function test_the_footer_counts_packages(): void
    {
        $this->package();

        $this->assertStringContainsString('of 1 package', $this->page());
    }
}
