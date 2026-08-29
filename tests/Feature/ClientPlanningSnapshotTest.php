<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ClientPlanningSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The AI tool pages used to open onto a fabricated wedding — "Sarah & Alex",
 * The Garden Estate, six confirmed vendors, $12,450 remaining — the same for
 * every account. A brand-new client saw a full plan that was not theirs.
 *
 * These lock the replacement: real records, or an honest nothing.
 */
class ClientPlanningSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');

        return $u->fresh();
    }

    private function event(User $client, array $attrs = []): Event
    {
        return Event::create(array_merge([
            'title'      => 'Garden Reception',
            'client_id'  => $client->id,
            'created_by' => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
        ], $attrs));
    }

    private function booking(User $client, User $pro, Event $event, string $status, float $price): Booking
    {
        return Booking::create([
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $pro->id,
            'event_id'    => $event->id,
            'status'      => $status,
            'price'       => $price,
        ]);
    }

    public function test_a_client_with_no_event_gets_nothing_rather_than_a_stranger_plan(): void
    {
        $snap = ClientPlanningSnapshot::for($this->client());

        $this->assertFalse($snap->hasEvent());
        $this->assertSame(0, $snap->prosBooked);
        $this->assertSame([], $snap->vendors);
        $this->assertNull($snap->budgetRemaining());
    }

    public function test_the_page_shows_an_empty_state_not_the_old_demo_dashboard(): void
    {
        $response = $this->actingAs($this->client())->get(route('ai-tools.checklist-generator'));

        $response->assertSuccessful();
        $response->assertSee('Nothing to show here yet');

        // The fabricated plan must be gone for good.
        foreach (['The Garden Estate', 'Gourmet Eats Co.', 'DJ Soundwave', 'Blossom Floral', '12,450', 'Event Health'] as $ghost) {
            $response->assertDontSee($ghost);
        }
    }

    public function test_it_reads_the_clients_own_budget_and_booked_professionals(): void
    {
        $client = $this->client();

        $event = $this->event($client, ['budget' => 10000, 'starts_at' => now()->addDays(30)]);

        $pro = User::factory()->create(['name' => 'Halloway Sound']);
        $this->booking($client, $pro, $event, 'confirmed', 2500);

        // A request still waiting on the pro is not money spent.
        $other = User::factory()->create(['name' => 'Vestry Floral']);
        $this->booking($client, $other, $event, 'requested', 4000);

        $snap = ClientPlanningSnapshot::for($client);

        $this->assertTrue($snap->hasEvent());
        $this->assertSame(1, $snap->prosBooked, 'A pending request was counted as booked.');
        $this->assertSame(2500.0, $snap->spent, 'A pending request was counted as spent.');
        $this->assertSame(7500.0, $snap->budgetRemaining());
        $this->assertSame(25, $snap->spentPercent());
        $this->assertCount(2, $snap->vendors);
    }

    public function test_a_client_without_a_budget_is_not_given_a_remaining_figure(): void
    {
        $client = $this->client();
        $this->event($client, ['budget' => null, 'budget_max' => null, 'starts_at' => now()->addDays(10)]);

        $snap = ClientPlanningSnapshot::for($client);

        $this->assertTrue($snap->hasEvent());
        $this->assertNull($snap->budgetRemaining());
        $this->assertNull($snap->spentPercent());
    }

    public function test_days_to_event_counts_from_the_real_date(): void
    {
        $client = $this->client();
        $this->event($client, ['starts_at' => now()->addDays(45)]);

        $this->assertSame(45, ClientPlanningSnapshot::for($client)->daysToEvent());
    }

    /** The Guided Event Planner opened onto "Sarah & Alex Wedding" for everyone. */
    public function test_the_event_planner_no_longer_shows_a_borrowed_wedding(): void
    {
        $response = $this->actingAs($this->client())->get(route('ai-tools.event-planner'));

        $response->assertSuccessful();
        $response->assertSee('No event to plan yet');

        // "e.g. Baltimore, MD" survives as an input placeholder — that is an
        // example of what to type, not a claim about this client's event.
        foreach (['Sarah &amp; Alex', 'Elegant Affairs', 'TrueBlue Photography', 'Blossom Floral Studio'] as $ghost) {
            $response->assertDontSee($ghost);
        }
    }

    public function test_the_event_planner_shows_the_clients_real_event(): void
    {
        $client = $this->client();
        $this->event($client, ['title' => 'Marisol Quinceañera', 'starts_at' => now()->addDays(60)]);

        $this->actingAs($client)->get(route('ai-tools.event-planner'))
            ->assertSuccessful()
            ->assertSee('Marisol Quinceañera')
            ->assertDontSee('Sarah & Alex');
    }

    /**
     * Three calculators opened onto a finished result — a venue report for a
     * venue nobody entered, a seating plan for 185 guests nobody named, a
     * 12-vendor run-of-show with two conflicts. The page must now be quiet
     * until the client actually submits something.
     */
    public static function calculatorPages(): array
    {
        return [
            'venue analyzer'   => ['ai-tools.venue-analyzer',   ['The Garden Estate', '1234 Garden Way', 'Venue Score']],
            // Every capacity label survives inside the JS that renders a REAL
            // computed result. What must NOT survive is a stat element rendered
            // by the server on page load — assert the markup, not the class
            // name, which the stylesheet always emits.
            'guest capacity'   => ['ai-tools.guest-capacity',   ['<div class="gc-stat ', '<div class="gc-ins']],
            'timeline builder' => ['ai-tools.timeline-builder', ['Timeline Health', 'Vendors Scheduled', 'Start Live Event Mode']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('calculatorPages')]
    public function test_a_calculator_does_not_open_onto_a_finished_result(string $route, array $ghosts): void
    {
        $response = $this->actingAs($this->client())->get(route($route));

        $response->assertSuccessful();

        foreach ($ghosts as $ghost) {
            $response->assertDontSee($ghost);
        }
    }

    /**
     * Best Match listed invented vendors whenever fewer than five real ones
     * matched. They carried a name, a star rating, a review count and a price,
     * and sat in the same list as real people — the only thing separating them
     * was a missing button. It also printed a made-up price and a 4.3-ish
     * rating beside REAL professionals who had set neither.
     */
    public function test_best_match_lists_no_invented_vendors(): void
    {
        $response = $this->actingAs($this->client())->get(route('ai-tools.vendor-matchmaking'));

        $response->assertSuccessful();

        // Names from the retired catalogue must never appear again.
        foreach (['Coastal Canvas', 'Bloom & Vine', 'Lumen Studios', 'Tropical Beach Party'] as $ghost) {
            $response->assertDontSee($ghost);
        }
    }

    public function test_a_professional_with_no_rate_or_reviews_is_described_honestly(): void
    {
        $client = $this->client();

        // R38 — the client can only be shown pros in their own state.
        $client->profile()->updateOrCreate([], ['state' => 'MD']);

        $pro = User::factory()->create(['name' => 'Ondrej Bellweather']);
        $pro->assignRole('professional');
        $pro->profile()->create(['skills' => ['Photography'], 'hourly_rate' => null, 'state' => 'MD']);

        $response = $this->actingAs($client)->get(route('ai-tools.vendor-matchmaking'));

        $response->assertSuccessful();
        $response->assertSee('Ondrej Bellweather');
        $response->assertSee('Rate on request');
        $response->assertSee('No reviews yet');
    }
}
