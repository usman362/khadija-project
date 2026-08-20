<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every event type card carries a picture.
 *
 * Peter asked for "the events images" on a page showing 106 tinted tiles with
 * a letter in them. The artwork was not missing — 273 pictures came across
 * from the old live site into the v1 taxonomy, and when v2 replaced v1 as the
 * live tree the import did not carry them. A migration matches them by name.
 *
 * The 71 occasions the old site never had a page for get a stand-in chosen by
 * what the occasion is. It decorates and nothing else: it makes no claim about
 * a professional, a price or a place, and anything an admin uploads replaces it.
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

    public function test_a_category_with_its_own_artwork_uses_it(): void
    {
        $c = $this->eventType('Wedding', ['thumbnail' => 'categories/thumbnails/wedding.png']);

        $this->assertTrue($c->hasOwnImage());
        $this->assertStringContainsString('categories/thumbnails/wedding.png', $c->imageUrl());
        $this->assertStringNotContainsString('unsplash', $c->imageUrl());
    }

    public function test_a_category_without_artwork_still_gets_a_picture(): void
    {
        // A card with no picture was the complaint.
        $c = $this->eventType('Bachelorette Party');

        $this->assertFalse($c->hasOwnImage());
        $this->assertStringContainsString('images.unsplash.com', $c->imageUrl());
    }

    public function test_the_longest_matching_word_wins(): void
    {
        /*
         * "Bachelorette Party" contains "bachelor", and "Block Party" contains
         * "party". A shorter key matching first is how the bachelorette ends up
         * with the bachelor's photograph.
         */
        $this->assertNotSame(
            Category::stockFor('Bachelor Party'),
            Category::stockFor('Bachelorette Party'),
        );

        $this->assertSame(Category::stockFor('Block Party'), Category::stockFor('Block Party'));
        $this->assertNotSame(Category::stockFor('Block Party'), Category::stockFor('Bachelor Party'));
    }

    public function test_an_occasion_nobody_wrote_a_rule_for_still_gets_something(): void
    {
        $this->assertStringContainsString('images.unsplash.com', Category::stockFor('Something Nobody Listed'));
        $this->assertStringContainsString('images.unsplash.com', Category::stockFor(null));
    }

    public function test_the_wall_draws_a_picture_on_every_card_and_no_letters(): void
    {
        config(['taxonomy.version' => 'v2']);

        $this->eventType('Wedding', ['taxonomy_version' => 'v2', 'thumbnail' => 'categories/thumbnails/wedding.png']);
        $this->eventType('Bachelor Party', ['taxonomy_version' => 'v2']);

        $html = $this->get('/event-types')->assertOk()->getContent();

        $this->assertStringNotContainsString('et-all-init', $html,
            'a card fell back to its initial — the wall of letters is back');

        $this->assertSame(2, substr_count($html, 'class="et-all-card"'));
        $this->assertStringContainsString('categories/thumbnails/wedding.png', $html);
    }

    /**
     * A dead stock id renders a blank card, which is worse than the letter it
     * replaced. This checks the ids are well-formed and unique per lookup —
     * it deliberately does NOT call Unsplash, because a test that needs the
     * network fails on a train.
     */
    public function test_every_stock_id_is_well_formed(): void
    {
        $src = file_get_contents(app_path('Models/Category.php'));

        preg_match_all("/'(photo-[0-9a-zA-Z-]+)'/", $src, $m);

        $this->assertGreaterThan(10, count($m[1]), 'the stock map has gone missing');

        foreach (array_unique($m[1]) as $id) {
            $this->assertMatchesRegularExpression('/^photo-\d{10,}-[0-9a-f]{12}$/', $id,
                "{$id} is not an Unsplash photo id");
        }
    }
}
