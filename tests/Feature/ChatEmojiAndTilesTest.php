<?php

namespace Tests\Feature;

use App\Support\EmojiCatalog;
use Tests\TestCase;

/**
 * Two things Ali reported alongside the attachments.
 *
 *  - The emoji picker held ten characters, all of them event-planning
 *    shorthand. People write to each other in these threads.
 *  - The Bookings status tiles wrapped: "Awaiting Completion" was added as a
 *    seventh tile and the grid was left at `repeat(6, ...)`, so Cancelled sat
 *    alone on a second line. The tiles are the status filter, and a filter
 *    that looks like two rows reads as two groups.
 */
class ChatEmojiAndTilesTest extends TestCase
{
    public function test_the_emoji_set_is_a_picker_not_a_toolbar(): void
    {
        $all = EmojiCatalog::all();

        $this->assertGreaterThan(120, count($all), 'The catalogue is back to being a handful.');
        $this->assertGreaterThanOrEqual(4, count(EmojiCatalog::groups()));
    }

    /** Search filters on the NAME — searching the character finds nothing. */
    public function test_every_emoji_carries_searchable_words(): void
    {
        foreach (EmojiCatalog::all() as $char => $name) {
            $this->assertNotSame('', trim($name), "{$char} has no name to search on.");
        }
    }

    /** The words people actually type have to find something. */
    public function test_the_obvious_searches_return_something(): void
    {
        $all = EmojiCatalog::all();

        foreach (['party', 'thanks', 'yes', 'no', 'money', 'calendar', 'photo', 'music', 'happy', 'sad'] as $term) {
            $hits = array_filter($all, fn ($name) => str_contains($name, $term));

            $this->assertNotEmpty($hits, "Searching \"{$term}\" finds no emoji.");
        }
    }

    public function test_no_emoji_is_listed_twice_in_one_group(): void
    {
        foreach (EmojiCatalog::groups() as $key => $group) {
            $this->assertSame(
                count($group['emoji']),
                count(array_unique(array_keys($group['emoji']))),
                "Group {$key} repeats an emoji.",
            );
        }
    }

    /** Seven tiles need seven columns. */
    public function test_the_booking_tiles_fit_on_one_row(): void
    {
        $view = file_get_contents(resource_path('views/client/bookings/index.blade.php'));

        // Count the tiles the page declares.
        preg_match('/\$tiles = \[(.*?)\n            \];/s', $view, $m);
        $this->assertNotEmpty($m, 'Could not find the $tiles array.');
        $tiles = preg_match_all("/^\s*\['/m", $m[1]);

        preg_match('/\.bk-stats \{[^}]*repeat\((\d+),/', $view, $c);
        $this->assertNotEmpty($c, 'No column count on .bk-stats.');

        $this->assertSame(
            $tiles,
            (int) $c[1],
            "The page declares {$tiles} status tiles but the grid is {$c[1]} columns wide, so they wrap.",
        );
    }
}
