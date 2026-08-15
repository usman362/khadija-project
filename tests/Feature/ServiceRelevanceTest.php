<?php

namespace Tests\Feature;

use App\Domain\Taxonomy\ServiceRelevance;
use App\Models\Category;
use App\Models\CategoryRelevance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peter's Category Masterlist, and the gap between importing data and using it.
 *
 * The 139-row Archetype Relevance Matrix was imported on 5 August, complete and
 * correct — and no controller, view or query read a single row of it. What WAS
 * running was config/event-service-map.php: a keyword guess naming 48 event
 * types of which only 22 exist, so on 84 of the 106 live event types it did
 * nothing whatsoever.
 */
class ServiceRelevanceTest extends TestCase
{
    use RefreshDatabase;

    private function archetype(string $name, array $tiers): array
    {
        $ids = [];

        foreach ($tiers as $categoryName => $tier) {
            $cat = Category::create([
                'name' => $categoryName, 'slug' => \Illuminate\Support\Str::slug($categoryName),
                'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
            ]);
            CategoryRelevance::create(['archetype' => $name, 'category_id' => $cat->id, 'tier' => $tier]);
            $ids[$categoryName] = $cat->id;
        }

        return $ids;
    }

    protected function setUp(): void
    {
        parent::setUp();
        ServiceRelevance::forget();
    }

    /* ── The lookup ─────────────────────────────────────────── */

    public function test_an_event_type_resolves_through_its_archetype_to_tiers(): void
    {
        $ids = $this->archetype('Wedding & Related Ceremonies', [
            'Catering'      => 'Essential',
            'Photo Booths'  => 'Occasional',
        ]);

        Category::create([
            'name' => 'Wedding', 'slug' => 'wedding', 'kind' => Category::EVENT_TYPE,
            'is_active' => true, 'archetype' => 'Wedding & Related Ceremonies',
        ]);

        $data = ServiceRelevance::forBrowser();

        $this->assertSame('Wedding & Related Ceremonies', $data['archetypeOf']['wedding']);
        $this->assertSame('Essential', $data['tiers']['Wedding & Related Ceremonies'][$ids['Catering']]);
        $this->assertSame('Occasional', $data['tiers']['Wedding & Related Ceremonies'][$ids['Photo Booths']]);
    }

    /** The event type is matched case-insensitively; the picker sends whatever was typed. */
    public function test_the_lookup_is_keyed_lower_case(): void
    {
        Category::create([
            'name' => 'Baby Shower', 'slug' => 'baby-shower', 'kind' => Category::EVENT_TYPE,
            'is_active' => true, 'archetype' => 'Milestone & Personal Celebrations',
        ]);

        $this->assertArrayHasKey('baby shower', ServiceRelevance::forBrowser()['archetypeOf']);
    }

    /**
     * A tier is a ranking, so it sorts. Anything the matrix does not rank goes
     * last rather than being dropped — the client may still want it.
     */
    public function test_an_unranked_service_sorts_last_rather_than_disappearing(): void
    {
        $this->assertSame(0, ServiceRelevance::rank('Essential'));
        $this->assertSame(1, ServiceRelevance::rank('Common'));
        $this->assertSame(2, ServiceRelevance::rank('Occasional'));
        $this->assertSame(3, ServiceRelevance::rank(null));
        $this->assertSame(3, ServiceRelevance::rank('Nonsense'));
    }

    /* ── What the picker is given ───────────────────────────── */

    /** The picker gets the maps, and no longer the keyword guess. */
    public function test_the_picker_carries_the_masterlist_and_not_the_keyword_map(): void
    {
        $markup = file_get_contents(resource_path('views/components/service-picker.blade.php'));

        $this->assertStringContainsString('ServiceRelevance::forBrowser()', $markup);
        $this->assertStringNotContainsString("config('event-service-map", $markup);
    }

