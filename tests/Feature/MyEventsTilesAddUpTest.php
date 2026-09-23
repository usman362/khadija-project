<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Events, the two arithmetic faults Sir Peter found on 23 September.
 *
 * OA-170: "Total 17 = Open 4 + In Progress 0 + Completed 0 + Past 11 = 15.
 *          The 2 Drafts are missing, so the row does not add up."
 * OA-171: "Open 24% + Past 65% + Draft 12% = 101%."
 *
 * Both are the same kind of mistake — a figure that invites being checked and
 * then fails the check — so both are built exactly as he counted them.
 */
class MyEventsTilesAddUpTest extends TestCase
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

        // His counts: 4 open, 11 past, 2 drafts. 17 in all.
        for ($i = 0; $i < 4; $i++) {
            $this->event(['starts_at' => now()->addDays(10 + $i)]);
        }
        for ($i = 0; $i < 11; $i++) {
            $this->event(['starts_at' => now()->subDays(10 + $i)]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->event(['status' => 'draft', 'is_published' => false, 'starts_at' => now()->addDays(40 + $i)]);
        }
    }

    private function event(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title' => 'Garden Reception', 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'budget' => 1500,
        ]);
    }

    private function page(): string
    {
        return $this->actingAs($this->client)->get(route('client.events.index'))->assertOk()->getContent();
    }

    public function test_the_tiles_reconcile_with_total_events(): void
    {
        $stats = $this->actingAs($this->client)->get(route('client.events.index'))->viewData('stats');

        $this->assertSame(17, $stats['total']);
        $this->assertSame(
            $stats['total'],
            $stats['list_open'] + $stats['list_in_progress'] + $stats['list_completed']
                + $stats['list_past'] + $stats['list_draft'] + $stats['list_cancelled'],
            'The stages have to account for every event the Total counts.'
        );
    }

    public function test_total_events_names_what_no_tile_shows(): void
    {
        // The four tiles beside it come to 15; the caption says where the
        // other two are rather than leaving the client to find them.
        $this->assertStringContainsString('All time, incl. 2 drafts', $this->page());
    }

    public function test_the_donut_shares_total_one_hundred(): void
    {
        preg_match_all('/\((\d+)%\)/', $this->page(), $matches);

        $this->assertNotEmpty($matches[1], 'The legend prints a share for every stage.');
        $this->assertSame(100, array_sum(array_map('intval', $matches[1])));
    }
}
