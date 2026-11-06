<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * An HTML entity inside {{ }} reaches the page as its own characters.
 *
 * Blade escapes what it prints, so the ampersand in "&middot;" becomes
 * "&amp;middot;" and the reader sees the word, not the dot. It is invisible in
 * the source and obvious on screen, and it has now happened twice on the same
 * two pages:
 *
 *   "&minus; $375.00"                      on Payments & Payouts
 *   "Fundraiser &middot; Trade Show Booth" on the same table, a day later
 *
 * Both were written as a string concatenated inside {{ }}, where the entity
 * looks right in the editor. The fix either way is the character itself, or
 * putting the entity outside the braces.
 *
 * This walks the Blade files rather than rendering them, because the mistake
 * is in the source and a page only shows it on a row with the right data.
 */
class NoHtmlEntityIsPrintedThroughBladeEscapingTest extends TestCase
{
    /** Entities that are worth writing, and so worth getting wrong. */
    private const ENTITIES = [
        'minus', 'middot', 'ndash', 'mdash', 'times', 'hellip', 'rarr', 'larr',
        'nbsp', 'bull', 'deg', 'copy', 'pound', 'euro', 'laquo', 'raquo',
    ];

    public function test_no_blade_file_prints_an_entity_through_the_escaping_braces(): void
    {
        $names  = implode('|', self::ENTITIES);
        $offend = [];

        foreach ($this->bladeFiles() as $file) {
            $source = file_get_contents($file);

            // Every {{ ... }}, non-greedy, across lines. {!! !!} is fine: that
            // one does not escape, which is exactly the point.
            preg_match_all('/\{\{(?!--)(.*?)\}\}/s', $source, $matches);

            foreach ($matches[1] as $printed) {
                if (preg_match('/&(' . $names . ');/', $printed, $hit)) {
                    $offend[] = str_replace(base_path() . '/', '', $file)
                        . ': ' . trim(preg_replace('/\s+/', ' ', $printed));
                }
            }
        }

        $this->assertSame([], $offend, implode("\n", array_merge(
            ['An HTML entity inside {{ }} is printed as text, not as the character:'],
            $offend,
            ['', 'Write the character itself, or move the entity outside the braces.'],
        )));
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
