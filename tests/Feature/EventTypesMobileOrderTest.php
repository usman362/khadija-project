<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On a phone the Event Types page opened onto a plain list of ten shortcuts.
 *
 * The page is a two-column grid — a rail of popular event types beside the
 * cards. Below 1080px it becomes one column, and the rail is first in the
 * markup, so on a phone that text list stood between the reader and the event
 * types themselves. The cards, with their pictures, are the point of the page.
 *
 * Measured in a mobile viewport: the rail sat at 679px with the first card at
 * 740px; it now sits below them.
 */
class EventTypesMobileOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_loads(): void
    {
        $this->get(route('public.event-types'))->assertSuccessful();
    }

    public function test_the_cards_come_before_the_shortcut_list_on_narrow_screens(): void
    {
        $html = $this->get(route('public.event-types'))->assertSuccessful()->getContent();

        // The rail is first in the DOM for the desktop layout, so the single
        // column has to reorder it. Without this the list is what a phone meets
        // first.
        $this->assertStringContainsString('.et-rail-card { position: static; order: 2; }', $html,
            'The shortcut rail is not moved below the cards on a narrow screen.');
        $this->assertStringContainsString('.et-browse > div { order: 1; }', $html,
            'The cards are not moved above the shortcut rail.');
    }

    /**
     * "15 services available" was wrong twice over.
     *
     * The number counts service CATEGORIES the archetype recommends, not
     * services — a wedding touches 171 of the 241 services. And the page the
     * card opens lists all 27 categories with the recommended ones marked, so
     * the card promised 15 and delivered 27. Peter asked why the number never
     * matched what he found inside, and it never could.
     */
    public function test_the_card_count_says_what_it_is_counting(): void
    {
        // A card only renders if there is an event type to render.
        \App\Models\Category::create([
            'name' => 'Wedding', 'slug' => 'wedding',
            'kind' => \App\Models\Category::EVENT_TYPE,
            'archetype' => 'Wedding & Related Ceremonies', 'is_active' => true,
        ]);

        $html = $this->get(route('public.event-types'))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('services available', $html,
            'The card still calls a count of service categories a count of services.');

        // The card quotes what the page HOLDS, so clicking it is never a
        // surprise. A recommended subset here opened onto every category.
        $this->assertMatchesRegularExpression('/\d+ service categor(y|ies)/', $html);
    }

    /**
     * The rail badge was a bare number. "Bachelor Party  17" tells a visitor
     * nothing, and the list was headed "All Event Types" above ten of the 106,
     * with a "View all event types" link directly underneath contradicting it.
     */
    public function test_the_rail_explains_its_number_and_does_not_claim_to_be_everything(): void
    {
        \App\Models\Category::create([
            'name' => 'Bachelor Party', 'slug' => 'bachelor-party',
            'kind' => \App\Models\Category::EVENT_TYPE,
            'archetype' => 'Wedding & Related Ceremonies', 'is_active' => true,
        ]);

        $html = $this->get(route('public.event-types'))->assertSuccessful()->getContent();

        $this->assertStringContainsString('The number is how many service categories you can choose from', $html);
        $this->assertStringContainsString('service categories to choose from for a Bachelor Party', $html);

        // A list of ten must not call itself all of them.
        $this->assertStringNotContainsString('<h4>All Event Types</h4>', $html);
    }

    /**
     * Event images were cut off at the top — signs and decorations lost.
     *
     * The card box was 1.94:1 while the pictures are 1.50:1, so `cover` had to
     * take a slice off the top and the bottom of nearly every one. Measured on
     * the live page: 120px showed 81% of each picture, 144px shows 93%, and the
     * remaining crop is pushed off the bottom instead of off the sign.
     *
     * Peter's rules for the fix, all held here: one height for every card, no
     * stretching, cover kept.
     */
    public function test_the_card_image_area_shows_more_of_the_picture(): void
    {
        $html = $this->get(route('public.event-types'))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('.et-all-img { height: 120px', $html);
        $this->assertStringNotContainsString('.et-all-img { height: 118px', $html);
        $this->assertStringContainsString('.et-all-img { height: 144px', $html);

        // What is left to crop comes off the bottom, not off the top.
        $this->assertStringContainsString('object-position: center 35%', $html);

        // Still cover — never contain, which would leave bands, and never a
        // stretch, which would distort.
        $this->assertStringContainsString('object-fit: cover', $html);
    }

    /** Every card, the same height. A ragged grid was explicitly ruled out. */
    public function test_every_card_uses_the_same_image_height(): void
    {
        $html = $this->get(route('public.event-types'))->assertSuccessful()->getContent();

        preg_match_all('/\.et-all-img\s*\{[^}]*height:\s*(\d+)px/', $html, $m);

        $this->assertNotEmpty($m[1]);
        $this->assertCount(1, array_unique($m[1]),
            'The rule appears more than once with different heights, so the cards will not line up.');
    }
}
