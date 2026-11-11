<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The inbox is three columns: the conversation list, the conversation, and the
 * panel about whoever you are talking to. Under 1100px they are meant to stack.
 *
 * They did not, and the reason is worth keeping: the stacking rule was written
 * as `.cm-main { grid-template-columns: 1fr }` inside a media query, while the
 * three-column rule is `.cm-main.has-side`. Two classes beat one, and being
 * inside a media query adds no specificity, so the three-column rule won at
 * every width. At 1024px the middle column — the conversation itself — was
 * about forty pixels wide, wrapping one word to a line, with the profile panel
 * drawn on top of it.
 *
 * Asserted against the stylesheet rather than a rendered width because the
 * fault is in the selector, and a browser is the only thing that can measure
 * the other.
 */
class TheInboxStacksOnANarrowScreenTest extends TestCase
{
    private function stylesheet(): string
    {
        return file_get_contents(base_path('resources/views/client/chat/index.blade.php'));
    }

    public function test_the_stacking_rule_is_specific_enough_to_apply(): void
    {
        // The media query block, from its brace to the matching close.
        preg_match('/@media \(max-width: 1100px\) \{(.+?)\n    \}/s', $this->stylesheet(), $m);

        $this->assertNotEmpty($m, 'the narrow-screen rule for the inbox has gone');

        $this->assertStringContainsString('.cm-main.has-side', $m[1],
            'the stacking rule does not name .has-side, so the three-column rule outranks it '
            .'and the conversation column collapses');

        $this->assertStringContainsString('.cm-main.has-side.panel-closed', $m[1],
            'the closed-panel variant is three classes and outranks the stacking rule too');
    }
}
