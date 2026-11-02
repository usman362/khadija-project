<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The same column the client side was given on 25 September.
 *
 * The note left when that one was fixed says this layout had the same fault
 * and worse: `.pro-topbar` was `padding: 12px 26px` with no cap, while
 * `.cl-content` was `padding: 28px 32px` capped at 1760 and centred. The
 * gutters were different numbers, so the bar and the page never lined up at
 * any width, and past the cap the page centred while the bar ran on to the
 * edges. It was left until this side was opened. It is open.
 *
 * Same construction as the client's, and held for the same reason: the way it
 * broke was two places each keeping their own numbers.
 */
class ProfessionalPagesShareOneColumnTest extends TestCase
{
    private function layout(): string
    {
        return file_get_contents(base_path('resources/views/layouts/professional.blade.php'));
    }

    public function test_the_column_is_declared_once(): void
    {
        $layout = $this->layout();

        foreach (['--cl-max:', '--cl-edge:'] as $token) {
            $this->assertSame(
                1,
                substr_count($layout, $token),
                "{$token} is declared more than once, so two places can disagree about it.",
            );
        }

        // Wide and narrow, both on the one element the bar and the page sit in.
        $this->assertSame(2, substr_count($layout, '--cl-gutter:'));
        $this->assertSame(
            2,
            preg_match_all('/\.cl-main\s*\{[^}]*--cl-gutter:/s', $layout),
            'Something other than .cl-main is setting the gutter.',
        );
    }

    public function test_the_bar_and_the_page_take_their_edges_from_it(): void
    {
        $layout = $this->layout();

        $this->assertMatchesRegularExpression(
            '/\.pro-topbar\s*\{[^}]*padding:\s*12px\s+var\(--cl-edge\)/s',
            $layout,
            'The bar is measuring itself again.',
        );

        $this->assertMatchesRegularExpression(
            '/\.cl-content\s*\{[^}]*padding:\s*28px\s+var\(--cl-edge\)/s',
            $layout,
            'The page is measuring itself again.',
        );
    }

    public function test_neither_carries_its_own_numbers_any_more(): void
    {
        $layout = $this->layout();

        foreach ([
            'padding: 12px 26px 2px;',
            'padding: 28px 32px;',
            '.cl-content { padding: 20px 16px; }',
        ] as $old) {
            $this->assertStringNotContainsString(
                $old,
                $layout,
                'A hard-coded edge is back; that is how the two columns parted in the first place.',
            );
        }
    }

    /**
     * The sidebar still gets out of the way on a phone. Worth holding next to
     * the column work, because both are about what `.cl-main` is told, and a
     * page pushed off by a sidebar that should have gone is the one way this
     * can look fine at every width above it and be unusable below.
     */
    public function test_the_page_is_not_left_behind_the_sidebar_on_a_phone(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*768px\)\s*\{[^@]*\.cl-main\s*\{\s*margin-left:\s*0;/s',
            $this->layout(),
        );
    }
}
