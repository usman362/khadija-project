<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The 6 October package named one pattern its highest priority: a summary
 * tile contradicting the detail beneath it, found on four pages in one round.
 * Its advice was to trace it to a shared cause rather than patch tile by
 * tile. Those four were on a different build, so this went looking for the
 * same shape here.
 *
 * It was on Proposals. Each pipeline tile was defined twice — once to count
 * it, once to filter to it — and the two copies had drifted: In Progress
 * counted won proposals whose event is running right now, while clicking it
 * filtered on "won" alone. The tile could read 0 and open a list of seven.
 *
 * There is one definition per tile now, and this is what holds it there: for
 * every tile, the number on it is the number of rows you get when you press
 * it.
 */
class ATileAndItsListAgreeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->service = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-tiles',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    private function event(array $attrs = []): Event
    {
        $e = Event::create(array_merge([
            'title' => 'Beach Party', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'source' => 'user',
            'starts_at' => now()->addDays(20)->setTime(17, 0),
        ], $attrs));
        $e->categories()->sync([$this->service->id]);

        return $e;
    }

    private function bid(Event $e, string $status, string $name): Bid
    {
        $pro = User::factory()->create(['name' => $name, 'primary_role' => 'professional']);
        $pro->assignRole('professional');

        return Bid::create([
            'event_id' => $e->id, 'category_id' => $this->service->id, 'supplier_id' => $pro->id,
            'amount' => 1000, 'status' => $status, 'available_confirmed' => true,
        ]);
    }

    /**
     * The spread that caught it: a won proposal on an event happening now, a
     * won proposal on one still ahead, and a won proposal on one finished.
     * Only the first belongs under In Progress.
     */
    private function spread(): void
    {
        $running = $this->event(['title' => 'Running Now',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $ahead = $this->event(['title' => 'Still Ahead',
            'starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHours(5)]);
        $done = $this->event(['title' => 'All Over', 'status' => 'completed',
            'starts_at' => now()->subDays(30), 'ends_at' => now()->subDays(30)->addHours(5)]);

        $this->bid($running, 'won', 'Running Pro');
        $this->bid($ahead, 'won', 'Ahead Pro');
        $this->bid($done, 'won', 'Done Pro');
        $this->bid($ahead, 'submitted', 'Waiting Pro');
        $this->bid($ahead, 'rejected', 'Turned Down Pro');
    }

    /** Every tile, against the list it opens. */
    public function test_each_pipeline_tile_counts_what_pressing_it_shows(): void
    {
        $this->spread();

        foreach (['submitted', 'pending', 'accepted', 'in_progress', 'completed', 'declined'] as $tab) {
            $onTile = $this->tileCount($tab);
            $inList = $this->rowCount($tab);

            $this->assertSame(
                $onTile,
                $inList,
                "The {$tab} tile says {$onTile} and opens a list of {$inList}."
            );
        }
    }

    /** The one that had drifted, named on its own so a failure reads plainly. */
    public function test_in_progress_means_the_event_is_running_on_both(): void
    {
        $this->spread();

        $this->assertSame(1, $this->tileCount('in_progress'), 'Only one event is actually running.');

        // The rows only. Every one of this client's events also appears in
        // the page's own event filter, which is not what this is about.
        $rows = $this->rowsOf($this->tab('in_progress'));

        $this->assertStringContainsString('Running Now', $rows);
        $this->assertStringNotContainsString('Still Ahead', $rows, 'A won proposal on a future event is not in progress.');
        $this->assertStringNotContainsString('All Over', $rows);
    }

    /**
     * Bookings' money panel is the other half of the same pattern: a figure
     * over a list that does not add up to it, because the list is filtered
     * and the figure is not. The page has to say which.
     */
    public function test_the_bookings_money_panel_names_its_own_scope(): void
    {
        $e = $this->event();
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        Booking::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 1000,
        ]);

        $html = $this->actingAs($this->client)->get('/client/bookings')->assertSuccessful()->getContent();

        $this->assertMatchesRegularExpression('/Across \d+ bookings?/', $html);
    }

    /** The proposal table, without the toolbar and the rail around it. */
    private function rowsOf(string $html): string
    {
        $start = strpos($html, '<tbody');
        $this->assertNotFalse($start, 'No proposal table on the page.');

        $end = strpos($html, '</tbody>', $start);

        return substr($html, $start, $end - $start);
    }

    private function tab(string $tab): string
    {
        $url = '/client/proposals' . ($tab === 'submitted' ? '' : '?tab=' . $tab);

        return $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();
    }

    /** The figure printed on the tile itself. */
    private function tileCount(string $tab): int
    {
        $html = $this->tab('submitted');

        $label = ['submitted' => 'Submitted', 'pending' => 'Pending', 'accepted' => 'Accepted',
            'in_progress' => 'In Progress', 'completed' => 'Completed', 'declined' => 'Declined'][$tab];

        $this->assertMatchesRegularExpression(
            '/' . preg_quote($label, '/') . '\s*<\/div>\s*<div[^>]*>\s*(\d+)/s',
            $html,
            'No ' . $label . ' tile on the page.'
        );

        preg_match('/' . preg_quote($label, '/') . '\s*<\/div>\s*<div[^>]*>\s*(\d+)/s', $html, $m);

        return (int) $m[1];
    }

    /** How many proposal rows that tab actually lists. */
    private function rowCount(string $tab): int
    {
        return substr_count($this->tab($tab), 'class="pr-prop"');
    }
}
