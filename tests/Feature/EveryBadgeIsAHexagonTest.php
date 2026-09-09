<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * One badge shape on the site.
 *
 * Sir Peter, 2026-09-09: "we are now and only using the hexagon style badges
 * across the users, but whatever colors, shades, icons within them is
 * Khadijah's decision."
 *
 * So this reads the views themselves. A rendering test can only prove the
 * pages it happens to visit; the thing being promised is that no OTHER badge
 * style exists anywhere — which is a claim about the source.
 */
class EveryBadgeIsAHexagonTest extends TestCase
{
    /** The pill and tag styles the badges used to be drawn as are gone. */
    public function test_no_badge_is_drawn_as_a_pill_any_more(): void
    {
        $gone = [
            'resources/views/public/browse.blade.php'            => ['class="br-chip verif"', 'class="br-chip top"'],
            'resources/views/public/professional/show.blade.php' => ['class="pp-tag top"', 'class="pp-tag verified"'],
            'resources/views/client/dashboard.blade.php'         => ['class="od-badge"'],
        ];

        foreach ($gone as $file => $markers) {
            $src = file_get_contents(base_path($file));

            foreach ($markers as $marker) {
                $this->assertStringNotContainsString($marker, $src,
                    "{$file} still draws a badge as a pill: {$marker}");
            }
        }
    }

    /** And those places use the one component that owns the shape. */
    public function test_they_use_the_hexagon_component(): void
    {
        foreach ([
            'resources/views/public/browse.blade.php',
            'resources/views/public/professional/show.blade.php',
            'resources/views/client/dashboard.blade.php',
        ] as $file) {
            $this->assertStringContainsString('<x-hex-badge', file_get_contents(base_path($file)),
                "{$file} draws a badge without the component that fixes the shape.");
        }
    }

    /**
     * The shape is written once. A second polygon somewhere would be a second
     * opinion about what a hexagon is on this site.
     */
    public function test_the_shape_is_defined_in_one_place(): void
    {
        $others = [];

        foreach ($this->bladeFiles() as $file) {
            if (str_ends_with($file, 'partials/_hex_shape.blade.php')) {
                continue;
            }

            if (str_contains(file_get_contents($file), 'polygon(50% 0%')) {
                $others[] = $file;
            }
        }

        $this->assertSame([], $others, 'The hexagon is drawn outside partials/_hex_shape: ' . implode(', ', $others));
    }

    /**
     * It has to be an actual hexagon, not a crest.
     *
     * The component shipped with 14%/62%, which pulls the bottom vertex into a
     * long spike -- a shield. Sir Peter saw it beside the hexagons he wanted on
     * 2026-09-10: "and not these style". Asserting the literal polygon string
     * would have passed the whole time, because the string was the bug.
     *
     * A hexagon's two waist vertices sit the same distance from the top and the
     * bottom, so their percentages add up to 100. A shield's do not.
     */
    public function test_the_polygon_is_a_hexagon_and_not_a_shield(): void
    {
        $component = file_get_contents(resource_path('views/partials/_hex_shape.blade.php'));

        $this->assertTrue(
            (bool) preg_match('/clip-path:\s*polygon\(([^)]+)\)/', $component, $m),
            'The hexagon partial draws no polygon at all.',
        );

        preg_match_all('/(\d+(?:\.\d+)?)%?\s+(\d+(?:\.\d+)?)%/', $m[1], $points, PREG_SET_ORDER);

        $this->assertCount(6, $points, 'A hexagon has six corners.');

        $ys = array_map(fn ($p) => (float) $p[2], $points);
        sort($ys);

        // Top point at 0, bottom point at 100, and the four waist corners in
        // two mirrored pairs.
        $this->assertSame(0.0, $ys[0], 'The top point is not at the top.');
        $this->assertSame(100.0, $ys[5], 'The bottom point is not at the bottom.');
        $this->assertSame(100.0, $ys[1] + $ys[4], 'The waist is not centred -- this is a crest, not a hexagon.');
        $this->assertSame(100.0, $ys[2] + $ys[3], 'The waist is not centred -- this is a crest, not a hexagon.');
    }

    /** @return array<int, string> */
    private function bladeFiles(): array
    {
        $files = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** Colours are settings, because they are not ours to choose. */
    public function test_the_colours_are_configurable(): void
    {
        $this->assertNotEmpty(config('badges.verified_colour'));
        $this->assertNotEmpty(config('badges.top_rated_colour'));

        foreach (config('badges.client') as $badge) {
            $this->assertArrayHasKey('colour', $badge, "The {$badge['key']} badge has no colour to change.");
            $this->assertArrayHasKey('icon', $badge);
        }
    }
}
