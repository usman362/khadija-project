<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client dashboard must not tell the client things that are not true.
 *
 * Three panels were inventing their content:
 *   - "Total Spent" summed bookings.total_amount / agreed_price. Neither
 *     column exists (the amount is bookings.price), so the query threw on
 *     every render and a try/catch swallowed it — the card read $0.00 for
 *     every client, forever, and looked like an answer.
 *   - The calendar drew five events the client had never created (Wedding,
 *     Baltimore MD; Brand Launch, Washington DC…) whenever they had none.
 *   - The to-do list held four hardcoded chores under tab counts —
 *     To Do (4), In Progress (2) — that counted nothing at all.
 */
class ClientDashboardHonestyTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create();
        $u->assignRole('client');
        $u->givePermissionTo('dashboard.view');

        return $u->fresh();
    }

    private function dashboard(User $u): string
    {
        return $this->actingAs($u)->get(route('client.dashboard'))->assertOk()->getContent();
    }

    /** The number moves with real money, instead of being a permanent zero. */
    public function test_total_spent_reflects_completed_payments(): void
    {
        $client = $this->client();

        $this->assertStringContainsString('$0.00', $this->dashboard($client));

        Payment::create([
            'user_id' => $client->id, 'gateway' => 'test', 'status' => 'completed',
            'amount' => 1250.50, 'currency' => 'USD', 'completed_at' => now(),
        ]);

        $this->assertStringContainsString('$1,250.50', $this->dashboard($client));
    }

    /** Pending money is not spent money. */
    public function test_an_incomplete_payment_is_not_counted_as_spent(): void
    {
        $client = $this->client();

        Payment::create([
            'user_id' => $client->id, 'gateway' => 'test', 'status' => 'pending',
            'amount' => 900, 'currency' => 'USD',
        ]);

        $this->assertStringNotContainsString('$900.00', $this->dashboard($client));
    }

    public function test_an_empty_calendar_shows_nothing_rather_than_invented_events(): void
    {
        $html = $this->dashboard($this->client());

        foreach (['Brand Launch, Washington DC', 'Wedding, Baltimore MD', 'Corporate Event, Arlington VA'] as $invented) {
            $this->assertStringNotContainsString($invented, $html);
        }

        $this->assertStringContainsString('Nothing scheduled this month', $html);
    }

    public function test_the_to_do_list_holds_real_work_not_hardcoded_chores(): void
    {
        $client = $this->client();

        $html = $this->dashboard($client);
        $this->assertStringNotContainsString('Find and book a photographer', $html);
        $this->assertStringNotContainsString('To Do (4)', $html);
        $this->assertStringContainsString('Nothing needs you right now', $html);

        // An unpublished request is real outstanding work, so it appears.
        Event::create([
            'title' => 'Draft request', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'pending', 'is_published' => false, 'starts_at' => now()->addMonth(),
        ]);

        $this->assertStringContainsString('still unpublished', $this->dashboard($client));
    }

    /** Finished work the client has not rated is offered, once. */
    public function test_a_completed_booking_appears_as_a_review_to_leave(): void
    {
        $client = $this->client();
        $pro    = User::factory()->create();

        $event = Event::create([
            'title' => 'Done', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'completed', 'starts_at' => now()->subWeek(),
        ]);
        Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
            'created_by' => $client->id, 'status' => 'completed', 'price' => 500, 'currency' => 'USD',
        ]);

        $this->assertStringContainsString('Review 1 completed booking', $this->dashboard($client));
    }
}
