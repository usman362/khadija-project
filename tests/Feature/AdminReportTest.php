<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\Reports\AdminReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin reporting, built after Peter's 2026-08-09 note that reporting was
 * "not even close": influencers had six analytics pages, professionals had
 * two CSV exports, and admins and clients had nothing at all.
 *
 * The discipline that matters more than any single figure: nothing here is
 * modelled or projected. Where the data cannot answer a question the answer
 * is null and the page prints a dash. An invented number on a report is worse
 * than a missing one, because a report is the thing that gets acted on.
 */
class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin  = $this->account('admin');
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

    private function gig(string $title = 'Gala'): Event
    {
        return Event::create([
            'title' => $title, 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'is_published' => true, 'status' => 'published', 'starts_at' => now()->addMonth(),
        ]);
    }

    private function completedBooking(Event $event, float $price): Booking
    {
        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => $price, 'currency' => 'USD',
        ]);
    }

    private function report(): array
    {
        return (new AdminReport(now()->subDays(30), now()))->all();
    }

    public function test_booked_value_is_the_sum_of_completed_bookings(): void
    {
        $this->completedBooking($this->gig('A'), 1000);
        $this->completedBooking($this->gig('B'), 500);

        $this->assertSame(1500.0, $this->report()['money']['gross']);
        $this->assertSame(2, $this->report()['money']['bookings']);
    }

    public function test_a_booking_that_is_not_complete_is_not_revenue(): void
    {
        $event = $this->gig();
        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 9999, 'currency' => 'USD',
        ]);

        $this->assertSame(0.0, $this->report()['money']['gross']);
    }

    public function test_commission_uses_each_professionals_own_rate(): void
    {
        // A blended rate off the total would be a different number and a
        // wrong one — Starter pays 5%, Elite 1.5%, and the report is summed
        // per booking for that reason.
        $this->completedBooking($this->gig(), 1000);

        // No subscription, so Starter's 5%.
        $this->assertSame(50.0, $this->report()['money']['commission']);
    }

    public function test_an_empty_period_has_no_average_rather_than_zero(): void
    {
        // Zero would read as "our average booking is worth nothing".
        $report = $this->report();

        $this->assertSame(0.0, $report['money']['gross']);
        $this->assertNull($report['money']['average_value']);
    }

    public function test_the_two_rates_a_marketplace_lives_on(): void
    {
        $withBid  = $this->gig('Has a bid');
        $this->gig('Nobody bid');

        Bid::create([
            'event_id' => $withBid->id, 'supplier_id' => $this->pro->id,
            'amount' => 800, 'status' => 'submitted',
        ]);

        $market = $this->report()['marketplace'];

        $this->assertSame(2, $market['posted']);
        $this->assertSame(50, $market['bid_rate']);     // one of two got a bid
        $this->assertSame(0, $market['award_rate']);    // neither was hired
    }

    public function test_rates_are_null_when_nothing_was_posted(): void
    {
        $market = $this->report()['marketplace'];

        $this->assertSame(0, $market['posted']);
        $this->assertNull($market['bid_rate']);
        $this->assertNull($market['bids_per_gig']);
        $this->assertNull($market['time_to_first_bid_hours']);
    }

    public function test_active_means_someone_did_something(): void
    {
        // Not "has an account". Counting accounts that merely exist is how a
        // marketplace convinces itself it is busy.
        $idle = $this->account('professional');
        $this->gig();

        $people = $this->report()['people'];

        $this->assertSame(0, $people['active_pros'], 'nobody has bid yet');
        $this->assertSame(1, $people['active_clients']);
        $this->assertGreaterThan(0, $people['signups']);
    }

    public function test_the_queue_ignores_the_date_range(): void
    {
        // A document waiting since June is exactly what an admin needs to see
        // in an August report.
        $this->pro->profile->update([
            'trade_license_doc' => 'verification/old.pdf',
            'trade_license_verified_at' => null,
        ]);

        $narrow = (new AdminReport(now()->subDay(), now()))->all();

        $this->assertSame(1, $narrow['needs_attention']['verification_pending']);
    }

    public function test_open_gigs_with_no_bids_are_counted(): void
    {
        $this->gig('Nobody bid');

        $this->assertSame(1, $this->report()['needs_attention']['open_gigs_no_bids']);
    }

    public function test_every_launch_state_has_a_row_even_at_zero(): void
    {
        // A state missing from the table reads as a data problem; a state
        // showing zero is the answer.
        $states = collect($this->report()['by_state'])->pluck('state');

        $this->assertSame(count(config('geo.allowed_states')), $states->count());
        $this->assertContains('MD', $states->all());
    }

    public function test_the_state_table_always_adds_up_to_the_headline(): void
    {
        // It did not, and it looked like a bug in the report rather than a
        // gap in the data: $4,000 across the states under a headline of
        // $55,950, because events raised before R38 added the column have no
        // state to be filed under. A breakdown that does not reconcile to its
        // own total is not worth reading, so whatever cannot be placed gets a
        // row of its own.
        $placed = $this->gig('Has a state');
        $this->completedBooking($placed, 1000);

        $orphan = $this->gig('No state');
        $orphan->forceFill(['state' => null])->saveQuietly();
        $this->completedBooking($orphan, 250);

        $report = $this->report();

        $this->assertSame(
            $report['money']['gross'],
            collect($report['by_state'])->sum('revenue'),
        );
        $this->assertSame(250.0, collect($report['by_state'])->firstWhere('state', 'Not recorded')['revenue']);
    }

    public function test_the_unplaced_row_is_absent_when_everything_is_placed(): void
    {
        // A "Not recorded — 0" row on a clean database is noise that reads as
        // a problem.
        $this->completedBooking($this->gig(), 1000);

        $this->assertNull(collect($this->report()['by_state'])->firstWhere('state', 'Not recorded'));
    }

    public function test_revenue_is_attributed_to_the_events_state(): void
    {
        $this->completedBooking($this->gig(), 2000);

        $md = collect($this->report()['by_state'])->firstWhere('state', 'MD');

        $this->assertSame(2000.0, $md['revenue']);
        $this->assertSame(1, $md['gigs']);
    }

    /* ── The page ──────────────────────────────────────────── */

    public function test_an_admin_can_open_the_report(): void
    {
        $this->actingAs($this->admin)
            ->get(route('app.admin.reports.index'))
            ->assertSuccessful()
            ->assertSee('Reports')
            ->assertSee('Booked value');
    }

    public function test_a_professional_cannot(): void
    {
        $this->actingAs($this->pro)
            ->get(route('app.admin.reports.index'))
            ->assertForbidden();
    }

    public function test_the_range_can_be_changed_and_a_bad_one_falls_back(): void
    {
        $this->actingAs($this->admin)
            ->get(route('app.admin.reports.index', ['range' => '7']))
            ->assertSuccessful()->assertViewHas('range', '7');

        $this->actingAs($this->admin)
            ->get(route('app.admin.reports.index', ['range' => 'nonsense']))
            ->assertSuccessful()->assertViewHas('range', '30');
    }

    public function test_the_csv_carries_the_same_figures(): void
    {
        $this->completedBooking($this->gig(), 1000);

        $response = $this->actingAs($this->admin)->get(route('app.admin.reports.csv'));

        $response->assertSuccessful();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('GigResource report', $csv);
        $this->assertStringContainsString('Gross,1000', $csv);
        $this->assertStringContainsString('By state', $csv);
    }

    public function test_the_admin_sidebar_links_to_it(): void
    {
        // The Toolkit Tiers page shipped reachable by URL and linked from
        // nowhere, so nobody found it.
        $this->assertStringContainsString(
            "route('app.admin.reports.index')",
            file_get_contents(resource_path('views/layouts/dashboard.blade.php')),
        );
    }
}
