<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 7 October: "duplicated for the same purpose, can you remove the
 * image 2 on the webpage but then make all the cards with the weblinks since
 * image 2 is no longer being used in the webpage."
 *
 * The row of tabs under the stat cards said the same six words with the same
 * six numbers. One of them was the filter and the other was decoration, and
 * nothing on the page said which.
 *
 * The cards are the filter now.
 */
class StatCardsAreTheFilterTest extends TestCase
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
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    /** Every card leads somewhere. */
    public function test_each_card_filters_the_list(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.proposals.index'))->assertOk();

        foreach (['all', 'pending', 'accepted', 'in_progress', 'completed', 'declined'] as $tab) {
            $page->assertSee(route('client.proposals.index', ['tab' => $tab]), false);
        }
    }

    /** And the duplicate row is gone with its styles. */
    public function test_the_tab_row_is_gone(): void
    {
        $markup = file_get_contents(base_path('resources/views/client/proposals/index.blade.php'));

        $this->assertStringNotContainsString('All Proposals', $markup,
            'The pipeline tabs are back, saying what the cards already say.');
        $this->assertStringNotContainsString('class="pr-tab ', $markup);
    }

    /** The card you are looking at says so. */
    public function test_the_open_filter_is_marked(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.proposals.index', ['tab' => 'accepted']))
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }

    /**
     * My Events got the same treatment on the same day, and had the worse
     * version of the fault: a second set of tiles inside Details View
     * counting the same events again.
     */
    public function test_my_events_tiles_filter_and_do_not_repeat_themselves(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.events.index'))->assertOk();

        foreach (['open', 'in_progress', 'completed', 'past'] as $status) {
            $page->assertSee('status=' . $status, false);
        }

        $markup = file_get_contents(base_path('resources/views/client/events/index.blade.php'));

        $this->assertStringNotContainsString('cl-stat-label">Total Events', $markup,
            'Details View counts the events a second time again.');
        $this->assertStringNotContainsString('cl-stat-label">Open Events', $markup);
        $this->assertStringContainsString('cl-stat-label">Total Budget', $markup,
            'Total Budget was the one figure Details View owned; it should stay.');
    }

    /** The numbers on a filter must not move when you press it. */
    public function test_the_tiles_count_everything_whatever_is_filtered(): void
    {
        $all = $this->actingAs($this->client)->get(route('client.events.index'))->getContent();
        $one = $this->actingAs($this->client)->get(route('client.events.index', ['status' => 'past']))->getContent();

        preg_match_all('/mg-stat-value">(\d+)</', $all, $a);
        preg_match_all('/mg-stat-value">(\d+)</', $one, $b);

        $this->assertSame($a[1], $b[1],
            'Filtering changed the numbers on the filter, so pressing one tile renumbers the rest.');
    }
}
