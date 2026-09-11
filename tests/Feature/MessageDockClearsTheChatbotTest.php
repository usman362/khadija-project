<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The messages launcher and the AI assistant's bubble share a corner, and
 * must not cover each other.
 *
 * Ali, 2026-09-10, with a screenshot of the purple bubble sitting on top of the
 * orange launcher: "yeh dono buttons ek saath hogaye hain isse sahi se manage
 * kro". The launcher was at 18px, the bubble at 24px, so one hid the other.
 *
 * The launcher now stacks above the bubble. Its numbers are worked out from the
 * bubble's, so this reads both and checks they still agree; move or resize the
 * bubble and this fails before the two collide again.
 */
class MessageDockClearsTheChatbotTest extends TestCase
{
    private const LAUNCHER = 52;   // .md-launch
    private const GAP = 14;

    private function px(string $css, string $selector, string $prop): int
    {
        $this->assertMatchesRegularExpression('/' . preg_quote($selector, '/') . '\s*\{[^}]*' . $prop . ':\s*(\d+)px/s', $css);
        preg_match('/' . preg_quote($selector, '/') . '\s*\{[^}]*' . $prop . ':\s*(\d+)px/s', $css, $m);

        return (int) $m[1];
    }

    public function test_the_launcher_stacks_above_the_bubble_without_touching_it(): void
    {
        $bot  = file_get_contents(resource_path('views/partials/_ai_chatbot_widget.blade.php'));
        $dock = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        $bubbleBottom = $this->px($bot, '.aic-bubble', 'bottom');
        $bubbleRight  = $this->px($bot, '.aic-bubble', 'right');
        $bubbleSize   = $this->px($bot, '.aic-bubble', 'width');

        $this->assertSame(self::LAUNCHER, $this->px($dock, '.md-launch', 'width'));

        $dockRight  = $this->px($dock, 'body:has(.aic-bubble) .md', 'right');
        $dockBottom = $this->px($dock, 'body:has(.aic-bubble) .md', 'bottom');

        // Clear of the bubble, with the gap.
        $this->assertSame($bubbleBottom + $bubbleSize + self::GAP, $dockBottom);
        // Centred on it.
        $this->assertSame($bubbleRight + intdiv($bubbleSize - self::LAUNCHER, 2), $dockRight);
    }

    /**
     * On a phone the window opens above the launcher and fits the screen.
     * Found in the browser: at 375px it opened to the left and started 55px
     * off-screen.
     */
    public function test_on_a_phone_the_window_opens_above_and_fits(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        $this->assertMatchesRegularExpression('/@media \(max-width: 520px\) \{\s*\.md \{ flex-direction: column;/', $dock);
        // Width leaves the launcher's own right margin on the left as well.
        $this->assertStringContainsString('.md-win { width: calc(100vw - 36px); }', $dock);                          // 2 × 18
        $this->assertStringContainsString('body:has(.aic-bubble) .md-win { width: calc(100vw - 54px); }', $dock);   // 2 × 27
    }

    /** With the assistant off there is nothing to clear; the launcher keeps the corner. */
    public function test_the_launcher_only_moves_when_the_bubble_is_there(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        $this->assertStringContainsString('body:has(.aic-bubble) .md {', $dock);
        $this->assertMatchesRegularExpression('/^\s*\.md \{ position: fixed; right: 18px; bottom: 18px;/m', $dock);
    }
}
