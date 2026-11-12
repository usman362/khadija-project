<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Check-in, from the Gig Operations Hub line of the 5 November checklist.
 *
 * Two timestamps rather than one, because the useful fact is not that
 * somebody said it happened but WHO said so: the professional marking
 * themselves on site is a claim, the client agreeing is a confirmation, and
 * the gap between them is the only part anybody argues about afterwards.
 *
 * The rules these hold are the ones that make the record worth keeping: only
 * on the day, only the two parties, written once, and undone only while it is
 * still the day it was made on.
 */
class CheckingInOnTheDayTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro      = $this->person('professional');
        $this->client   = $this->person('client');
        $this->stranger = $this->person('client');
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

    private function booking(string $when = 'today', string $status = 'confirmed'): Booking
    {
        $starts = match ($when) {
            'today'    => now()->setTime(18, 0),
            'tomorrow' => now()->addDay()->setTime(18, 0),
            'past'     => now()->subWeek()->setTime(18, 0),
        };

        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => $starts, 'location' => 'Baltimore, MD',
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'category_id' => Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()])->id,
            'status' => $status, 'price' => 2000, 'currency' => 'USD',
        ]);
    }

    public function test_the_professional_can_say_they_are_on_site_today(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->pro)
            ->post(route('bookings.check-in', $booking))
            ->assertRedirect();

        $booking->refresh();

        $this->assertNotNull($booking->supplier_checked_in_at);
        $this->assertNull($booking->client_checked_in_at, 'one side checking in is not both');
        $this->assertSame('The professional has checked in', $booking->checkInLine());
    }

    public function test_the_two_sides_are_recorded_apart(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));
        $this->actingAs($this->client)->post(route('bookings.check-in', $booking));

        $booking->refresh();

        // The claim and the confirmation. One shared column would keep the
        // first and lose the second.
        $this->assertNotNull($booking->supplier_checked_in_at);
        $this->assertNotNull($booking->client_checked_in_at);
        $this->assertSame('On site, confirmed by both', $booking->checkInLine());
    }

    public function test_a_second_tap_does_not_move_the_time(): void
    {
        Carbon::setTestNow(now()->setTime(9, 0));
        $booking = $this->booking();

        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));
        $first = $booking->fresh()->supplier_checked_in_at;

        Carbon::setTestNow(now()->setTime(11, 30));
        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));

        // A second tap is not a second arrival, and overwriting would quietly
        // move a time somebody may be relying on.
        $this->assertEquals($first, $booking->fresh()->supplier_checked_in_at);
    }

    public function test_not_before_the_day(): void
    {
        $booking = $this->booking('tomorrow');

        $this->actingAs($this->pro)
            ->post(route('bookings.check-in', $booking))
            ->assertForbidden();

        $this->assertNull($booking->fresh()->supplier_checked_in_at);
    }

    public function test_not_after_the_day(): void
    {
        $booking = $this->booking('past');

        $this->actingAs($this->pro)
            ->post(route('bookings.check-in', $booking))
            ->assertForbidden();
    }

    public function test_not_on_a_gig_nobody_has_accepted(): void
    {
        $booking = $this->booking('today', 'requested');

        // Nobody turns up to a request.
        $this->actingAs($this->pro)
            ->post(route('bookings.check-in', $booking))
            ->assertForbidden();
    }

    public function test_not_on_a_cancelled_gig(): void
    {
        $booking = $this->booking('today', 'cancelled');

        $this->actingAs($this->client)
            ->post(route('bookings.check-in', $booking))
            ->assertForbidden();
    }

    public function test_a_stranger_cannot_check_anybody_in(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->stranger)
            ->post(route('bookings.check-in', $booking))
            ->assertForbidden();

        $booking->refresh();
        $this->assertNull($booking->supplier_checked_in_at);
        $this->assertNull($booking->client_checked_in_at);
    }

    public function test_a_mistake_can_be_undone_on_the_day(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));
        $this->actingAs($this->pro)->delete(route('bookings.check-in.undo', $booking))->assertRedirect();

        $this->assertNull($booking->fresh()->supplier_checked_in_at);
    }

    public function test_one_side_cannot_undo_the_other(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));

        // The client's undo clears the CLIENT's check-in, which is not set,
        // so it is refused and the professional's stands.
        $this->actingAs($this->client)
            ->delete(route('bookings.check-in.undo', $booking))
            ->assertForbidden();

        $this->assertNotNull($booking->fresh()->supplier_checked_in_at);
    }

    public function test_yesterdays_check_in_is_a_record_and_not_a_button(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));

        Carbon::setTestNow(now()->addDay());

        // After the day it is what happened, not something to tidy up.
        $this->actingAs($this->pro)
            ->delete(route('bookings.check-in.undo', $booking))
            ->assertForbidden();

        $this->assertNotNull($booking->fresh()->supplier_checked_in_at);
    }

    public function test_the_hub_shows_todays_gig_with_the_button(): void
    {
        $this->booking();
        $this->booking('tomorrow');

        $page = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))->assertOk();

        $this->assertCount(1, $page->viewData('liveToday'), 'only today is today');
        $page->assertSee('Happening today');
        $page->assertSee('I am on site');
        $page->assertSee('Nobody has checked in yet');
    }

    public function test_the_client_sees_it_on_their_booking(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->pro)->post(route('bookings.check-in', $booking));

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertOk()
            ->assertSee('The professional has checked in')
            ->assertSee('They have arrived');
    }
}
