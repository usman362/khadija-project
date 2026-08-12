<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 106, 110, 111, 139, 141 and 151 — the numbers on the
 * bidding board.
 *
 * Three faults, each reported more than once because each was on every card:
 *
 *   The guest count was invented from the event id — `50 + (id % 250)` — so
 *   a card read "114 Guests" beside its own description saying "catering for
 *   200".
 *
 *   Every urgent card carried the same hardcoded countdown of 6300 seconds,
 *   which is why two events five days apart showed the same time to the
 *   second. Nothing was being computed.
 *
 *   "All Opportunities" and the type tabs came from different queries, so the
 *   header said 54 while the tabs summed to 47.
 */
class BiddingBoardFiguresTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->account('professional');
        $this->client = $this->account('client');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function gig(array $attributes = []): Event
    {
        $event = Event::create(array_merge([
            'title'             => 'Conference Catering',
            'description'       => 'Catering for 200.',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'status'            => 'published',
            'is_published'      => true,
            'published_at'      => now()->subDay(),
            'starts_at'         => now()->addDays(40),
            'proposal_deadline' => now()->addDays(3)->addHours(2),
            'location'          => 'Baltimore',
        ], $attributes));

        $category = Category::firstOrCreate(
            ['slug' => 'board-figures-service'],
            ['name' => 'Board Figures Service', 'kind' => 'service', 'is_active' => true],
        );
        $event->categories()->syncWithoutDetaching([$category->id]);
        $this->pro->serviceCategories()->syncWithoutDetaching([$category->id]);

        return $event->fresh();
    }

    private function board()
    {
        return $this->actingAs($this->pro)->get(route('professional.bidding-board.index'));
    }

    private function gigs()
    {
        return collect($this->board()->viewData('gigs'));
    }

    /* ── Row 110: the guest count ───────────────────────────── */

    public function test_the_guest_count_is_the_real_one(): void
    {
        $this->gig(['guest_count' => 200]);

        $this->assertSame(200, $this->gigs()->first()['guests']);
    }

    /**
     * The old value was derived from the primary key, so it was always
     * present and always wrong. Absent beats invented.
     */
    public function test_an_unknown_guest_count_shows_nothing_rather_than_a_number(): void
    {
        $this->gig(['guest_count' => null]);

        $this->assertNull($this->gigs()->first()['guests']);
        $this->board()->assertDontSee('Guests', false);
    }

    /* ── Rows 106, 139, 141, 151: the countdown ─────────────── */

    /**
     * The one that made the bug obvious: two listings with different
     * deadlines must not report the same time remaining.
     */
    public function test_two_listings_with_different_deadlines_count_differently(): void
    {
        $this->gig(['title' => 'Closes soon', 'proposal_deadline' => now()->addHours(3)]);
        $this->gig(['title' => 'Closes later', 'proposal_deadline' => now()->addDays(5)]);

        $times = $this->gigs()->pluck('seconds')->filter()->unique();

        $this->assertCount(2, $times, 'each listing counts from its own deadline');
    }

    /**
     * Row 106 — a three-day deadline reads as three days, never "Tomorrow".
     *
     * Asserted on the day component only. The hours floor, and by the time
     * the request is served a few milliseconds have gone, so pinning the
     * exact string would make this fail on timing rather than on behaviour.
     */
    public function test_a_three_day_deadline_is_not_reported_as_one(): void
    {
        $this->gig(['proposal_deadline' => now()->addDays(3)->addHours(2)]);

        $this->assertStringStartsWith('3d ', $this->gigs()->first()['time']);
    }

    /** Row 151 — one format everywhere. */
    public function test_the_countdown_uses_one_format(): void
    {
        $this->gig(['title' => 'Days out', 'proposal_deadline' => now()->addDays(2)->addHours(5)]);
        $this->gig(['title' => 'Hours out', 'proposal_deadline' => now()->addHours(6)]);

        $times = $this->gigs()->pluck('time');

        // Every one reads "Xd Yh left" or "Yh left" — and never HH:MM:SS,
        // which was the other format the page used.
        foreach ($times as $time) {
            $this->assertMatchesRegularExpression(
                '/^(\d+d \d+h left|\d+h left|Under an hour left)$/', $time,
                "unexpected countdown format: {$time}",
            );
        }

        $this->assertTrue($times->contains(fn ($t) => str_starts_with($t, '2d ')));
        $this->assertTrue($times->contains(fn ($t) => preg_match('/^\dh left$/', $t) === 1));
    }

    /** It counts to the DEADLINE, which is what a professional is racing. */
    public function test_the_countdown_measures_the_deadline_not_the_event_date(): void
    {
        $this->gig([
            'starts_at'         => now()->addDays(60),
            'proposal_deadline' => now()->addDays(2),
        ]);

        // Two days out, floored — so "1d 23h", not sixty days.
        $this->assertMatchesRegularExpression('/^1d \d+h left$/', $this->gigs()->first()['time']);
    }

    public function test_a_listing_with_no_deadline_reads_open(): void
    {
        $this->gig(['proposal_deadline' => null]);

        $this->assertSame('Open', $this->gigs()->first()['time']);
        $this->assertNull($this->gigs()->first()['seconds']);
    }

    /* ── Row 111: the total and the tabs ────────────────────── */

    /**
     * The header total is the SUM of the type tabs, so they cannot drift
     * apart whatever the filters do. Saved sits outside the sum on purpose —
     * a saved gig is also a BR or an ER, and adding it would double-count.
     */
    public function test_all_opportunities_equals_the_sum_of_the_tabs(): void
    {
        $this->gig(['title' => 'Broadcast one']);
        $this->gig(['title' => 'Broadcast two']);
        $this->gig(['title' => 'An emergency', 'source' => 'esr']);

        $counts = $this->board()->viewData('counts');

        $this->assertSame(
            $counts['all'],
            $counts['BR'] + $counts['ER'] + $counts['DR'],
            'the header total must be the tabs added up',
        );
    }

    /** And it counts only what this professional can actually see (R38, R33). */
    public function test_the_tabs_exclude_what_the_list_excludes(): void
    {
        $this->gig(['title' => 'In my state']);
        $this->gig(['title' => 'Already expired', 'proposal_deadline' => now()->subDay()]);

        $outOfState = $this->gig(['title' => 'Another state']);
        $outOfState->forceFill(['state' => 'PA'])->save();

        $counts = $this->board()->viewData('counts');
        $listed = $this->gigs()->count();

        $this->assertSame(1, $counts['all']);
        $this->assertSame($listed, $counts['all'], 'the tab total must match what the list renders');
    }
}
