<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sir Peter, 29 September: "i dont understand why image 1 is all aligned but
 * the others are not, so far."
 *
 * The page column was already one column everywhere, and measuring it said so.
 * What differed was the search box in the orange bar. The title never shrank
 * and the search took whatever room was left, so the box was as wide as the
 * title was short: the Emergency Request's search began at 509, the
 * dashboard's at 565, the Direct Request's at 640. Three pages, three search
 * bars, and nothing on the page to explain why.
 *
 * The box has a width of its own now and the title gives way instead. This
 * holds that arrangement, because the fault was a width nobody chose.
 */
class TheBannerSearchIsOneWidthTest extends TestCase
{
    private function layout(): string
    {
        return file_get_contents(base_path('resources/views/layouts/client.blade.php'));
    }

    /** The search is a fixed size, so the page title cannot change it. */
    public function test_the_search_box_has_a_width_of_its_own(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.cl-banner-search \{[^}]*flex: 0 0 \d+px/',
            $this->layout(),
            'The search box takes its width from whatever the title leaves again, '
            .'so it is a different size on every page.',
        );
    }

    /** And the title is the one that yields, rather than pushing it about. */
    public function test_the_title_gives_way_instead(): void
    {
        $layout = $this->layout();

        $this->assertStringNotContainsString(
            '.cl-banner-text { flex-shrink: 0; }',
            $layout,
            'The title refuses to shrink again, which is what made the search box vary.',
        );

        $this->assertMatchesRegularExpression(
            '/\.cl-banner-text h1 \{[^}]*text-overflow: ellipsis/',
            $layout,
            'A long page title has nowhere to go, so it will push the search box narrow.',
        );
    }
}
