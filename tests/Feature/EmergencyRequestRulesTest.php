<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 107, 108 and 112 — the emergency request's own rules, and
 * the My Bids totals.
 *
 * Row 108 is the one with teeth. Bidding on an ESR could stay open until the
 * moment the event started, so a professional could win a rush job at 6:58
 * for a 7:00 start. Nobody gets there, and the client — who raised an
 * EMERGENCY request — has nobody to fall back on.
 */
class EmergencyRequestRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function service(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'esr-rules-service'],
            ['name' => 'ESR Rules Service', 'kind' => 'service', 'is_active' => true],
        );
    }

    private function raiseEsr(string $neededBy, array $overrides = [])
    {
        return $this->actingAs($this->client)->post(route('client.esr.store'), array_merge([
            'event_name'  => 'Emergency catering',
            'reason'      => 'professional_cancelled',
            'description' => 'The caterer pulled out this morning and we need a replacement.',
            'needed_by'   => $neededBy,
            'services'    => [$this->service()->id],
            'budget_min'  => 600,
            'location'    => 'Baltimore',
        ], $overrides));
    }

    /* ── Row 108: the five-hour buffer (R7) ─────────────────── */

    public function test_bidding_closes_at_least_five_hours_before_the_event(): void
    {
        $neededBy = now()->addHours(30);

        $this->raiseEsr($neededBy->format('Y-m-d H:i:s'))->assertSessionHasNoErrors();

        $event = Event::where('source', 'esr')->firstOrFail();

        $this->assertNotNull($event->proposal_deadline);
        $this->assertTrue(
            $event->proposal_deadline->lessThanOrEqualTo($neededBy->copy()->subHours(5)),
            'the deadline must leave at least five hours before the event starts',
        );
    }

    /**
     * The 24-hour window still applies when it is the tighter of the two —
     * the buffer is a ceiling on the deadline, not a replacement for it.
     */
    public function test_the_twenty_four_hour_window_still_binds_on_a_distant_event(): void
    {
        $this->raiseEsr(now()->addDays(10)->format('Y-m-d H:i:s'))->assertSessionHasNoErrors();

        $event = Event::where('source', 'esr')->firstOrFail();

        $this->assertTrue($event->proposal_deadline->lessThanOrEqualTo(now()->addHours(25)));
    }

    /**
     * Refused, not published with a deadline already behind it — that would
     * be a request nobody could answer dressed up as an open one.
     */
    public function test_an_event_inside_the_buffer_is_refused_rather_than_published_dead(): void
    {
        $this->raiseEsr(now()->addHours(3)->format('Y-m-d H:i:s'))
            ->assertSessionHasErrors('needed_by');

        $this->assertDatabaseCount('events', 0);
    }

    /* ── Row 107: an ESR budget is one figure ───────────────── */

    /**
     * An emergency request states a fixed figure; SSR and MSR quote a range.
     * Showing "$400–$800" on a rush job invites a negotiation there is no
     * time for.
     */
    public function test_an_esr_shows_a_single_budget_figure_not_a_range(): void
    {
        $this->raiseEsr(now()->addHours(30)->format('Y-m-d H:i:s'), ['budget_min' => 600]);

        $event = Event::where('source', 'esr')->firstOrFail();
        $event->categories()->syncWithoutDetaching([$this->service()->id]);
        $this->pro->serviceCategories()->syncWithoutDetaching([$this->service()->id]);

        // Past the 60-minute tier delay, so a Starter professional sees it.
        $event->forceFill(['published_at' => now()->subHours(2)])->save();

        $gigs = collect(
            $this->actingAs($this->pro)->get(route('professional.bidding-board.index'))->viewData('gigs')
        );

        $esr = $gigs->firstWhere('type', 'ER');

        $this->assertNotNull($esr, 'the emergency request should reach the board');
        $this->assertSame('$600', $esr['budget']);
        $this->assertStringNotContainsString('–', $esr['budget']);
    }

    /* ── Row 112: My Bids totals reconcile ──────────────────── */

    /**
     * The reported fault was the status tabs summing to 29 against an "All"
     * of 23. Whatever the data, every bid falls in exactly one state, so the
     * buckets must add up to the total — that is the guarantee the row asks
     * for "after any filter or data change, not just at one snapshot".
     */
    public function test_the_status_tabs_add_up_to_all(): void
    {
        $event = Event::create([
            'title' => 'A gig', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(20),
        ]);

        foreach (['submitted', 'submitted', 'withdrawn'] as $status) {
            Bid::create([
                'event_id' => $event->id, 'supplier_id' => $this->pro->id,
                'amount' => 900, 'status' => $status,
            ]);
        }

        $counts = $this->actingAs($this->pro)
            ->get(route('professional.bidding-board.my-bids'))
            ->viewData('counts');

        $buckets = $counts['submitted'] + $counts['negotiating'] + $counts['won']
                 + $counts['not_selected'] + $counts['withdrawn'] + $counts['expired'];

        $this->assertSame($counts['all'], $buckets, 'every bid must fall in exactly one status bucket');
    }

    public function test_the_type_tabs_also_add_up_to_all(): void
    {
        $event = Event::create([
            'title' => 'A gig', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(20),
        ]);

        Bid::create(['event_id' => $event->id, 'supplier_id' => $this->pro->id, 'amount' => 900, 'status' => 'submitted']);

        $page = $this->actingAs($this->pro)->get(route('professional.bidding-board.my-bids'));

        $counts     = $page->viewData('counts');
        $typeCounts = $page->viewData('typeCounts');

        $this->assertSame(
            $counts['all'],
            $typeCounts['BR'] + $typeCounts['ER'] + $typeCounts['DR'],
        );
    }
}
