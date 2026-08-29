<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The page shell and the card border, site-wide.
 *
 * Sir Peter, 27 Aug: "can you add borderlines, or maybe darken the background
 * slight … this is for the entire website wide".
 *
 * The shell was pure white and the cards were white too, separated by a 7%
 * black hairline. On a bright screen that hairline disappears and a page of
 * cards reads as one flat sheet — which is what his before/after showed.
 *
 * Two values, in the design tokens rather than on any page, so all three
 * layouts get them at once. This test exists because they are exactly the kind
 * of value a later edit resets to #ffffff without anyone noticing.
 */
class PageShellContrastTest extends TestCase
{
    /** The portals: one product, so one shell. */
    public function test_the_page_shell_is_not_pure_white(): void
    {
        foreach (['client', 'professional'] as $layout) {
            $css = $this->lightBlock($layout);

            preg_match('/--bg-primary:\s*([^;]+);/', $css, $m);

            $this->assertNotEmpty($m, "{$layout}: no --bg-primary in the light theme.");
            $this->assertNotSame(
                '#ffffff',
                strtolower(trim($m[1])),
                "{$layout}: the page shell is pure white again, so white cards have nothing to lift off.",
            );
        }
    }

    /** A hairline you cannot see is not a border. */
    public function test_the_card_border_is_visible(): void
    {
        foreach (['client', 'professional'] as $layout) {
            $css = $this->lightBlock($layout);

            preg_match('/--border-color:\s*rgba\([^)]*?([\d.]+)\s*\)/', $css, $m);

            $this->assertNotEmpty($m, "{$layout}: no rgba --border-color in the light theme.");
            $this->assertGreaterThanOrEqual(
                0.1,
                (float) $m[1],
                "{$layout}: the border is back under 10% opacity, which reads as no border at all.",
            );
        }
    }

    /** The public pages use their own names for the same two things. */
    public function test_the_public_pages_match(): void
    {
        $css = file_get_contents(resource_path('views/layouts/landing.blade.php'));

        preg_match('/--bg:\s*([^;]+);/', $css, $bg);
        $this->assertNotEmpty($bg);
        $this->assertNotSame('#ffffff', strtolower(trim($bg[1])));
    }

    /** Cards themselves stay white — only the shell behind them moved. */
    public function test_cards_are_still_white(): void
    {
        foreach (['client', 'professional'] as $layout) {
            preg_match('/--bg-card:\s*([^;]+);/', $this->lightBlock($layout), $m);

            $this->assertSame(
                '#ffffff',
                strtolower(trim($m[1])),
                "{$layout}: card backgrounds moved. Only the shell was meant to.",
            );
        }
    }

    /** The `[data-theme="light"]` token block for one layout. */
    private function lightBlock(string $layout): string
    {
        $css = file_get_contents(resource_path("views/layouts/{$layout}.blade.php"));
        $at  = strpos($css, '[data-theme="light"]');

        $this->assertNotFalse($at, "{$layout}: no light theme block.");

        return substr($css, $at, 2000);
    }
}
