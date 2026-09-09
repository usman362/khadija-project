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
        $component = file_get_contents(resource_path('views/components/hex-badge.blade.php'));

        $this->assertStringContainsString(
            'polygon(50% 0%, 100% 14%, 100% 62%, 50% 100%, 0 62%, 0 14%)',
            $component,
        );
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
