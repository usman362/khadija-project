<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Category artwork.
 *
 * The pictures were not missing — 273 came across from the old live site into
 * the v1 taxonomy, and when v2 replaced v1 as the live tree the import did not
 * carry them. A migration matches them by name, which brought 35 of the 106
 * event types their own artwork back.
 *
 * The occasions with none draw a tinted tile with their initial, and keep
 * doing so until somebody uploads a picture. A stock photograph was here for
 * an afternoon on 2026-08-20 and came straight back out on the Owner's
 * instruction: he is uploading the real ones, and a stand-in in the meantime
 * is a placeholder somebody has to remember to remove.
 */
class CategoryArtworkTest extends TestCase
{
    use RefreshDatabase;

    private function eventType(string $name, array $over = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-art',
            'kind' => Category::EVENT_TYPE,
            'is_active' => true,
        ], $over));
    }

    public function test_a_category_with_artwork_uses_it(): void
    {
        $c = $this->eventType('Wedding', ['thumbnail' => 'categories/thumbnails/wedding.png']);

        $this->assertTrue($c->hasOwnImage());
        $this->assertStringContainsString('categories/thumbnails/wedding.png', $c->imageUrl());
    }

    public function test_a_cover_image_counts_as_artwork_too(): void
    {
        // The controller used to read `thumbnail` inline and miss this column
        // entirely, so a category with only a cover looked like it had nothing.
        $c = $this->eventType('Gala', ['cover_image' => 'categories/covers/gala.png']);

        $this->assertTrue($c->hasOwnImage());
        $this->assertStringContainsString('categories/covers/gala.png', $c->imageUrl());
    }

    public function test_a_category_without_artwork_claims_none(): void
    {
        $c = $this->eventType('Bachelorette Party');

        $this->assertFalse($c->hasOwnImage());
        $this->assertNull($c->imageUrl(), 'a card must not point at a picture that does not exist');
    }

    public function test_no_stock_photography_stands_in_for_a_category(): void
    {
        /*
         * The Owner is uploading the real pictures. A stock photograph in the
         * meantime stops looking like a placeholder and starts looking like a
         * decision — and the ones that got left behind would be the ones nobody
         * noticed.
         */
        $src = file_get_contents(app_path('Models/Category.php'));
        $src = preg_replace('#/\*.*?\*/#s', '', $src);

        $this->assertStringNotContainsString('unsplash', strtolower($src));
        $this->assertStringNotContainsString('photo-1', $src);
    }

    public function test_the_wall_draws_the_tile_where_there_is_no_picture(): void
    {
        config(['taxonomy.version' => 'v2']);

        $this->eventType('Wedding', ['taxonomy_version' => 'v2', 'thumbnail' => 'categories/thumbnails/wedding.png']);
        $this->eventType('Bachelor Party', ['taxonomy_version' => 'v2']);

        $html = $this->get('/event-types')->assertOk()->getContent();

        $this->assertStringContainsString('categories/thumbnails/wedding.png', $html);
        $this->assertStringContainsString('et-all-init', $html, 'the occasion with no picture should draw its tile');
        $this->assertSame(2, substr_count($html, 'class="et-all-card"'));
    }

    /**
     * The page ran the full width of the window while the navbar and footer
     * above and below it stayed centred, because .et-shell was written into the
     * stylesheet and never put on any element.
     */
    public function test_the_page_content_sits_inside_the_shell(): void
    {
        config(['taxonomy.version' => 'v2']);
        $this->eventType('Wedding', ['taxonomy_version' => 'v2']);

        $html = $this->get('/event-types')->assertOk()->getContent();

        $this->assertStringContainsString('class="et-shell"', $html,
            'the page is not inside its own container and will stretch edge to edge');

        // And the container is the same width the chrome uses.
        $this->assertMatchesRegularExpression('/\.et-shell \{[^}]*max-width: 1320px/', $html);
    }
}
