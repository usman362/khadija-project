<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ClientStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-129 — "Cancellation Rate: 100%" from a single booking.
 *
 * The account had one cancelled booking and nothing completed, so the
 * arithmetic said 100% and the page said it on a card headed how professionals
 * see you. A professional reading that would decline the work.
 *
 * The sum was never wrong. Publishing it was: a percentage computed from one
 * booking is arithmetic, not a reputation. It is withheld until there are
 * enough decided bookings for it to mean something, and the card says so
 * rather than showing a bare dash.
 */
class ReputationRateNeedsEnoughDataTest extends TestCase
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

    private function bookings(int $cancelled, int $completed): void
    {
        $event = Event::create([
            'title' => 'An event', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'open',
        ]);

        foreach (array_merge(
            array_fill(0, $cancelled, 'cancelled'),
            array_fill(0, $completed, 'completed'),
        ) as $status) {
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id,
                'created_by' => $this->client->id,
                'status' => $status, 'price' => 100, 'currency' => 'USD',
            ]);
        }
    }

    /** The exact shape of the ticket: one cancelled, nothing else. */
    public function test_one_cancelled_booking_is_not_a_hundred_percent(): void
    {
        $this->bookings(cancelled: 1, completed: 0);

        $this->assertNull(ClientStats::for($this->client)['cancellation_rate']);
    }

    /** With enough history it is reported, and reported accurately. */
    public function test_the_rate_appears_once_there_is_enough_history(): void
    {
        $this->bookings(cancelled: 2, completed: 6);

        $this->assertSame(25, ClientStats::for($this->client)['cancellation_rate']);
    }

    /** A genuinely poor record is not hidden — the bar is deliberately low. */
    public function test_a_real_pattern_is_still_shown(): void
    {
        $this->bookings(cancelled: 5, completed: 0);

        $this->assertSame(100, ClientStats::for($this->client)['cancellation_rate']);
    }

    /** And the card explains itself instead of printing a bare dash. */
    public function test_the_card_says_why_there_is_no_rate_yet(): void
    {
        $this->bookings(cancelled: 1, completed: 0);

        $this->actingAs($this->client)->get('/client/reports')
            ->assertOk()
            ->assertSee('Shown once you have', false);
    }
}
