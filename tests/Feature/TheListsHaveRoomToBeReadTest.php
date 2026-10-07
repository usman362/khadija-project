<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * My Events and Proposals had both become unreadable, and for the same
 * reason: eight and seven columns sharing a width equally, so the column
 * carrying a sentence got the same room as the one carrying "0".
 *
 * On My Events the event name was crushed to a sliver and "Urgent: Awards &
 * Trophy Design and BBQ & Grill Catering" wrapped over six lines, standing
 * the row 170px tall. On Proposals the title was cut to twenty characters
 * and still wrapped, the date broke over three lines and the "Has not
 * confirmed a date" chip over two.
 *
 * A table at width:100% has nothing to overflow with, so the wrapper's
 * overflow-x could never engage: it simply squeezed instead. The minimum
 * width is what buys the room, and the side panel moves under the table
 * unless the window can hold both — 340px of panel beside a table needing
 * about 1120 comes to roughly 1760 once the menu and padding are counted.
 *
 * This asserts the CSS because that is where the fault was, in the same way
 * DashboardCalendarTest asserts the grid that cut Saturday off.
 */
class TheListsHaveRoomToBeReadTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    public static function lists(): array
    {
        return [
            'My Events' => ['/client/events', '.mg-table', '.mg-layout'],
            'Proposals' => ['/client/proposals', '.pr-table', '.pr-layout'],
        ];
    }

    /** Bookings is cards rather than a table, but the panel rule is the same. */
    public static function panels(): array
    {
        return [
            'My Events' => ['/client/events', '.mg-layout'],
            'Proposals' => ['/client/proposals', '.pr-layout'],
            'Bookings'  => ['/client/bookings', '.bk-layout'],
        ];
    }

    #[DataProvider('lists')]
    public function test_the_table_can_outgrow_its_box(string $path, string $table): void
    {
        $html = $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();

        $this->assertMatchesRegularExpression(
            '/' . preg_quote($table, '/') . '\s*\{[^}]*min-width:\s*(\d{4})px/',
            $html,
            $table . ' has no minimum width, so it will squeeze instead of scrolling.'
        );

        preg_match('/' . preg_quote($table, '/') . '\s*\{[^}]*min-width:\s*(\d{4})px/', $html, $m);

        $this->assertGreaterThanOrEqual(
            1000,
            (int) $m[1],
            'The minimum is too small to fit a name, its figures and its buttons.'
        );
    }

    #[DataProvider('panels')]
    public function test_the_side_panel_steps_aside_before_the_content_is_crushed(string $path, string $layout): void
    {
        $html = $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();

        /*
         * The exact figure differs per page — a booking card needs about 900
         * beside the panel, a table about 1120 — so what is asserted is that
         * the panel steps aside somewhere in the fifteen-hundreds or above,
         * not at 1100, where it was leaving a card 632px wide on an ordinary
         * 1280 screen.
         */
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: (1[5-9]\d\d|[2-9]\d{3})px\)\s*\{[^}]*' . preg_quote($layout, '/') . '\s*\{\s*grid-template-columns:\s*1fr/s',
            $html,
            'The panel still sits beside the content at widths that cannot hold both.'
        );
    }

    /**
     * The row of figures above the inbox.
     *
     * Two things were wrong with it. The action sat in the row as a sixth
     * column of its own — 210px wide, shorter than the tiles beside it,
     * aligned to nothing — and the figures landed at different heights along
     * the row, because a label that wraps to two lines pushes its own number
     * a line lower than the one next to it. The number is the thing being
     * compared across the row; it has to sit on one line.
     */
    public function test_the_inbox_figures_line_up_and_the_action_is_not_a_tile(): void
    {
        $html = $this->actingAs($this->client)->get('/client/messages')->assertSuccessful()->getContent();

        // The action is above the row now, where every other client page
        // keeps its one action.
        $this->assertStringContainsString('cm-top-bar', $html);
        $this->assertStringNotContainsString('class="cm-actions"', $html,
            'The action is back in the tile row as a column of its own.');

        /*
         * What keeps the figures level is the label being held to one line.
         * Pinning the caption to the bottom did it too, and left a hole
         * between every number and its caption — the tiles read as padded
         * out rather than filled. The label cannot wrap now, so the three
         * pieces sit together and the slack falls at the foot of the card.
         */
        $this->assertMatchesRegularExpression(
            '/\.cm-stat-h\s*\{[^}]*white-space:\s*nowrap/s',
            $html,
            'The label can wrap again, so a two-line one will push its figure below its neighbours.'
        );
        $this->assertMatchesRegularExpression(
            '/\.cm-stat-h\s*\{[^}]*min-height:/s',
            $html,
            'The label has no floor.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.cm-stat\s\.s\s*\{[^}]*margin-top:\s*auto/s',
            $html,
            'The caption is pinned to the bottom again, which puts a hole back under every figure.'
        );
    }

    /** A long event name survives to the page instead of being cut to nothing. */
    public function test_a_long_event_name_is_not_cut_to_a_stub(): void
    {
        $svc = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-room',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $e = Event::create([
            'title' => 'Urgent: Awards and Trophy Design and BBQ Grill Catering',
            'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'source' => 'user',
            'starts_at' => now()->addDays(20)->format('Y-m-d H:i:s'),
        ]);
        $e->categories()->sync([$svc->id]);

        $html = $this->actingAs($this->client)->get('/client/events')->assertSuccessful()->getContent();

        $this->assertStringContainsString($e->title, $html, 'My Events no longer shows the whole name.');
    }
}
