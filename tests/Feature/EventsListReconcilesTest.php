<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 85, 86, 101, 102 and 125 — the client's request list.
 *
 * Two faults, each reported several times because each showed up on several
 * cards:
 *
 *   The tiles never reconciled with the list, because they counted two
 *   different things under one heading. "Total Events" counted EVENTS;
 *   "Confirmed", "Pending" and "Paid" counted BOOKINGS. A client with one
 *   event and three professionals on it saw Total 1 beside Confirmed 3, and
 *   no arrangement of those numbers adds up.
 *
 *   Every card got the same generic pair of buttons, so a draft lost
 *   "Continue Draft" and a request in negotiation lost its way back in.
 *   "Compare Proposals" on a card with no proposals was the tell.
 */
class EventsListReconcilesTest extends TestCase
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

    private function event(string $status, bool $published = true, string $title = 'A request'): Event
    {
        return Event::create([
            'title'        => $title,
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => $status,
            'is_published' => $published,
            'starts_at'    => now()->addDays(30),
        ]);
    }

    private function page()
    {
        return $this->actingAs($this->client)->get(route('client.events.index'));
    }

    /* ── Rows 86, 101, 125: the tiles add up ────────────────── */

    /**
     * The heart of it. One event with three professionals on it used to read
     * Total 1, Confirmed 3 — two units, one row of tiles.
     */
    public function test_the_tiles_count_events_not_bookings(): void
    {
        $event = $this->event('published');

        foreach (range(1, 3) as $i) {
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id,
                'supplier_id' => $this->account('professional')->id,
                'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 500,
            ]);
        }

        $stats = $this->page()->assertOk()->viewData('stats');

        $this->assertSame(1, $stats['total'], 'one event');
        $this->assertSame(0, $stats['confirmed'], 'the EVENT is not confirmed — its bookings are');
    }

    public function test_every_tile_is_a_subset_of_the_total(): void
    {
        $this->event('published');
        $this->event('pending', false);
        $this->event('confirmed');
        $this->event('completed');
        $this->event('cancelled');

        $stats = $this->page()->viewData('stats');

        $this->assertSame(5, $stats['total']);
        $this->assertSame(
            $stats['total'],
            $stats['open'] + $stats['confirmed'] + $stats['completed'] + $stats['cancelled'],
            'the status tiles must account for every event exactly once',
        );
    }

    /** The footer says what it is counting, so it can be checked against the cards. */
    public function test_the_footer_states_what_its_number_counts(): void
    {
        foreach (range(1, 14) as $i) {
            $this->event('published', true, "Request {$i}");
        }

        $this->page()->assertSee('matching this filter', false);
    }

    /* ── Rows 85 and 102: the action fits the card ──────────── */

    public function test_a_draft_offers_the_one_action_that_finishes_it(): void
    {
        $this->event('pending', false, 'Corporate Holiday Dinner');

        $page = $this->page();

        $page->assertSee('Continue Draft', false);

        // Comparing proposals on an unpublished draft makes no sense.
        $page->assertDontSee('Compare Proposals', false);
    }

    public function test_a_request_in_negotiation_offers_a_way_back_into_it(): void
    {
        $event = $this->event('published', true, 'Wedding Reception Catering');

        Finalization::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'status'      => 'in_progress',
        ]);

        $this->page()->assertSee('Open Negotiation', false);
    }

    /** One proposal is reviewed, not compared. */
    public function test_a_single_proposal_is_not_offered_a_comparison(): void
    {
        $event = $this->event('published');

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'requested', 'price' => 800,
        ]);

        $page = $this->page();

        $page->assertSee('Review Proposal', false);
        $page->assertDontSee('Compare Proposals', false);
    }

    public function test_two_or_more_proposals_can_be_compared_and_the_count_is_shown(): void
    {
        $event = $this->event('published');

        foreach (range(1, 2) as $i) {
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id,
                'supplier_id' => $this->account('professional')->id,
                'created_by' => $this->client->id, 'status' => 'requested', 'price' => 800,
            ]);
        }

        $this->page()->assertSee('Compare Proposals (2)', false);
    }

    /** A published request nobody has bid on offers neither. */
    public function test_a_request_with_no_proposals_offers_no_proposal_action(): void
    {
        $this->event('published');

        $page = $this->page();

        $page->assertDontSee('Compare Proposals', false);
        $page->assertDontSee('Review Proposal', false);
        $page->assertSee('View', false);
    }
}
