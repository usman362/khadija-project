<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Everything We Cover" (/events-categories) was removed on the Owner's
 * instruction, 2026-08-20: "this entire webpage system needs to be removed".
 *
 * It was a second way into the same tree Explore Event Types already covers,
 * and its cards led to the same /category/{slug} pages.
 *
 * A page taken out on purpose has a way of coming back — a link left in a
 * footer, a row re-added to the sitemap, a controller restored from a branch.
 * These hold it out.
 */
class EventsCategoriesRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_old_url_redirects_rather_than_404ing(): void
    {
        // It was in the sitemap and has been shared, so it must land somewhere
        // — a 404 punishes people who followed a link we published.
        $this->get('/events-categories')
            ->assertRedirect('/event-types')
            ->assertStatus(301);
    }

    public function test_the_page_and_its_parts_are_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/events-categories.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/partials/_ec-tree-item.blade.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Public/EventsCategoriesController.php'));
    }

    public function test_nothing_in_the_app_still_links_to_it(): void
    {
        /*
         * Not "does the link work" — the redirect makes any such link work.
         * The point is that no journey inside the app should route through a
         * page that was deliberately taken out; every one of them now points
         * at Explore Event Types directly.
         */
        $offenders = [];

        foreach ($this->views() as $path) {
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));

            if (str_contains($body, "route('events-categories')")) {
                $offenders[] = str_replace(base_path() . '/', '', $path);
            }
        }

        $this->assertSame([], $offenders,
            'these views still link to the removed page: ' . implode(', ', $offenders));
    }

    public function test_the_sitemap_does_not_advertise_a_redirect(): void
    {
        // Publishing a URL that 301s is telling a search engine to index a
        // door rather than a room.
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('/events-categories', $xml);
        $this->assertStringContainsString('/event-types', $xml);
    }

    /**
     * The rule the removed page's own tests protected, carried onto the wall
     * that remains.
     *
     * V2 event types and service categories are both root-level, so a query
     * that forgets to say which it wants dumps Catering in beside Wedding as
     * if both were occasions. That is what made the old page unreadable.
     */
    public function test_the_event_type_wall_does_not_mix_service_categories_in(): void
    {
        config(['taxonomy.version' => 'v2']);

        \App\Models\Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-wall-check',
            'kind' => \App\Models\Category::EVENT_TYPE, 'is_active' => true, 'sort_order' => 10,
        ]);
        \App\Models\Category::create([
            'name' => 'Catering', 'slug' => 'catering-wall-check',
            'kind' => \App\Models\Category::SERVICE_CATEGORY, 'is_active' => true, 'sort_order' => 10,
        ]);

        $this->get('/event-types')
            ->assertOk()
            ->assertSee('Wedding')
            ->assertDontSee('Catering');
    }

    /** @return array<int, string> */
    private function views(): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $out[] = $file->getPathname();
            }
        }

        return $out;
    }
}
