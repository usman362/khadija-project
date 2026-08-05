<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Rule R29: nothing on GigResource may claim or imply that a machine does the
 * work. Peter settled the naming on 2026-08-05: "Manual / Semi / Maximum are
 * now the ONLY toolkit-tier labels, for both Client and Professional."
 *
 * That retires three earlier sets at once — the R29-violating "Do It Myself /
 * Help Me Plan / Coordinate It For Me", the client-facing "Basic / Plus /
 * Premium", and the 2026-07-21 amendment that had briefly renamed the base
 * level "Starter". Starter is a membership name, not a tier label.
 *
 * The old wording came back on 12 professional tool cards because each tool
 * kept its own hardcoded copy of the labels rather than reading the config.
 * These tests fail if that happens again.
 */
class ToolLevelWordingTest extends TestCase
{
    private const RETIRED = ['Do It Myself', 'Help Me Plan', 'Coordinate It For Me'];

    /** Also retired, but only as tier labels — both are live words elsewhere. */
    private const RETIRED_AS_TIER_LABELS = ['Starter', 'Basic', 'Plus', 'Premium'];

    public function test_the_levels_are_named_manual_semi_maximum(): void
    {
        $labels = config('ai-levels.labels');

        $this->assertSame('Manual', $labels['manual']);
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

    public function test_no_earlier_naming_scheme_is_used_as_a_tier_label(): void
    {
        $labels = array_values(config('ai-levels.labels'));

        foreach (self::RETIRED_AS_TIER_LABELS as $retired) {
            $this->assertNotContains($retired, $labels, "'{$retired}' is retired as a tier label");
        }

        $this->assertSame(
            ['Manual', 'Semi', 'Maximum'],
            array_values(config('toolkit-tiers.tiers')),
            'the toolkit tab table must use the same three names',
        );
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
