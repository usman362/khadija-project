<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * BRIEF item 9 — the colour token table promised to the web designer.
 *
 * The brief tells the designer they will receive every token with its light
 * and dark value, because that is how dark mode works here: the layout swaps
 * the token values and every page follows. Without the table they cannot spec
 * dark mode at all.
 *
 * It is generated, not typed out. A pasted table is correct on the day it is
 * pasted and wrong the first time somebody changes a colour — and the designer
 * would be working from it for three months.
 *
 * What is asserted is what would mislead the designer if it were wrong: that
 * the table is complete, that no non-colour is smuggled into a colour table,
 * and that a portal with no dark theme says so rather than reporting its light
 * values twice.
 */
class DesignTokenExportTest extends TestCase
{
    private function csv(string $portal): array
    {
        // Artisan::call, not $this->artisan(): the latter returns a pending
        // command and Artisan::output() is still empty when you read it, so
        // every assertion below quietly passed against nothing.
        \Illuminate\Support\Facades\Artisan::call('design:tokens', [
            '--csv' => true,
            '--portal' => $portal,
        ]);

        $out = \Illuminate\Support\Facades\Artisan::output();
        $rows = [];

        foreach (explode("\n", $out) as $line) {
            if (preg_match('/^"(--[^"]+)","([^"]*)","([^"]*)","([^"]*)"$/', trim($line), $m)) {
                $rows[$m[1]] = ['light' => $m[2], 'dark' => $m[3], 'note' => $m[4]];
            }
        }

        return $rows;
    }

    public function test_the_client_table_covers_the_whole_palette(): void
    {
        $rows = $this->csv('client');

        // Every token the layout's own light theme overrides must appear. This
        // is the check that catches a parser stopping early — the token blocks
        // contain nested media queries, and reading to the first closing brace
        // returns a fraction of the palette while looking complete.
        $this->assertGreaterThan(25, count($rows), 'the table looks truncated');

        foreach (['--brand', '--bg-primary', '--text-primary', '--border-color'] as $token) {
            $this->assertArrayHasKey($token, $rows, "{$token} is missing from the table");
        }
    }

    /** Light and dark must actually differ somewhere, or the swap is a fiction. */
    public function test_the_two_themes_are_not_the_same_table(): void
    {
        $rows = $this->csv('client');

        $differing = array_filter($rows, fn ($r) => $r['light'] !== $r['dark']);

        $this->assertGreaterThan(10, count($differing));
    }

    /** Only colours. A radius or a timing in a colour table is a trap. */
    public function test_nothing_but_colours_is_listed(): void
    {
        foreach ($this->csv('client') as $token => $r) {
            $this->assertMatchesRegularExpression(
                '/^(#[0-9a-f]{3,8}|rgba?\(|hsla?\(|linear-gradient\()/i',
                $r['light'],
                "{$token} is not a colour",
            );
        }
    }

    /**
     * The Influencer portal has tokens but no dark theme. It must not report
     * its light values as though they were also its dark ones — that is the
     * shape of answer that gets a designer to produce dark screens for a
     * portal that has no dark mode.
     */
    public function test_a_portal_with_no_dark_theme_says_so(): void
    {
        \Illuminate\Support\Facades\Artisan::call('design:tokens', ['--portal' => 'influencer']);

        $this->assertStringContainsString(
            'No dark theme on this portal',
            \Illuminate\Support\Facades\Artisan::output(),
        );
    }
}
