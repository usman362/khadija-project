<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\ClientStats;
use App\Support\Reports\ClientReport;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Issue #117, finished.
 *
 * A booking naming the client as their own professional is sample data. The
 * rule was put on the model as a scope so "the four pages cannot drift apart
 * again" — and then four more places went on counting those rows: the
 * dashboard's booking tiles and its recent list, the shared ClientStats that
 * feeds the dashboard and the client's public profile, the client report's
 * money totals, and the prompt asking somebody to review a booking they
 * supplied to themselves.
 *
 * Booking refuses to create one now, so these rows can only be the ones made
 * before the guard. They are inserted past the model here for the same reason
 * the scope still exists: the rows came first.
 */
class HiringYourselfIsNotABookingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Event $event;

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

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');

        $this->event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30),
        ]);

        // One real booking, completed, and one of the bad rows beside it.
        Booking::create([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'category_id' => $this->category(), 'status' => 'completed',
            'price' => 2000, 'currency' => 'USD',
        ]);

        $this->selfSupplied('completed', 400);
    }

    private function category(): int
    {
        return Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()])->id;
    }

    private function selfSupplied(string $status, float $price): void
    {
        DB::table('bookings')->insert([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->client->id, 'created_by' => $this->client->id,
            'category_id' => $this->category(), 'status' => $status,
            'price' => $price, 'currency' => 'USD',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_dashboard_tiles_leave_it_out(): void
    {
        $stats = $this->actingAs($this->client)->get(route('client.dashboard'))
            ->assertOk()->viewData('stats');

        $this->assertSame(1, $stats['total_bookings']);
    }

    public function test_the_dashboard_does_not_ask_you_to_review_yourself(): void
    {
        $todos = collect(
            $this->actingAs($this->client)->get(route('client.dashboard'))
                ->assertOk()->viewData('todos')
        );

        // One completed booking is reviewable, not two.
        $review = $todos->first(fn ($t) => str_contains($t['title'] ?? '', 'Review'));

        $this->assertNotNull($review);
        $this->assertStringContainsString('1 completed booking', $review['title']);
    }

    public function test_the_reviews_page_does_not_offer_it(): void
    {
        $pending = $this->actingAs($this->client)->get(route('client.reviews.index'))
            ->assertOk()->viewData('pendingReviews');

        $this->assertCount(1, $pending);
        $this->assertNotEquals($this->client->id, $pending->first()->supplier_id);
    }

    public function test_the_shared_stats_leave_it_out(): void
    {
        // These feed the dashboard AND the client's public profile, so a bad
        // row here is shown to other people.
        $this->assertSame(1, ClientStats::for($this->client)['completed_events']);
    }

    public function test_it_is_not_a_professional_this_client_keeps_rehiring(): void
    {
        $this->selfSupplied('confirmed', 400);

        // Two self-supplied rows is not "a professional booked twice", and the
        // professional in question would be the client.
        $this->assertSame(0, ClientStats::repeatProfessionals($this->client));
    }

    public function test_the_dashboards_own_page_counts_the_same_set(): void
    {
        /*
         * The dashboard VIEW runs six booking queries of its own, and they did
         * not carry the rule its controller had just been given. Two of them
         * sat two lines apart on the same card, one through ClientTotals and
         * one not, counting different sets.
         *
         * Confirmed rather than completed, so this row would land in the
         * "Hired" figure the page prints. That figure is the assertion: there
         * is one real confirmed booking and one bad row, and the card has to
         * say one.
         */
        $this->selfSupplied('confirmed', 400);

        Booking::create([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'category_id' => $this->category(), 'status' => 'confirmed',
            'price' => 1500, 'currency' => 'USD',
        ]);

        $html = $this->actingAs($this->client)->get(route('client.dashboard'))
            ->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<div class="od-mini-value">1<\/div><div class="od-mini-label">Hired<\/div>/',
            $html,
            'the Hired card is counting a booking the client supplied to themselves',
        );
    }

    public function test_an_event_type_needs_a_real_booking_to_count(): void
    {
        // The only completed booking on this event that is not self-supplied
        // is the real one, so the event counts once, not twice, and an event
        // with nothing but a self-supplied booking would not count at all.
        $counts = ClientStats::eventTypeCounts($this->client);

        $this->assertLessThanOrEqual(1, $counts->sum());
    }

    public function test_the_report_does_not_count_it_as_money_spent(): void
    {
        $report = new ClientReport($this->client, now()->subMonth(), now()->addDay());

        $this->assertEquals(2000.0, $report->spend()['spent']);
    }
}
