<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Rule R29: nothing on GigResource may claim or imply that a machine does the
 * work. The three tool-package levels are Starter / Semi / Maximum — Peter
 * renamed "Help Me Plan" and "Coordinate It For Me" first, then "Do It Myself"
 * on 2026-07-21.
 *
 * The old wording came back on 12 professional tool cards because each tool
 * kept its own hardcoded copy of the labels rather than reading the config.
 * These tests fail if that happens again.
 */
class ToolLevelWordingTest extends TestCase
{
    private const RETIRED = ['Do It Myself', 'Help Me Plan', 'Coordinate It For Me'];

    public function test_the_levels_are_named_starter_semi_maximum(): void
    {
        $labels = config('ai-levels.labels');

        $this->assertSame('Starter', $labels['manual']);
        $this->assertSame('Semi', $labels['semi']);
        $this->assertSame('Maximum', $labels['maximum']);
    }

    public function test_no_retired_label_survives_in_config(): void
    {
        $text = json_encode([
            config('ai-levels.labels'),
            config('ai-levels.descriptions'),
        ]);

        foreach (self::RETIRED as $retired) {
            $this->assertStringNotContainsString($retired, $text);
        }
    }

    public function test_the_level_descriptions_do_not_say_a_machine_does_it(): void
    {
        foreach (config('ai-levels.descriptions') as $level => $description) {
            foreach (['AI', 'artificial intelligence', 'machine learning'] as $banned) {
                $this->assertStringNotContainsString(
                    $banned,
                    $description,
                    "the {$level} description claims AI, which R29 forbids",
                );
            }
        }
    }

    public function test_no_tool_page_carries_its_own_copy_of_the_old_labels(): void
    {
        $offenders = [];

        foreach ($this->toolViews() as $path) {
            $contents = file_get_contents($path);

            foreach (self::RETIRED as $retired) {
                if (str_contains($contents, $retired)) {
                    $offenders[] = basename($path) . " — {$retired}";
                }
            }
        }

        $this->assertEmpty($offenders, "retired level wording is back:\n" . implode("\n", $offenders));
    }

    /** @return list<string> */
    private function toolViews(): array
    {
        return array_merge(
            glob(resource_path('views/ai-tools/*.blade.php')) ?: [],
            glob(resource_path('views/client/ai-tools/*.blade.php')) ?: [],
        );
    }
}
