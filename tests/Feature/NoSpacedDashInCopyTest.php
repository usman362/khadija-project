<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * No " — " in anything a user reads.
 *
 * Ali, 2026-09-10: "project me text me yeh dash boht use horaha hai space k
 * saath yeh remove kro yeh dash nahi ana chaiye". 631 were replaced across the
 * client portal, the public site, the shared pages, the emails and the copy in
 * config — each by what the sentence needed: a comma, a full stop, a colon.
 *
 * Code comments keep theirs; nobody reads them on screen. Ali extended it to
 * the whole project the same day, professional, admin and influencer areas
 * and the seeders included. One lookup list is exempt: it must match rows
 * already in the database. Text a language model writes at runtime is put
 * through App\Support\PlainPunctuation before anyone sees it.
 */
class NoSpacedDashInCopyTest extends TestCase
{
    private const HELD = [
        // The rules that find the dash have to contain it.
        '#^app/Support/PlainPunctuation\.php$#',
        // Matched exactly against titles the demo seeders wrote. Lookup keys, not copy.
        '#^app/Console/Commands/InventoryDemoData\.php$#',
    ];

    private function held(string $path): bool
    {
        foreach (self::HELD as $re) {
            if (preg_match($re, $path)) {
                return true;
            }
        }

        return false;
    }

    private function rel(string $abs): string
    {
        return ltrim(str_replace(base_path(), '', $abs), '/');
    }

    public function test_no_view_shows_a_spaced_dash(): void
    {
        // Everything between comments. Blade, HTML, CSS and JS comments are
        // for developers and keep whatever punctuation they like.
        //
        // A "/*" counts as a comment only when no word, quote, = or / sits
        // right before it. accept="image/*" on a file input was read as the
        // start of a CSS comment, and everything up to the next "*/" — real
        // copy on the professional profile page — was skipped, by the fixer
        // and by this test alike.
        $comments = '/(\{\{--.*?--\}\}|<!--.*?-->|(?<![\w"\'=\/])\/\*.*?\*\/|(?m:^[ \t]*\/\/[^\n]*$)|(?<=[;{}),])[ \t]*\/\/[^\n]*)/s';
        $found = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $path = $this->rel($file->getPathname());
            if ($this->held($path)) {
                continue;
            }
            $parts = preg_split($comments, file_get_contents($file->getPathname()), -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($parts as $i => $part) {
                // Also the placeholder style, "— Choose your event —", which
                // starts with the dash and so has no space before it.
                if ($i % 2 === 0 && (preg_match('/.{0,40} —(\s|$).{0,30}/u', $part, $m)
                    || preg_match('/— [^<>"\n—]{1,70} —/u', $part, $m))) {
                    $found[] = $path . ': ' . trim(preg_replace('/\s+/', ' ', $m[0]));
                    break;
                }
            }
        }

        $this->assertSame([], $found, "A spaced dash is back in copy:\n" . implode("\n", $found));
    }

    public function test_no_string_in_the_code_carries_one(): void
    {
        $found = [];
        $files = array_merge(
            iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()))),
            array_map(fn ($p) => new \SplFileInfo($p), glob(config_path('*.php'))),
            // What the seeders write ends up on screen too.
            iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(database_path('seeders'))))
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = $this->rel($file->getPathname());
            if ($this->held($path)) {
                continue;
            }
            foreach (token_get_all(file_get_contents($file->getPathname())) as $t) {
                if (is_array($t) && in_array($t[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                    && preg_match('/ —(\s|$)/u', $t[1])) {
                    $found[] = $path . ':' . $t[2] . ' ' . mb_substr(trim($t[1]), 0, 80);
                }
            }
        }

        $this->assertSame([], $found, "A spaced dash is back in a string:\n" . implode("\n", $found));
    }
}
