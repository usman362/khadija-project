<?php

namespace Tests\Feature;

use App\Models\{Bid, Booking, Category, Event, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Proposals / Bids, against Khadijah's 28 October screen.
 *
 * Her table has ten columns and seven tabs, and three of the figures on it
 * have nothing behind them on this platform. Those three are the point of most
 * of these tests: a screen a professional uses to decide whether to chase a
 * job, or drop it, must not carry a number that was made up to fill a column.
 *
 *   Est. Margin / Est. profit   nothing records what a job costs this
 *                               professional to deliver.
 *   Viewed / Not Viewed         nothing records a client opening a proposal.
 *   The event photo             no event stores an image.
 */
class TheProposalsListMatchesTheDesignTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00'));

        $this->pro    = $this->person('professional');
        $this->client = $this->person('client');

        $this->service = Category::create([
            'name' => 'DJ Services', 'slug' => 'dj-services-proposals',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function proposal(array $event = [], array $booking = []): Booking
    {
        $e = Event::create(array_merge([
            'title'        => 'Dana Whitfield Wedding Reception',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'event_type'   => 'Wedding',
            'location'     => 'Baltimore, MD',
            'guest_count'  => 150,
            'starts_at'    => now()->addDays(60),
        ], $event));

        $e->categories()->sync([$this->service->id]);

        return Booking::create(array_merge([
            'event_id'    => $e->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $this->client->id,
            'status'      => 'requested',
            'price'       => 6200,
        ], $booking));
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.proposals.index', $query))
            ->assertOk()
            ->getContent();
    }

    // ---------------------------------------------------------------- layout

    /** Her four cards, with the words she wrote under each one. */
    public function test_the_four_summary_cards_carry_her_labels(): void
    {
        $this->proposal();

        $html = $this->page();

        foreach ([
            'All Proposals', 'All submitted and received proposals',
            'Needs My Response', 'Client replied, countered, or requested changes',
            'Awaiting Client Response', 'No client response yet',
            'Accepted', 'Ready to move forward',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The card copy \"{$copy}\" is missing.");
        }
    }

    /** Her tabs, each with its own count in brackets. */
    public function test_every_tab_names_itself_and_counts_itself(): void
    {
        $this->proposal();

        $html = $this->page();

        foreach (['All (1)', 'Needs My Response (0)', 'Awaiting Client (1)',
                  'Accepted (0)', 'Declined (0)', 'Expired (0)',
                  'Withdrawn (0)', 'Archived (0)'] as $tab) {
            $this->assertStringContainsString($tab, $html, "The tab \"{$tab}\" is missing.");
        }
    }

    /** Her ten column headings. */
    public function test_the_table_has_her_columns(): void
    {
        $this->proposal();

        $html = $this->page();

        foreach (['Event / Client', 'Your Proposal', 'Proposal Status', 'Client Activity',
                  'Last Activity', 'Expires', 'Next Action', 'Actions'] as $head) {
            $this->assertStringContainsString($head, $html, "The column \"{$head}\" is missing.");
        }
    }

    /** The row names the client, the place, the date and the head count. */
    public function test_a_row_carries_the_client_the_place_the_date_and_the_guests(): void
    {
        $this->proposal();

        $html = $this->page();

        $this->assertStringContainsString('Dana Whitfield Wedding Reception', $html);
        $this->assertStringContainsString($this->client->name, $html);
        $this->assertStringContainsString('Baltimore, MD', $html);
        $this->assertStringContainsString('150 guests', $html);
        $this->assertStringContainsString('Wedding', $html);
    }

    // --------------------------------------------- figures with nothing behind them

    /**
     * Est. Margin is on her screen and is not on this one.
     *
     * Nothing on GigResource records what a job costs a professional to
     * deliver, so a margin would be a number with nothing behind it on the
     * screen somebody uses to decide whether to take the work. The column
     * keeps its place and carries the one deduction that is known.
     */
    public function test_no_margin_is_shown_because_nothing_records_a_cost(): void
    {
        $this->proposal();

        $html = $this->page();

        $this->assertStringNotContainsString('Est. Margin', $html);
        $this->assertStringNotContainsString('Est. profit', $html);

        // What is known: the platform's own cut, and what is left after it.
        $this->assertStringContainsString('After Fees', $html);
        $this->assertStringContainsString('$5,890', $html, 'The 5% Starter fee on $6,200 leaves $5,890.');
    }

    /**
     * "Viewed" and "Not Viewed" are not shown, because nothing records a view.
     *
     * What is recorded is whether the client has written back, which is the
     * thing that actually moves a proposal on.
     */
    public function test_client_activity_reports_replies_not_views(): void
    {
        $booking = $this->proposal();

        $this->assertStringNotContainsString('Not Viewed', $this->page());

        $this->assertStringContainsString('No reply yet', $this->page());

        Message::create([
            'booking_id' => $booking->id,
            'sender_id'  => $this->client->id,
            'body'       => 'Hi, thanks for the proposal.',
        ]);

        $html = $this->page();
        $this->assertStringContainsString('Replied', $html);
        $this->assertStringNotContainsString('Viewed', $html);
    }

    /** A proposal the client has answered is in negotiation, not pending. */
    public function test_a_proposal_the_client_answered_reads_in_negotiation(): void
    {
        $booking = $this->proposal();

        $this->assertStringContainsString('Pending', $this->page());

        Message::create([
            'booking_id' => $booking->id,
            'sender_id'  => $this->client->id,
            'body'       => 'Could you break the lighting out separately?',
        ]);

        $this->assertStringContainsString('In Negotiation', $this->page());
    }

    // --------------------------------------------------------------- expiry

    /** Her wording, both ways round. */
    public function test_the_expiry_column_says_when_or_says_there_is_none(): void
    {
        $this->proposal(['proposal_deadline' => now()->addDays(4)]);

        $html = $this->page();
        $this->assertStringContainsString('Expires in 4 days', $html);
        $this->assertStringContainsString('Nov 10, 2026', $html);

        Event::query()->update(['proposal_deadline' => null]);

        $this->assertStringContainsString('No expiration set', $this->page());
    }

    /**
     * The closing date was read off a column that was never selected.
     *
     * proposal_deadline was missing from the eager load, so it came back null
     * and the page said "no closing date" about every request that had one.
     */
    public function test_a_request_with_a_deadline_never_reads_as_having_none(): void
    {
        $this->proposal(['proposal_deadline' => now()->addDays(9)]);

        $this->assertStringNotContainsString('No expiration set', $this->page());
    }

    // ------------------------------------------------------------- the panel

    /** The opened row shows the version history that is actually recorded. */
    public function test_the_version_history_lists_only_what_was_recorded(): void
    {
        $booking = $this->proposal();

        Bid::create([
            'event_id'     => $booking->event_id,
            'supplier_id'  => $this->pro->id,
            'category_id'  => $this->service->id,
            'amount'       => 6200,
            'status'       => 'submitted',
            'submitted_at' => now()->subDays(3),
        ]);

        $html = $this->page();

        $this->assertStringContainsString('Proposal Version History', $html);
        $this->assertStringContainsString('Version 1 (Current)', $html);
        $this->assertStringNotContainsString('Version 2', $html);
    }

    /** A bid changed after it was first written down is a second version. */
    public function test_a_revised_bid_becomes_version_two(): void
    {
        $booking = $this->proposal();

        $bid = Bid::create([
            'event_id'    => $booking->event_id,
            'supplier_id' => $this->pro->id,
            'category_id' => $this->service->id,
            'amount'      => 6200,
            'status'      => 'submitted',
        ]);

        Carbon::setTestNow(now()->addDays(2));
        $bid->update(['amount' => 5800]);

        $html = $this->page();

        $this->assertStringContainsString('Version 2 (Current)', $html);
        $this->assertStringContainsString('Revised proposal', $html);
        $this->assertStringContainsString('Original proposal', $html);
    }

    /** A service with no price on it says so rather than showing $0. */
    public function test_a_service_with_no_price_says_so(): void
    {
        $this->proposal();

        $html = $this->page();

        $this->assertStringContainsString('DJ Services', $html);
        $this->assertStringContainsString('No price given', $html);
        $this->assertStringNotContainsString('DJ Services</span>
                                        <span style="display:flex; align-items:center; gap:10px;">
                                                <b>$0</b>', $html);
    }

    // ------------------------------------------------------------- the acts

    /** Archiving takes a row out of the list and out of every count but one. */
    public function test_archiving_hides_a_proposal_without_deleting_it(): void
    {
        $booking = $this->proposal();

        $this->actingAs($this->pro)
            ->post(route('professional.proposals.archive'), ['ids' => (string) $booking->id])
            ->assertRedirect();

        $this->assertNotNull($booking->fresh()->archived_at);

        $html = $this->page();
        $this->assertStringContainsString('All (0)', $html);
        $this->assertStringContainsString('Archived (1)', $html);
        $this->assertStringNotContainsString('Dana Whitfield Wedding Reception', $html);

        // And it is still there when asked for.
        $this->assertStringContainsString(
            'Dana Whitfield Wedding Reception',
            $this->page(['tab' => 'archived'])
        );
    }

    /** Archiving undone puts the row back where it was. */
    public function test_an_archived_proposal_can_be_put_back(): void
    {
        $booking = $this->proposal(booking: ['archived_at' => now()]);

        $this->actingAs($this->pro)
            ->post(route('professional.proposals.archive'),
                ['ids' => (string) $booking->id, 'undo' => 1])
            ->assertRedirect();

        $this->assertNull($booking->fresh()->archived_at);
    }

    /** Withdrawing is only for an offer the client has not answered. */
    public function test_only_an_unanswered_proposal_can_be_withdrawn(): void
    {
        $open     = $this->proposal();
        $accepted = $this->proposal(booking: ['status' => 'confirmed']);

        $this->actingAs($this->pro)
            ->post(route('professional.proposals.withdraw'),
                ['ids' => $open->id . ',' . $accepted->id])
            ->assertRedirect();

        $this->assertSame('cancelled', $open->fresh()->status);
        $this->assertSame('confirmed', $accepted->fresh()->status,
            'A booking the client already accepted is not the professional\'s to withdraw.');
    }

    /** Export gives the list as a file, with the net figure in it. */
    public function test_the_list_exports_as_a_file(): void
    {
        $this->proposal();

        $response = $this->actingAs($this->pro)
            ->get(route('professional.proposals.export'))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Yours after fees', $csv);
        $this->assertStringContainsString('Dana Whitfield Wedding Reception', $csv);
        $this->assertStringContainsString('5890', $csv);
    }

    // ------------------------------------------------------------- filtering

    /** Each of her four dropdowns narrows the list for real. */
    public function test_the_dropdowns_filter_the_list(): void
    {
        $wedding = $this->proposal();
        $gala    = $this->proposal(['title' => 'Annual Corporate Gala', 'event_type' => 'Corporate Event'],
                                   ['status' => 'confirmed']);

        $byType = $this->page(['type' => 'Corporate Event']);
        $this->assertStringContainsString('Annual Corporate Gala', $byType);
        $this->assertStringNotContainsString('Dana Whitfield Wedding Reception', $byType);

        $byStatus = $this->page(['status' => 'accepted']);
        $this->assertStringContainsString('Annual Corporate Gala', $byStatus);
        $this->assertStringNotContainsString('Dana Whitfield Wedding Reception', $byStatus);

        $byActivity = $this->page(['activity' => 'accepted']);
        $this->assertStringContainsString('Annual Corporate Gala', $byActivity);

        // Both were created just now, so a week's window keeps both.
        $recent = $this->page(['period' => '7']);
        $this->assertStringContainsString('Annual Corporate Gala', $recent);
        $this->assertStringContainsString('Dana Whitfield Wedding Reception', $recent);

        $this->assertNotNull($wedding->fresh());
        $this->assertNotNull($gala->fresh());
    }

    /** The search box does the three things its own placeholder promises. */
    public function test_search_matches_event_client_or_place(): void
    {
        $this->proposal();
        $this->proposal(['title' => 'Annual Corporate Gala', 'location' => 'Washington, DC']);

        $this->assertStringNotContainsString('Annual Corporate Gala', $this->page(['search' => 'Whitfield']));
        $this->assertStringNotContainsString('Dana Whitfield Wedding', $this->page(['search' => 'Washington']));
    }

    /** The page honours the size the footer offers. */
    public function test_the_page_size_in_the_footer_is_obeyed(): void
    {
        foreach (range(1, 8) as $i) {
            $this->proposal(['title' => "Event number {$i}"]);
        }

        $flat = fn (string $html) => preg_replace('/\s+/', ' ', $html);

        $this->assertStringContainsString('Showing 1&ndash;6 of 8 proposals', $flat($this->page()));
        $this->assertStringContainsString('Showing 1&ndash;8 of 8 proposals', $flat($this->page(['per' => 12])));
    }
}
