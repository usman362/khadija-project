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
 * Code comments keep theirs; nobody reads them on screen. The professional,
 * admin and influencer areas are left until Ali says so (client-side-only
 * rule), and so is one lookup list that must match rows already in the
 * database. Everything else is checked here so the dash cannot creep back.
 */
class NoSpacedDashInCopyTest extends TestCase
{
    private const HELD = [
        '#^resources/views/professional/#', '#^resources/views/layouts/professional#',
        '#^resources/views/influencer/#',   '#^resources/views/layouts/influencer#', '#^resources/views/emails/influencer/#',
        '#^resources/views/dashboard/admin/#',
        '#^app/Http/Controllers/Professional/#', '#^app/Http/Controllers/Influencer/#',
        '#^app/Http/Controllers/Dashboard/Admin#', '#^app/Http/Controllers/Admin/#',
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
        $comments = '/(\{\{--.*?--\}\}|<!--.*?-->|\/\*.*?\*\/|(?m:^[ \t]*\/\/[^\n]*$)|(?<=[;{}),])[ \t]*\/\/[^\n]*)/s';
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
            array_map(fn ($p) => new \SplFileInfo($p), glob(config_path('*.php')))
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
