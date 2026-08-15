<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * An overlay that would not go away.
 *
 * The Owner reported a page that "completely freezes me out unless I refresh —
 * nothing works if I don't". The cause was one line of CSS: the crop dialog
 * carried the HTML `hidden` attribute, and its own rule set `display: flex`.
 *
 * `hidden` is only `display: none` from the browser's default stylesheet, so
 * any author rule that sets a display beats it. The dialog therefore sat over
 * the whole page at z-index 9999 with a dark backdrop, and its Cancel and ✕
 * buttons — which set `hidden = true` — did nothing at all. Every click landed
 * on the backdrop.
 *
 * Two other elements had it: the influencer portal's overlay and the chatbot's
 * attachment tray. Same shape, same result if they ever opened.
 */
class HiddenActuallyHidesTest extends TestCase
{
    public function test_nothing_that_can_be_hidden_overrides_the_hidden_attribute(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));

            // Classes on an element that also carries the hidden attribute.
            $classes = [];
            foreach ([
                '/class="([^"]+)"[^>]*\shidden[\s>]/',
                '/\shidden[^>]*\sclass="([^"]+)"/',
            ] as $pattern) {
                if (preg_match_all($pattern, $src, $m)) {
                    foreach ($m[1] as $list) {
                        $classes = array_merge($classes, preg_split('/\s+/', trim($list)));
                    }
                }
            }

            foreach (array_unique(array_filter($classes)) as $class) {
                $c = preg_quote($class, '/');

                $setsDisplay = preg_match('/\.' . $c . '\s*\{[^}]*display\s*:\s*(flex|grid|block|inline-flex|inline-block)/', $src);
                $guarded     = preg_match('/\.' . $c . '\[hidden\]/', $src);

                if ($setsDisplay && ! $guarded) {
                    $offenders[] = str_replace(resource_path('views/'), '', $path) . '  .' . $class;
                }
            }
        }

        $this->assertSame([], $offenders,
            "these stay on screen when hidden, and swallow every click:\n" . implode("\n", $offenders));
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
