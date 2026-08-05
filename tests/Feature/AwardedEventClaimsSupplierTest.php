<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue 2 of the Professional IA Consolidation Plan: the same professional saw
 * 3 jobs on Contracts and Gig Operations Hub but 1 on My Gigs.
 *
 * Those pages read different tables — the first two count bookings, My Gigs
 * counts events — and accepting a bid stamped only the booking. The count was
 * the visible half; the other half was that the bidding board decides what is
 * still open by looking for events with no supplier, so awarded work stayed on
 * the board.
 */
class AwardedEventClaimsSupplierTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create();
    }

    private function event(): Event
    {
        $client = User::factory()->create();

        return Event::create([
            'title'        => 'Gig',
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'is_published' => true,
            'status'       => 'published',
            'starts_at'    => now()->addMonth(),
        ]);
    }

    private function book(Event $event, User $pro, string $status): Booking
    {
        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $event->client_id,
            'supplier_id' => $pro->id,
            'created_by'  => $event->client_id,
            'status'      => $status,
            'price'       => 500,
            'currency'    => 'USD',
        ]);
    }

    public function test_confirming_a_booking_marks_the_event_as_awarded(): void
    {
        $event = $this->event();
        $pro   = $this->pro();

        $this->assertNull($event->supplier_id);

        $this->book($event, $pro, 'confirmed');

        $this->assertSame($pro->id, $event->fresh()->supplier_id);
    }

    public function test_the_two_ways_of_counting_a_professionals_jobs_now_agree(): void
    {
        $pro = $this->pro();

        foreach (range(1, 3) as $ignored) {
            $this->book($this->event(), $pro, 'confirmed');
        }

        $asBookings = Booking::where('supplier_id', $pro->id)->count();   // Contracts, Gig Ops Hub
        $asEvents   = Event::where('supplier_id', $pro->id)->count();     // My Gigs

        $this->assertSame(3, $asBookings);
        $this->assertSame($asBookings, $asEvents, 'the two pages must not disagree about the same jobs');
    }

    public function test_an_unaccepted_proposal_does_not_take_the_job_off_the_board(): void
    {
        // A `requested` booking is a proposal nobody has accepted. If it
        // claimed the event, sending a proposal would lock every other
        // professional out of a job still open to them.
        $event = $this->event();

        $this->book($event, $this->pro(), 'requested');

        $this->assertNull($event->fresh()->supplier_id);
    }

    public function test_a_proposal_later_accepted_claims_the_event_then(): void
    {
        $event   = $this->event();
        $pro     = $this->pro();
        $booking = $this->book($event, $pro, 'requested');

        $this->assertNull($event->fresh()->supplier_id);

        $booking->update(['status' => 'confirmed']);

        $this->assertSame($pro->id, $event->fresh()->supplier_id);
    }

    public function test_a_second_professional_cannot_take_an_already_awarded_event(): void
    {
        $event = $this->event();
        $first = $this->pro();
        $this->book($event, $first, 'confirmed');

        $this->book($event, $this->pro(), 'confirmed');

        $this->assertSame($first->id, $event->fresh()->supplier_id, 'the first winner keeps the job');
    }

    public function test_a_cancelled_booking_leaves_the_event_open(): void
    {
        $event = $this->event();

        $this->book($event, $this->pro(), 'cancelled');

        $this->assertNull($event->fresh()->supplier_id);
    }
}
