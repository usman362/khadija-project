<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Checklist rows 146 and 119 — one relative time, rounded rather than
 * truncated.
 *
 * Carbon reports the largest WHOLE unit, so an event 54 days old reads "1
 * month ago". It is not wrong — one month and twenty-four days — but a reader
 * takes "1 month" to mean about a month and is out by nearly four weeks. It
 * was spotted on a notification, but every relative time on the site had it.
 *
 * ->humanAgo() rounds to the nearest unit instead. The macro exists so the rule is
 * in one place: thirty-odd views called diffForHumans() directly, and a rule
 * spread over thirty call sites is a rule that will be missed in one.
 */
class RelativeTimeTest extends TestCase
{
    /** The exact case from the report: Jun 09 read on Aug 02. */
    public function test_fifty_four_days_reads_as_two_months_not_one(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00'));

        $this->assertSame('2 months ago', Carbon::parse('2026-06-09 12:00')->humanAgo());

        Carbon::setTestNow();
    }

    /** Just under the halfway point still rounds down. */
    public function test_forty_days_still_reads_as_one_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00'));

        $this->assertSame('1 month ago', Carbon::parse('2026-06-23 12:00')->humanAgo());

        Carbon::setTestNow();
    }

    public function test_the_ordinary_cases_are_unchanged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00'));

        $this->assertSame('2 hours ago', Carbon::parse('2026-08-02 10:00')->humanAgo());
        $this->assertSame('3 days ago', Carbon::parse('2026-07-30 12:00')->humanAgo());

        Carbon::setTestNow();
    }

    /** The absolute form drops "ago", the way the two callers that pass true expect. */
    public function test_the_absolute_form_omits_the_suffix(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00'));

        $this->assertSame('3 days', Carbon::parse('2026-07-30 12:00')->humanAgo(true));

        Carbon::setTestNow();
    }

    /**
     * No view should be reaching past the macro to the raw call — that is how
     * one screen comes to round differently from the one beside it.
     *
     * The two admin tables using the short form are allowed: "3d" is a
     * deliberate density choice on an operations screen, not a second answer
     * to the same question.
     */
    public function test_no_view_calls_the_unrounded_form(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $body = file_get_contents($file);

            if (preg_match_all('/->diffForHumans\((?!short:)/', $body, $m) > 0) {
                $offenders[] = str_replace(base_path() . '/', '', $file);
            }
        }

        $this->assertSame([], $offenders, 'these views bypass ->humanAgo(): ' . implode(', ', $offenders));
    }

    /** @return string[] */
    private function bladeFiles(): array
    {
        $files = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($it as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        // Guards against the scan silently matching nothing, which would make
        // the assertion above pass for the wrong reason.
        $this->assertGreaterThan(100, count($files));

        return $files;
    }
}
