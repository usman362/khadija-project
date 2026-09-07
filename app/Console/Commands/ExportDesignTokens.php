<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * The colour token table for the web designer (BRIEF, item 9).
 *
 * The Web Designer Brief promises the designer a table of every colour token
 * with its light and dark value, because that is how dark mode works here —
 * the layouts swap the token values and every page follows. Without it the
 * designer cannot spec dark mode at all.
 *
 * Generated rather than written out by hand. A pasted table is right on the
 * day it is pasted and wrong the first time somebody changes a colour, and the
 * designer would be working from it for three months. Run this and send what
 * it prints.
 *
 * Two things it reports that the brief did not know:
 *
 *  - The client portal has 44 tokens, not 42, and the light theme overrides
 *    only some of them. A token with no override is the SAME colour in both
 *    themes — that is a decision the designer needs, not a gap in the table.
 *  - The Client and Professional portals each carry their own palette. The
 *    Influencer portal and the public pages have tokens but no dark theme at
 *    all, so for those, light is the only answer.
 */
class ExportDesignTokens extends Command
{
    protected $signature = 'design:tokens
        {--csv : output as CSV rather than a table}
        {--portal= : just one portal (client, professional, influencer, landing)}';

    protected $description = 'Colour tokens with their light and dark values, for the design handoff';

    /** Layout file per portal, and whether it carries a dark theme. */
    private const PORTALS = [
        'client' => 'client',
        'professional' => 'professional',
        'influencer' => 'influencer-portal',
        'landing' => 'landing',
    ];

    public function handle(): int
    {
        $only = $this->option('portal');

        foreach (self::PORTALS as $portal => $layout) {
            if ($only && $only !== $portal) {
                continue;
            }

            $path = resource_path("views/layouts/{$layout}.blade.php");

            if (! is_file($path)) {
                continue;
            }

            $css = file_get_contents($path);
            $rows = $this->tokensFor($css);

            if ($rows === []) {
                continue;
            }

            // Whether this portal can switch theme at all, which is a different
            // fact from "every token happens to match". Without it the Influencer
            // portal reported "15 the same in both themes" — true of the values,
            // and quite wrong about the platform.
            $this->render($portal, $rows, str_contains($css, '[data-theme="light"]'));
        }

        return self::SUCCESS;
    }

    /**
     * Read the token declarations out of a layout.
     *
     * The base block is `:root` — which this codebase writes as
     * `:root, [data-theme="dark"]`, so the base values ARE the dark values.
     * `[data-theme="light"]` then overrides a subset. A token missing from the
     * light block is not missing data: it is the same colour in both themes,
     * and it is reported that way rather than left blank.
     *
     * @return array<int, array{token: string, light: string, dark: string, note: string}>
     */
    private function tokensFor(string $css): array
    {
        $dark = $this->declarations($css, ':root');
        $light = $this->declarations($css, '[data-theme="light"]');

        $rows = [];

        foreach ($dark as $token => $value) {
            // Colours only. The same blocks carry radii, shadows and timings,
            // and a designer handed "--radius-lg | 14px | 14px" would rightly
            // wonder what else in the table was not a colour.
            if (! $this->looksLikeColour($value) && ! $this->looksLikeColour($light[$token] ?? '')) {
                continue;
            }

            $hasLight = array_key_exists($token, $light);

            $rows[] = [
                'token' => $token,
                'light' => $hasLight ? $light[$token] : $value,
                'dark' => $value,
                'note' => ($hasLight || $light === []) ? '' : 'same in both',
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['token'], $b['token']));

        return $rows;
    }

    /**
     * Declarations inside the first rule whose selector contains $selector.
     *
     * Brace-counted rather than matched to the first `}`, because these blocks
     * contain nested media queries — stopping at the first closing brace reads
     * a fraction of the palette and looks like a complete answer.
     *
     * @return array<string, string>
     */
    private function declarations(string $css, string $selector): array
    {
        $at = strpos($css, $selector);

        if ($at === false) {
            return [];
        }

        $open = strpos($css, '{', $at);

        if ($open === false) {
            return [];
        }

        $depth = 0;
        $end = $open;

        for ($i = $open, $len = strlen($css); $i < $len; $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        $body = substr($css, $open, $end - $open);

        preg_match_all('/(--[a-z0-9-]+)\s*:\s*([^;]+);/i', $body, $m, PREG_SET_ORDER);

        $out = [];

        foreach ($m as [$_, $token, $value]) {
            $out[$token] = trim($value);
        }

        return $out;
    }

    private function looksLikeColour(string $value): bool
    {
        return (bool) preg_match('/^(#[0-9a-f]{3,8}|rgba?\(|hsla?\(|linear-gradient\()/i', trim($value));
    }

    private function render(string $portal, array $rows, bool $hasDarkTheme): void
    {
        if ($this->option('csv')) {
            $this->line("# {$portal}");
            $this->line('Token,Light,Dark,Note');

            foreach ($rows as $r) {
                $this->line(sprintf('"%s","%s","%s","%s"', $r['token'], $r['light'], $r['dark'], $r['note']));
            }

            $this->newLine();

            return;
        }

        $this->newLine();
        $this->info(ucfirst($portal).' — '.count($rows).' colour tokens');

        if (! $hasDarkTheme) {
            $this->line('  No dark theme on this portal — these are the only values,');
            $this->line('  and the designer should not produce dark screens for it.');
        } else {
            $same = count(array_filter($rows, fn ($r) => $r['note'] !== ''));
            $this->line("  {$same} of them are the same colour in both themes.");
        }

        $this->table(['Token', 'Light', 'Dark', ''], array_map('array_values', $rows));
    }
}
