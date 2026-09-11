<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\Reports\ClientReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-147 — Reports disagreed with Bookings, three different ways.
 *
 *  - "Professionals hired 4" counted EVENTS that had a supplier, so three
 *    events with the same photographer read as three professionals. It also
 *    counted the client's own account, which appears as a supplier on a
 *    self-referential booking in the sample data.
 *  - "Who You Hire: Priya — 2 bookings" while Bookings showed three, because
 *    this counted only confirmed and completed while Bookings counts
 *    everything that stands.
 *  - "Requests posted 12" counted drafts — requests the client started and
 *    never sent, which no professional ever saw.
 *
 * All three are the same fault: a page deciding for itself what a word means.
 */
class ClientReportCountsTest extends TestCase
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

        $this->client = User::findOrFail($u->id);
    }

    private function event(bool $published = true, ?int $supplierId = null): Event
    {
        return Event::create([
            'title' => 'A request',
            'client_id' => $this->client->id,
            'created_by' => $this->client->id,
            'status' => 'published',
            'is_published' => $published,
            'supplier_id' => $supplierId,
        ]);
    }

    private function pro(): User
    {
        return User::factory()->create(['primary_role' => 'professional']);
    }

    private function report(): ClientReport
    {
        return new ClientReport($this->client, now()->subYear(), now()->addDay());
    }

    /* ── Requests posted ────────────────────────────────────── */

    public function test_drafts_are_not_counted_as_posted_requests(): void
    {
        $this->event(published: true);
        $this->event(published: false);   // started, never sent

        $this->assertSame(1, $this->report()->requests()['posted']);
    }

    /* ── Professionals hired ────────────────────────────────── */

    public function test_the_same_professional_twice_is_one_professional(): void
    {
        $pro = $this->pro();

        $this->event(supplierId: $pro->id);
        $this->event(supplierId: $pro->id);
        $this->event(supplierId: $pro->id);

        $this->assertSame(1, $this->report()->requests()['hired'], 'counted events, not professionals');
    }

    public function test_the_client_hiring_their_own_account_is_not_a_hire(): void
    {
        $this->event(supplierId: $this->client->id);

        $this->assertSame(0, $this->report()->requests()['hired']);
    }

    /* ── Who you hire ───────────────────────────────────────── */

    /**
     * Reports counted only confirmed and completed; Bookings counts everything
     * that stands. Both should say three.
     */
    public function test_who_you_hire_counts_the_same_bookings_the_bookings_page_does(): void
    {
        $pro = $this->pro();
        $event = $this->event();

        foreach (['confirmed', 'completed', 'requested'] as $status) {
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id,
                'created_by' => $this->client->id, 'supplier_id' => $pro->id,
                'status' => $status, 'price' => 100, 'currency' => 'USD',
            ]);
        }

        $row = $this->report()->professionals()->first();

        $this->assertSame(3, $row['bookings']);
    }

    /** A cancelled booking is not somebody you hired. */
    public function test_a_cancelled_booking_is_not_counted_as_hiring_them(): void
    {
        $pro = $this->pro();
        $event = $this->event();

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $pro->id,
            'status' => 'cancelled', 'price' => 5000, 'currency' => 'USD',
        ]);

        $this->assertCount(0, $this->report()->professionals());
    }

    /** And the self-referential record never appears in the list either. */
    public function test_the_client_never_appears_in_who_you_hire(): void
    {
        $event = $this->event();

        // Booking now refuses this (OA-141), but rows like it already exist on
        // the live site, so the report must still leave them out. Written
        // around the model's rule to stand for that old data.
        Booking::withoutEvents(fn () => Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->client->id,
            'status' => 'completed', 'price' => 100, 'currency' => 'USD',
        ]));

        $this->assertCount(0, $this->report()->professionals());
    }
}
