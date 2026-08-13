<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A control that does nothing.
 *
 * These keep coming back one at a time — row 122 fixed one link in a panel and
 * left the two beside it; a button was removed from confirmed.blade.php in
 * August and its twin two files over was not. So the rule is asserted rather
 * than re-checked by eye each time.
 *
 * THE RULE: an href="#" is allowed only where JavaScript actually takes the
 * click. In practice that means the element carries an id, an onclick, or a
 * data-bs-toggle. A bare href="#" with neither is a control that scrolls the
 * page to the top and calls it a feature.
 *
 * This is deliberately a markup rule and not a promise that the JS is correct.
 * It cannot be: only a browser knows that. What it can do is stop the
 * particular regression that has happened four times.
 */
class NoDeadControlsTest extends TestCase
{
    public function test_no_view_carries_a_link_that_goes_nowhere(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            // Blade comments describe removed controls on purpose — several of
            // the notes left behind quote the href they replaced.
            $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));

            foreach (explode("\n", $src) as $i => $line) {
                if (! str_contains($line, 'href="#"')) {
                    continue;
                }

                $wired = str_contains($line, 'id=')
                    || str_contains($line, 'onclick=')
                    || str_contains($line, 'data-bs-toggle=');

                if (! $wired) {
                    $offenders[] = str_replace(resource_path('views/'), '', $path) . ':' . ($i + 1)
                        . '  ' . trim(mb_substr($line, 0, 90));
                }
            }
        }

        $this->assertSame([], $offenders, "these controls take a click and do nothing:\n" . implode("\n", $offenders));
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
