<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Checklist row 180 — the accessibility pass, kept passed.
 *
 * The row says the conformance level was never decided. It is WCAG 2.1 AA:
 * the row itself names it as "the common legal-compliance baseline", and
 * every US accessibility claim settles there.
 *
 * The audit found four kinds of failure. Fixing them once is worth little on
 * its own — the next screen somebody builds puts them straight back. So this
 * scans the templates and fails the build instead, which is the only version
 * of an accessibility pass that survives contact with more development.
 *
 * Scanning the SOURCE rather than the rendered page is deliberate: it covers
 * every screen including the ones no test renders, and it names the file to
 * fix rather than a URL.
 */
class AccessibilityBaselineTest extends TestCase
{
    /** @return array<int, string> */
    private function views(): array
    {
        // The design-spec pages are internal reference documents, not product.
        return array_values(array_filter(
            glob(resource_path('views/**/*.blade.php'), GLOB_BRACE) + $this->recursiveViews(),
            fn ($path) => ! str_contains($path, '/design-'),
        ));
    }

    private function recursiveViews(): array
    {
        $out = [];
        $it  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $out[] = $file->getPathname();
            }
        }

        return $out;
    }

    /**
     * Every tag of $name in the source, as [start, end] offsets.
     *
     * Written by hand rather than with a regex because Blade breaks the
     * obvious one: `[^>]*` stops at the first `>` it meets, and inside
     * `{{ $post->cover }}` that is the arrow operator. An earlier version of
     * this scan miscounted for exactly that reason, and the fix built on it
     * inserted an attribute into the middle of an expression and 500'd three
     * pages.
     *
     * @return array<int, array{0:int,1:int}>
     */
    private function tags(string $src, string $name): array
    {
        $out = [];

        foreach ($this->matchAll('/<' . $name . '\b/', $src) as $start) {
            $i = $start + strlen($name) + 1;
            $quote = null;
            $depth = 0;

            while ($i < strlen($src)) {
                $c = $src[$i];

                if ($quote !== null) {
                    if ($c === $quote) {
                        $quote = null;
                    }
                } elseif (str_starts_with(substr($src, $i, 3), '{!!') || str_starts_with(substr($src, $i, 2), '{{')) {
                    $depth++;
                    $i += 2;
                    continue;
                } elseif (str_starts_with(substr($src, $i, 3), '!!}') || str_starts_with(substr($src, $i, 2), '}}')) {
                    $depth = max(0, $depth - 1);
                    $i += 2;
                    continue;
                } elseif ($depth === 0) {
                    if ($c === '"' || $c === "'") {
                        $quote = $c;
                    } elseif ($c === '>') {
                        $out[] = [$start, $i + 1];
                        break;
                    }
                }

                $i++;
            }
        }

        return $out;
    }

    /** @return array<int, int> */
    private function matchAll(string $pattern, string $src): array
    {
        preg_match_all($pattern, $src, $m, PREG_OFFSET_CAPTURE);

        return array_column($m[0], 1);
    }

    private function relative(string $path): string
    {
        return str_replace(resource_path('views') . '/', '', $path);
    }

    /* ── 1.1.1 Non-text Content ─────────────────────────────── */

    public function test_every_image_has_alt_text(): void
    {
        $offenders = [];

        foreach ($this->views() as $path) {
            $src = file_get_contents($path);

            foreach ($this->tags($src, 'img') as [$a, $b]) {
                if (! preg_match('/\balt\s*=/', substr($src, $a, $b - $a))) {
                    $offenders[] = $this->relative($path) . ': ' . substr(substr($src, $a, $b - $a), 0, 80);
                }
            }
        }

        $this->assertSame([], $offenders,
            "Images without alt. An avatar beside the name it depicts takes alt=\"\" — repeating the name makes a screen reader say it twice.\n"
            . implode("\n", $offenders));
    }

    /* ── 4.1.2 Name, Role, Value ────────────────────────────── */

    public function test_every_select_has_an_accessible_name(): void
    {
        $offenders = [];

        foreach ($this->views() as $path) {
            $src = file_get_contents($path);

            foreach ($this->tags($src, 'select') as [$a, $b]) {
                $tag = substr($src, $a, $b - $a);

                if (! str_contains($tag, 'aria-label') && ! preg_match('/\bid\s*=/', $tag)) {
                    $offenders[] = $this->relative($path) . ': ' . substr($tag, 0, 80);
                }
            }
        }

        $this->assertSame([], $offenders,
            "Selects with no accessible name. A filter whose only label is its first option needs aria-label.\n"
            . implode("\n", $offenders));
    }

    public function test_no_control_is_an_icon_with_no_name(): void
    {
        $offenders = [];

        foreach ($this->views() as $path) {
            $src = file_get_contents($path);

            foreach (['button', 'a'] as $name) {
                foreach ($this->tags($src, $name) as [$a, $b]) {
                    $tag = substr($src, $a, $b - $a);

                    if (str_contains($tag, 'aria-label') || preg_match('/\btitle\s*=/', $tag)) {
                        continue;
                    }

                    $close = strpos($src, '</' . $name . '>', $b);

                    if ($close === false || $close - $b > 900) {
                        continue;
                    }

                    $inner = substr($src, $b, $close - $b);

                    if (! str_contains($inner, '<svg') && ! str_contains($inner, '<i ')) {
                        continue;
                    }

                    $text = preg_replace('/<svg.*?<\/svg>|<i\b[^>]*>.*?<\/i>|\s/s', '', $inner);

                    if ($text === '') {
                        $offenders[] = $this->relative($path) . ': ' . substr($tag, 0, 80);
                    }
                }
            }
        }

        $this->assertSame([], $offenders,
            "Icon-only controls with no name. A screen reader announces these as just \"button\".\n"
            . implode("\n", $offenders));
    }

    /* ── 2.1.1 Keyboard ─────────────────────────────────────── */

    /**
     * The failure that actually stops somebody using the site.
     *
     * A div or span with onclick takes no focus and answers no key press, so
     * a keyboard user cannot reach it at all. A real button gets focus, Enter
     * and Space for free.
     *
     * The one accepted exception is a backdrop that is hidden from assistive
     * technology and has a keyboard equivalent elsewhere — clicking outside a
     * drawer is a mouse gesture, and Escape is the keyboard one.
     */
    public function test_nothing_is_clickable_but_unreachable_by_keyboard(): void
    {
        $offenders = [];

        foreach ($this->views() as $path) {
            $src = file_get_contents($path);

            foreach (['div', 'span', 'li', 'td'] as $name) {
                foreach ($this->tags($src, $name) as [$a, $b]) {
                    $tag = substr($src, $a, $b - $a);

                    if (! preg_match('/\bonclick\s*=/', $tag)) {
                        continue;
                    }

                    if (str_contains($tag, 'aria-hidden="true"') || str_contains($tag, 'tabindex')) {
                        continue;
                    }

                    $offenders[] = $this->relative($path) . ': ' . substr($tag, 0, 90);
                }
            }
        }

        $this->assertSame([], $offenders,
            "Clickable elements a keyboard cannot reach. Use <button type=\"button\">.\n"
            . implode("\n", $offenders));
    }

    /* ── 2.4.7 Focus Visible, 2.5.5 Target Size, 2.3.3 Motion ─ */

    /** The CSS baseline every layout has to carry. */
    public function test_every_layout_includes_the_accessibility_baseline(): void
    {
        $missing = [];

        foreach (glob(resource_path('views/layouts/*.blade.php')) as $layout) {
            $src = file_get_contents($layout);

            // Only layouts that render a full document need it.
            if (! str_contains($src, '<html')) {
                continue;
            }

            if (! str_contains($src, "partials._a11y")) {
                $missing[] = basename($layout);
            }
        }

        $this->assertSame([], $missing,
            'Layouts without the accessibility baseline (focus rings, 44px targets, reduced motion): '
            . implode(', ', $missing));
    }

    public function test_the_baseline_covers_the_four_things_it_claims_to(): void
    {
        $css = file_get_contents(resource_path('views/partials/_a11y.blade.php'));

        $this->assertStringContainsString(':focus-visible', $css, 'no visible focus ring');
        $this->assertStringContainsString('prefers-reduced-motion', $css, 'motion preference ignored');
        $this->assertStringContainsString('44px', $css, 'no minimum touch target');
        $this->assertStringContainsString('.sr-only', $css, 'no screen-reader-only utility');
    }
}
