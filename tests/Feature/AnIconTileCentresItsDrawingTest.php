<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The specificity trap, pinned so it cannot come back a third time.
 *
 * A rule like `.pd-attn-row span` is (0,1,1). Any class-only rule on a span
 * inside that block is (0,1,0) and loses, whatever order they are written
 * in. That is how an icon tile lost its `display:flex` and stopped centring
 * its drawing, which then sat in the top left corner of the tile, and how
 * the urgency chips beside it lost their colour and their size.
 *
 * It cost a morning on the Leads page first, where `.lc-who span` beat
 * `.lc-av` and left grey initials hanging outside their circle, and it was
 * walked into again on the dashboard the next day. Neither time did anything
 * fail: the page rendered, every test passed, and the drawing was simply in
 * the wrong corner. That is why it needs a test of its own.
 *
 * SCOPE, honestly. This checks the blocks that are known to hold a tile or a
 * chip. It cannot catch a blanket selector written tomorrow over some other
 * block, because whether one is harmful depends on what its children are
 * called. When a tile is added, add its block here.
 */
class AnIconTileCentresItsDrawingTest extends TestCase
{
    /**
     * Block, and the elements inside it that carry classes of their own.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function blocks(): array
    {
        $dash  = 'resources/views/professional/dashboard.blade.php';
        $leads = 'resources/views/professional/leads/index.blade.php';

        return [
            'attention rows'   => [$dash, 'pd-attn-row', 'span'],
            'do this first'    => [$dash, 'pd-next', 'span'],
            'since last visit' => [$dash, 'pd-new', 'span'],
            'summary cards'    => [$dash, 'pd-sumcard', 'span'],
            'lead avatars'     => [$leads, 'lc-who', 'span'],
        ];
    }

    /**
     * The block does not reach past the children that name themselves.
     *
     * A child combinator is fine: `.x > b` says which b it means. A
     * descendant selector does not, and takes every one.
     *
     * @dataProvider blocks
     */
    public function test_a_block_holding_named_children_does_not_blanket_them(
        string $view,
        string $block,
        string $element,
    ): void {
        $css = file_get_contents(base_path($view));

        preg_match_all('/\.'.preg_quote($block, '/').'\s+'.$element.'\s*\{/i', $css, $hits);

        $this->assertSame(
            [],
            $hits[0],
            ".{$block} {$element} outranks every class on the <{$element}>s inside it, "
            ."which is how an icon tile loses display:flex and draws in its corner. "
            ."Give that {$element} a class and style the class.",
        );
    }

    /** Every tile that holds a drawing says how to place it. */
    public function test_every_icon_tile_centres_what_it_holds(): void
    {
        $css = file_get_contents(base_path('resources/views/professional/dashboard.blade.php'));

        foreach (['pd-attn-ico', 'pd-sum-ico', 'pd-new-ico', 'pd-card-ico', 'pd-stat-ico'] as $tile) {
            preg_match('/\.'.$tile.'\s*\{([^}]*)\}/', $css, $m);

            $this->assertNotEmpty($m, ".{$tile} has no rule at all.");

            foreach (['display: flex', 'align-items: center', 'justify-content: center'] as $need) {
                $this->assertStringContainsString(
                    $need,
                    $m[1],
                    ".{$tile} holds a drawing and must centre it ({$need}).",
                );
            }
        }
    }
}
