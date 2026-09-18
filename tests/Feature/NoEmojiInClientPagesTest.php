<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * No decorative emoji on the client and public pages.
 *
 * D-21 (Khadijah, 13 Sep): "Emoji: remove them platform-wide … That includes
 * the dashboard welcome banner wave emoji and the emoji in My Professionals'
 * section headers." Emoji in headings, buttons and notes were deleted; emoji
 * that were the only thing in an icon slot became small line icons.
 *
 * Two things stay because they are features, not decoration: the emoji picker
 * in the AI chat widget (the person inserts an emoji into their message) and
 * the thumbs-up quick-send button on the client chat page. Plain typographic
 * symbols (★ ☆ ✓ ✔ ✕ ♡ ♥ ⌘ and the like) are not emoji and are allowed.
 *
 * Professional-only tools, admin screens and emails are outside this rule's
 * scope for now and are not scanned.
 */
class NoEmojiInClientPagesTest extends TestCase
{
    /** Directories and files (under resources/views) that must stay emoji-free. */
    private const SCOPE = [
        'client',
        'layouts/client.blade.php',
        'disputes',
        'cancellations',
        'forms',
        'public',
        'about.blade.php',
        'components',
        'partials',
        // AI tools a client can open (the professional-only ones are out of scope).
        'ai-tools/venue-analyzer.blade.php',
        'ai-tools/theme-advisor.blade.php',
        'ai-tools/guest-capacity.blade.php',
        'ai-tools/timeline-builder.blade.php',
        'ai-tools/checklist-generator.blade.php',
        'ai-tools/translator.blade.php',
        'ai-tools/message-assistant.blade.php',
        'ai-tools/contract-assistant.blade.php',
        'ai-tools/event-planner.blade.php',
        'ai-tools/index.blade.php',
    ];

    /**
     * Lines that may carry emoji because the emoji IS the feature.
     * file (relative to resources/views) => regex a line must match.
     */
    private const ALLOWED_LINES = [
        // The emoji picker: its category tabs and the palettes it inserts from.
        'partials/_ai_chatbot_widget.blade.php' => '/class="aic-emoji-tab|^\s*(events|people|food|symbols|objects):\s*\[/',
        // The thumbs-up quick-send button, and the script that sends it.
        'client/chat/index.blade.php' => '/id="cm-thumbs"|box\.value.*\'👍\'/',
        // The Live Messages window's emoji picker: the palette it inserts from.
        'partials/_live_message_dock.blade.php' => '/^\s*var EMOJI = \[/',
    ];

    /** Plain symbols in the scanned ranges that are typography, not emoji. */
    private const PLAIN_SYMBOLS = ['★', '☆', '✓', '✔', '✕', '✗', '✎', '✂', '✉', '☰', '✦', '✧', '➜', '➔', '✱', '✳', '✴', '❯', '❮', '➤', '♡', '♥', '⌘'];

    public function test_client_and_public_pages_show_no_emoji(): void
    {
        $emoji = '/[\x{1F000}-\x{1F2FF}\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}]|.\x{FE0F}/u';
        // Blade, HTML, CSS and JS comments are for developers. They are blanked
        // out line for line so the reported line numbers stay right.
        $comments = '/\{\{--.*?--\}\}|<!--.*?-->|(?<![\w"\'=\/])\/\*.*?\*\/|(?m:^[ \t]*\/\/[^\n]*$)/s';

        $found = [];
        foreach ($this->files() as $rel => $abs) {
            $source = preg_replace_callback($comments, fn ($m) => str_repeat("\n", substr_count($m[0], "\n")), file_get_contents($abs));
            foreach (explode("\n", $source) as $i => $line) {
                if (isset(self::ALLOWED_LINES[$rel]) && preg_match(self::ALLOWED_LINES[$rel], $line)) {
                    continue;
                }
                if (! preg_match_all($emoji, $line, $m)) {
                    continue;
                }
                $bad = array_diff(array_map(fn ($e) => rtrim($e, "\u{FE0F}"), $m[0]), self::PLAIN_SYMBOLS);
                if ($bad) {
                    $found[] = $rel . ':' . ($i + 1) . '  ' . implode(' ', array_unique($bad)) . '  ' . mb_substr(trim($line), 0, 90);
                }
            }
        }

        $this->assertSame([], $found, "Emoji are back on a client or public page (D-21):\n" . implode("\n", $found));
    }

    public function test_the_features_that_use_emoji_are_still_there(): void
    {
        // The allowlist above is only honest while these still exist.
        $this->assertStringContainsString('aic-emoji-tab', file_get_contents(resource_path('views/partials/_ai_chatbot_widget.blade.php')));
        $this->assertStringContainsString('id="cm-thumbs"', file_get_contents(resource_path('views/client/chat/index.blade.php')));
    }

    /** @return array<string, string> relative path => absolute path */
    private function files(): array
    {
        $files = [];
        foreach (self::SCOPE as $entry) {
            $abs = resource_path('views/' . $entry);
            if (is_file($abs)) {
                $files[$entry] = $abs;
                continue;
            }
            if (! is_dir($abs)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($abs, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $files[ltrim(str_replace(resource_path('views'), '', $file->getPathname()), '/')] = $file->getPathname();
                }
            }
        }

        $this->assertNotEmpty($files);

        return $files;
    }
}
