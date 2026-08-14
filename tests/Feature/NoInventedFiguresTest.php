<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Numbers nobody measured.
 *
 * The Owner's Budget-Does-Not-Equal-Funding rule says planning values must
 * never be shown as actual funds. What the sweep behind this test found was a
 * step worse: figures that were not planning values either, drawn at render
 * time and presented as fact.
 *
 * They were spread across four client screens — a random payment amount, a
 * random hourly rate for a named professional, a random proposal total, a
 * random "health score", a random "Friction Score", and a claim that a named
 * professional "has maintained 95–100% contract compliance across 8–20
 * completed events". Every one of them changed when the page was refreshed.
 *
 * THE RULE: a view may not call rand(). A figure on screen either comes from a
 * record or it is a dash. This is narrow on purpose — it cannot judge whether
 * a computed number is meaningful, but it does catch the specific habit that
 * produced all of the above.
 */
class NoInventedFiguresTest extends TestCase
{
    public function test_no_view_invents_a_number_at_render_time(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            // Comments are where the removals are explained, and several quote
            // the call they replaced.
            $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));
            $src = preg_replace('#/\*.*?\*/#s', '', $src);

            if (preg_match('/\brand\s*\(/', $src)) {
                $offenders[] = str_replace(resource_path('views/'), '', $path);
            }
        }

        $this->assertSame([], $offenders, "these views make up a number each time they render:\n" . implode("\n", $offenders));
    }

    /**
     * The two claims that were about a named person rather than a figure: a
     * tax form the platform cannot collect, and a compliance rate nothing
     * measures.
     */
    public function test_no_screen_makes_a_compliance_claim_about_a_professional(): void
    {
        $client = resource_path('views/client');

        foreach (['contract compliance', 'Push W-9 Reminder', 'IRS 1099 Liability'] as $claim) {
            $hits = [];

            foreach ($this->bladeFiles($client) as $path) {
                $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));
                $src = preg_replace('#/\*.*?\*/#s', '', $src);

                if (str_contains($src, $claim)) {
                    $hits[] = str_replace(resource_path('views/'), '', $path);
                }
            }

            $this->assertSame([], $hits, "\"{$claim}\" is stated where nothing measures it: " . implode(', ', $hits));
        }
    }

    /** @return list<string> */
    private function bladeFiles(?string $root = null): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root ?? resource_path('views')));

        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
