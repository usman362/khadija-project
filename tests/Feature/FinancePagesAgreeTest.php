<?php

namespace Tests\Feature;

use App\Domain\Finance\ClientTotals;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-146 — four finance pages, two totals.
 *
 * Bookings and Reports said $80. Spending and Payments said $5,080, "across
 * every booking". The difference was one cancelled $5,000 booking that two
 * pages excluded and two did not — and Spending put it under "Remaining",
 * showing a cancelled booking as money the client still had to spend.
 *
 * The figures below are the ticket's own: a live $80 and a cancelled $5,000.
 * Money is the one place where each page working out its own answer cannot
 * survive contact with a second page, so what is asserted is that the pages
 * agree, not merely that one of them is right.
 */
class FinancePagesAgreeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    private function booking(float $price, string $status): Booking
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);

        $event = Event::create([
            'title' => 'An event', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'open',
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $pro->id,
            'status' => $status, 'price' => $price, 'currency' => 'USD',
        ]);
    }

    /** The ticket's own numbers. */
    private function theTicketsAccount(): void
    {
        $this->booking(80, 'confirmed');
        $this->booking(5000, 'cancelled');
    }

    public function test_a_cancelled_booking_is_not_in_the_agreed_total(): void
    {
        $this->theTicketsAccount();

        $this->assertEqualsWithDelta(80.0, ClientTotals::agreed($this->client), 0.01);
        $this->assertEqualsWithDelta(5000.0, ClientTotals::cancelled($this->client), 0.01);
    }

    /** Bookings, Spending and Payments must print the same figure. */
    public function test_every_finance_page_reports_the_same_total(): void
    {
        $this->theTicketsAccount();

        $bookings = $this->actingAs($this->client)->get('/client/bookings')
            ->assertOk()->viewData('financial')['agreed_total'];

        $payments = $this->actingAs($this->client)->get('/client/payments')
            ->assertOk()->viewData('stats')['total_agreed'];

        $spending = $this->actingAs($this->client)->get('/client/spending')
            ->assertOk()->viewData('stats')['total_agreed'];

        $this->assertEqualsWithDelta(80.0, (float) $bookings, 0.01, 'Bookings');
        $this->assertEqualsWithDelta(80.0, (float) $payments, 0.01, 'Payments');
        $this->assertEqualsWithDelta(80.0, (float) $spending, 0.01, 'Spending');
    }

    /**
     * And the cancelled amount never lands in "Remaining" — that was the part
     * a client could act on.
     */
    public function test_cancelled_money_is_not_shown_as_still_to_spend(): void
    {
        $this->theTicketsAccount();

        $pipeline = $this->actingAs($this->client)->get('/client/spending')
            ->assertOk()->viewData('pipeline');

        $this->assertEqualsWithDelta(80.0, (float) $pipeline['total'], 0.01);
        $this->assertEqualsWithDelta(5000.0, (float) $pipeline['cancelled'], 0.01);

        $this->assertLessThanOrEqual(
            80.0,
            (float) $pipeline['remaining'],
            'a cancelled booking is being shown as money still to spend',
        );
    }

    /** A declined booking counts the same way a cancelled one does. */
    public function test_a_declined_booking_is_also_excluded(): void
    {
        $this->booking(200, 'confirmed');
        $this->booking(900, 'declined');

        $this->assertEqualsWithDelta(200.0, ClientTotals::agreed($this->client), 0.01);
    }

    /** Nothing outstanding reads as zero, never as a negative. */
    public function test_overpayment_does_not_produce_a_negative_outstanding(): void
    {
        $this->booking(100, 'confirmed');

        $this->assertSame(0.0, ClientTotals::outstanding($this->client, 250.0));
    }

    /**
     * And the shared total respects the event filter.
     *
     * The first version of ClientTotals took only the client, so both money
     * pages started reporting every booking whatever event was selected. Four
     * pages agreeing with each other and disagreeing with their own filter is
     * not an improvement on four pages disagreeing — it is the same fault,
     * harder to see. An existing test caught it, which is the only reason this
     * one exists.
     */
    public function test_the_total_follows_the_chosen_event(): void
    {
        $first = $this->booking(2000, 'confirmed');
        $this->booking(3000, 'confirmed');

        $all = ClientTotals::agreed($this->client);
        $one = ClientTotals::agreed($this->client, $first->event_id);

        $this->assertEqualsWithDelta(5000.0, $all, 0.01);
        $this->assertEqualsWithDelta(2000.0, $one, 0.01);
    }

    /** Cancelled money is scoped the same way, or the lines disagree. */
    public function test_cancelled_follows_the_chosen_event_too(): void
    {
        $cancelled = $this->booking(5000, 'cancelled');
        $this->booking(80, 'confirmed');

        $this->assertEqualsWithDelta(
            5000.0,
            ClientTotals::cancelled($this->client, $cancelled->event_id),
            0.01,
        );

        $this->assertEqualsWithDelta(
            0.0,
            ClientTotals::cancelled($this->client, $cancelled->event_id + 1),
            0.01,
        );
    }
}