    /**
     * Nothing is hidden any more, so the banner must not claim otherwise. The
     * old copy said "Showing services that fit …" beside a "Show all" button,
     * which described a filter that no longer exists.
     */
    public function test_the_banner_does_not_claim_to_be_hiding_anything(): void
    {
        // Comments stripped first: the note left in the file quotes the copy it
        // replaced, exactly as the dead-controls guard has to do.
        $markup = preg_replace(
            '/\{\{--.*?--\}\}/s', '',
            file_get_contents(resource_path('views/components/service-picker.blade.php')),
        );

        $this->assertStringNotContainsString('Showing services that fit', $markup);
        $this->assertStringContainsString('everything is still here', $markup);
    }

    /** Each service carries the category the matrix actually ranks. */
    public function test_each_service_carries_the_category_the_matrix_ranks(): void
    {
        $markup = file_get_contents(resource_path('views/components/service-picker.blade.php'));

        $this->assertStringContainsString('data-parent="{{ $cat->parent_id }}"', $markup);
    }

    /* ── The word "Category" meant three things at once ─────── */

    /**
     * The Owner's note: the title "Categories" should say Event or Event Type,
     * and "Shop Packages is in two locations, it sounds repetitive".
     *
     * Both are the same problem. This page's default view is the event-type
     * wall — 106 occasions — while every heading on it said "Category", which
     * on this site also means one of the 27 service categories and, loosely,
     * one of the 241 services. One word for three tiers is how "Trivia Night"
     * came to sit under a heading about categories without looking wrong to
     * anyone who built it.
     */
    public function test_the_page_names_the_thing_it_is_actually_showing(): void
    {
        $markup = $this->stripComments(resource_path('views/events-categories.blade.php'));

        $this->assertStringContainsString('Explore by <span class="b">Event Type</span>', $markup);
        $this->assertStringNotContainsString('Explore by <span class="b">Category</span>', $markup);
        $this->assertStringNotContainsString('Search categories or services', $markup);
    }

    /** Shop Packages had its own place in the bar and a second one in a menu. */
    public function test_shop_packages_appears_once_in_the_public_nav(): void
    {
        $nav = $this->stripComments(resource_path('views/layouts/landing.blade.php'));

        $this->assertSame(1, substr_count($nav, 'Shop Packages'), 'the same link twice, two inches apart');
    }

    private function stripComments(string $path): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));
    }

    /**
     * One page, one name.
     *
     * Every link to the event-type wall now says the same thing. Before this it
     * was reached through three different words in four places — "Browse
     * Categories" in the client sidebar, "Categories" in the public mobile menu
     * and footer, and "Events" in two older partials — for one destination.
     */
    public function test_every_link_to_the_event_type_wall_uses_one_name(): void
    {
        $labels = [];

        foreach ([resource_path('views/layouts'), resource_path('views/partials')] as $dir) {
            foreach ($this->bladeFilesIn($dir) as $path) {
                $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));

                // Anchor by anchor: a pattern spanning the whole file walks past
                // the closing tag and captures whatever link comes next.
                preg_match_all('/<a\\b[^>]*>.*?<\\/a>/s', $src, $anchors);

                foreach ($anchors[0] as $anchor) {
                    if (! str_contains($anchor, "route('events-categories')")) {
                        continue;
                    }

                    $text = trim(preg_replace('/\\s+/', ' ', strip_tags($anchor)));

                    if ($text !== '') {
                        $labels[$text] = true;
                    }
                }
            }
        }

        /*
         * The rule is not that every label is byte-identical — the public
         * dropdown carries a description under its title, and the client
         * sidebar says "Explore Event Types" because that is the wording the
         * Owner asked for. The rule is that none of them uses the two words
         * that made this page unreadable: "Categories", which on this site also
         * means one of the 27 service categories, and "Events", which means a
         * client's own booked event.
         */
        foreach (array_keys($labels) as $label) {
            $this->assertStringContainsStringIgnoringCase(
                'event type', $label, "\"{$label}\" does not name the event-type wall",
            );
        }

        $this->assertNotEmpty($labels, 'the links themselves should still exist');
    }

    /** @return list<string> */
    private function bladeFilesIn(string $dir): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }
}
