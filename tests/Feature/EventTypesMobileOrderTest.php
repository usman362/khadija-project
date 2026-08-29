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

        // Both numbers, so the page it opens is no surprise: the card quotes the
        // recommended count and that page lists every category.
        $this->assertMatchesRegularExpression('/\d+ of \d+ categories recommended/', $html);
    }
}
