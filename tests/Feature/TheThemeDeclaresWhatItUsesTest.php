<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A CSS variable that was never declared does not fail; it falls back.
 *
 * That is why the client's dark mode had been broken in plain sight. The
 * searchable select — on every request form and every filter row — was
 * written correctly as `background: var(--bg-page, #fff)`, but nothing
 * anywhere declared --bg-page. So it was #fff in both themes, for ever, and
 * reading the stylesheet told you nothing was wrong. Post an Event had the
 * same fault twice over: its headings used var(--pe-text, #1f2937) against a
 * palette whose name is --pe-ink, which on the dark shell left them at
 * 1.2:1 — present, correct-looking, invisible.
 *
 * So: a shell colour used in a client view must be declared in the client
 * layout, where both themes define it. The fallback stays as what it is
 * meant to be, a last resort, rather than the value everybody gets.
 *
 * Only shell names are checked. A view is free to invent a palette of its
 * own (--pe-*, --tk-*, --pres) so long as it declares it, which is why a
 * name declared in the same file passes.
 */
class TheThemeDeclaresWhatItUsesTest extends TestCase
{
    /** The families the layout owns: surfaces, ink, borders, the accents. */
    private const SHELL = '/^--(bg-|text-|border|brand|accent-|ok-text|warn-text|bad-text|info-text|danger-text)/';

    public function test_every_shell_colour_a_client_view_uses_is_declared(): void
    {
        $declared = $this->declaredIn(resource_path('views/layouts/client.blade.php'));

        $this->assertContains('--bg-page', $declared, 'The layout stopped declaring --bg-page.');

        $offenders = [];

        foreach ($this->clientViews() as $file) {
            $local = $this->declaredIn($file);

            preg_match_all('/var\(\s*(--[a-z][a-z0-9-]*)/', file_get_contents($file), $m);

            foreach (array_unique($m[1]) as $name) {
                if (! preg_match(self::SHELL, $name)) {
                    continue;
                }
                if (in_array($name, $declared, true) || in_array($name, $local, true)) {
                    continue;
                }
                $offenders[] = $name . '  in ' . str_replace(resource_path('views/'), '', $file);
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), implode("\n", array_merge(
            ['These names are used but declared nowhere, so both themes silently get the fallback:'],
            array_unique($offenders),
        )));
    }

    /**
     * Both themes have to answer, not just one. A name declared only in the
     * dark block leaves the light theme on the fallback, which is the same
     * fault the other way round.
     */
    public function test_each_theme_declares_the_same_names(): void
    {
        $css = file_get_contents(resource_path('views/layouts/client.blade.php'));

        $dark = $this->block($css, ':root, [data-theme="dark"] {');
        $light = $this->block($css, '[data-theme="light"] {');

        $surfaces = fn (array $names) => array_values(array_filter(
            $names,
            fn ($n) => (bool) preg_match('/^--(bg-|text-|border|brand-text|ok-text|warn-text|bad-text|info-text|danger-text)/', $n)
        ));

        $onlyDark = array_diff($surfaces($dark), $light);

        $this->assertSame([], array_values($onlyDark), 'Declared for the dark theme only: ' . implode(', ', $onlyDark));
    }

    /** @return list<string> */
    private function declaredIn(string $file): array
    {
        preg_match_all('/(--[a-z][a-z0-9-]*)\s*:/', file_get_contents($file), $m);

        return array_values(array_unique($m[1]));
    }

    /** The names declared inside one rule block. */
    private function block(string $css, string $opener): array
    {
        $start = strpos($css, $opener);
        $this->assertNotFalse($start, 'The layout no longer has the block "' . $opener . '".');

        $end = strpos($css, "\n        }", $start);
        $body = substr($css, $start, $end - $start);

        preg_match_all('/(--[a-z][a-z0-9-]*)\s*:/', $body, $m);

        return array_values(array_unique($m[1]));
    }

    /** @return list<string> */
    private function clientViews(): array
    {
        $files = [resource_path('views/partials/_searchable_select.blade.php')];

        foreach (['views/client', 'views/components'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path($dir)));
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                    $files[] = $f->getPathname();
                }
            }
        }

        return $files;
    }
}
