<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ClientStats;
use App\Support\Reports\ClientReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A client's own report — the last of the three Peter asked for on
 * 2026-08-09. Clients had none at all.
 *
 * A client's question is not the professional's. They are not competing for
 * work; they are spending money and want to know where it went, who they keep
 * hiring, and whether posting a request actually produces anyone.
 *
 * The shared figures come from App\Support\ClientStats, which is also what
 * the public Client Portfolio and the Dashboard read — R53's spec requires
 * exactly that, and a test below pins it.
 */
class ClientReportTest extends TestCase
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
        $user->givePermissionTo('dashboard.view');
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

    private function booking(Event $event, string $status, float $price, ?User $supplier = null): Booking
    {
        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => ($supplier ?? $this->pro)->id, 'created_by' => $this->client->id,
            'status' => $status, 'price' => $price, 'currency' => 'USD',
        ]);
    }

    private function report(): array
    {
        return (new ClientReport($this->client, now()->subDays(365), now()))->all();
    }

    public function test_spend_is_what_they_paid_not_what_the_platform_kept(): void
    {
        // Commission comes out of the professional at payout. Netting it off
        // here would misstate what an event actually cost the client.
        $this->booking($this->gig(), 'completed', 1000);

        $spend = $this->report()['spend'];

        $this->assertSame(1000.0, $spend['spent']);
        $this->assertSame(1000.0, $spend['average']);
    }

    public function test_work_still_in_flight_is_committed_not_spent(): void
    {
        $this->booking($this->gig('Done'), 'completed', 1000);
        $this->booking($this->gig('Upcoming'), 'confirmed', 2500);

        $spend = $this->report()['spend'];

        $this->assertSame(1000.0, $spend['spent']);
        $this->assertSame(2500.0, $spend['committed']);
    }

    public function test_a_client_who_has_spent_nothing_has_no_average(): void
    {
        // Zero would read as "your average event costs nothing".
        $this->assertNull($this->report()['spend']['average']);
    }

    public function test_whether_posting_a_request_produced_anyone(): void
    {
        $answered = $this->gig('Answered');
        Bid::create(['event_id' => $answered->id, 'supplier_id' => $this->pro->id, 'amount' => 900, 'status' => 'submitted']);
        $this->gig('Silence');

        $reqs = $this->report()['requests'];

        $this->assertSame(2, $reqs['posted']);
        $this->assertSame(50, $reqs['got_a_bid']);
        $this->assertSame(1, $reqs['bids_received']);
    }

    public function test_a_client_who_posted_nothing_has_no_rate(): void
    {
        $reqs = $this->report()['requests'];

        $this->assertSame(0, $reqs['posted']);
        $this->assertNull($reqs['got_a_bid']);
        $this->assertNull($reqs['bids_per_request']);
    }

    public function test_standing_matches_the_public_portfolio(): void
    {
        // R53's single-source requirement. A client shown one thing here and
        // professionals shown another about them is the defect that rule
        // names outright.
        $this->booking($this->gig(), 'completed', 1000);

        $report = $this->report()['standing'];
        $stats  = ClientStats::for($this->client);

        $this->assertSame($stats['cancellation_rate'], $report['cancellation_rate']);
        $this->assertSame($stats['repeat_professionals'], $report['repeat_pros']);
        $this->assertSame($stats['rating'], $report['rating']);
    }

    public function test_who_they_hire_is_ordered_by_how_often(): void
    {
        $regular = $this->pro;
        $once    = $this->account('professional');

        $this->booking($this->gig('A'), 'completed', 500, $regular);
        $this->booking($this->gig('B'), 'completed', 700, $regular);
        $this->booking($this->gig('C'), 'completed', 900, $once);

        $pros = collect($this->report()['professionals']);

        $this->assertSame(2, $pros->first()['bookings']);
        $this->assertSame(1200.0, $pros->first()['spent']);
    }

    public function test_a_cancelled_booking_is_not_someone_they_hired(): void
    {
        $this->booking($this->gig(), 'cancelled', 800);

        $this->assertCount(0, $this->report()['professionals']);
    }

    /* ── The page ──────────────────────────────────────────── */

    public function test_the_client_can_open_their_report(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.reports.index'))
            ->assertSuccessful()
            ->assertSee('What you spent')
            ->assertSee('How professionals see you');
    }

    public function test_the_range_falls_back_on_nonsense(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.reports.index', ['range' => 'nope']))
            ->assertSuccessful()->assertViewHas('range', '365');
    }

    public function test_the_dropdown_shows_the_range_the_page_is_actually_on(): void
    {
        // It did not. The array keys are numeric strings, which PHP casts to
        // int on the way into a foreach, so `$range === $value` compared
        // '90' to 90 and was never true — no option carried `selected` and
        // the browser fell back to showing the first one. The page said one
        // range and the figures were another.
        //
        // Asserted on the rendered option, not on the view variable: the old
        // test checked assertViewHas() and passed all the way through this.
        $page = $this->actingAs($this->client)
            ->get(route('client.reports.index', ['range' => '365']));

        $this->assertMatchesRegularExpression(
            '/<option value="' . trim('365', "'") . '"[^>]*\bselected\b/',
            $page->getContent(),
            'the Last 12 months option is not marked selected',
        );
    }

    /**
     * The file used to carry its own header row per section — including
     * `Professional,Bookings,Spent` — with rows of four different widths in
     * one sheet. Nothing lined up under anything, which is how the Owner saw
     * it on 26 Aug. It is one square table now: Section / Item / Value.
     */
    public function test_the_csv_downloads(): void
    {
        $pro = $this->booking($this->gig(), 'completed', 1000);

        $response = $this->actingAs($this->client)->get(route('client.reports.csv'));

        $response->assertSuccessful();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Section,Item,Value', $csv);
        $this->assertStringContainsString('Spend', $csv);
        $this->assertStringContainsString('Professionals', $csv);
        $this->assertStringContainsString('bookings', $csv);
    }

    public function test_the_sidebar_links_to_it(): void
    {
        $this->assertStringContainsString(
            "route('client.reports.index')",
            file_get_contents(resource_path('views/layouts/client.blade.php')),
        );
    }
}
