<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use App\Domain\Requests\RequestLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B6 — a professional awarded two services on one request must get two
 * bookings, not one.
 *
 * Bids always carried their service (`bids` is unique on
 * event+supplier+category). Bookings and finalizations threw it away and keyed
 * on event+supplier, so the second award to the same pro either vanished
 * (firstOrCreate matched the first) or overwrote the first one's price
 * (updateOrCreate). Reachable today: multi-service requests take per-service
 * bids. These guard the service dimension at both award paths, and guard the
 * whole-event (null) case against regressing into duplicates.
 */
class MultiServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->givePermissionTo('dashboard.view');
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create();
        $this->pro->assignRole('professional');
    }

    private function event(): Event
    {
        return Event::create([
            'title'      => 'Two-service wedding',
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addMonth(),
        ]);
    }

    private function service(string $name): Category
    {
        return Category::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true]);
    }

    /** Post a multi-service request: it asks for these services. */
    private function requesting(Event $e, Category ...$services): Event
    {
        $e->categories()->sync(collect($services)->pluck('id')->all());
        $e->update(['is_published' => true, 'status' => 'published']);

        return $e->fresh();
    }

    private function bid(Event $e, Category $svc, float $amount): Bid
    {
        return Bid::create([
            'event_id'    => $e->id,
            'supplier_id' => $this->pro->id,
            'category_id' => $svc->id,
            'amount'      => $amount,
            'status'      => 'submitted',
        ]);
    }

    /** The defect itself: two services, one pro, two bookings with two prices. */
    public function test_winning_two_services_creates_two_bookings_with_their_own_prices(): void
    {
        $event = $this->event();
        $photo = $this->bid($event, $this->service('Photography'), 2400);
        $cater = $this->bid($event, $this->service('Catering'), 900);

        $this->actingAs($this->client)->post(route('client.proposals.accept', $photo))->assertRedirect();
        $this->actingAs($this->client)->post(route('client.proposals.accept', $cater))->assertRedirect();

        $bookings = Booking::where('event_id', $event->id)->where('supplier_id', $this->pro->id)->get();

        $this->assertCount(2, $bookings, 'Each awarded service should be its own booking.');
        $this->assertEqualsCanonicalizing(
            [2400.0, 900.0],
            $bookings->pluck('price')->map(fn ($p) => (float) $p)->all(),
            'Neither award may overwrite the price of the other.'
        );
        $this->assertEqualsCanonicalizing(
            [$photo->category_id, $cater->category_id],
            $bookings->pluck('category_id')->all(),
        );
    }

    /** Finalizing the second service must not reuse the first's finalization. */
    public function test_two_services_start_two_finalizations(): void
    {
        $event = $this->event();
        $photo = $this->bid($event, $this->service('Photography'), 2400);
        $cater = $this->bid($event, $this->service('Catering'), 900);

        $this->actingAs($this->client)->post(route('client.finalize.start', $photo))->assertRedirect();
        $this->actingAs($this->client)->post(route('client.finalize.start', $cater))->assertRedirect();

        $fins = Finalization::where('event_id', $event->id)->where('supplier_id', $this->pro->id)->get();

        $this->assertCount(2, $fins, 'Each service is a separate agreement to finalize.');
        $this->assertEqualsCanonicalizing(
            [2400.0, 900.0],
            $fins->pluck('agreed_price')->map(fn ($p) => (float) $p)->all(),
        );
    }

    /** Accepting the same award twice is still one booking (idempotent). */
    public function test_accepting_one_award_twice_is_one_booking(): void
    {
        $event = $this->event();
        $bid   = $this->bid($event, $this->service('Photography'), 2400);

        $this->actingAs($this->client)->post(route('client.proposals.accept', $bid))->assertRedirect();
        $this->actingAs($this->client)->post(route('client.proposals.accept', $bid))->assertRedirect();

        $this->assertSame(1, Booking::where('event_id', $event->id)->where('supplier_id', $this->pro->id)->count());
    }

    /**
     * A whole-event (SSR) award carries no service. Two such awards to the same
     * pro on one event must still collapse to one booking -- the null case must
     * not become a duplicate-maker now that category is in the key.
     */
    public function test_whole_event_awards_stay_a_single_booking(): void
    {
        $event = $this->event();
        $bid   = Bid::create([
            'event_id'    => $event->id,
            'supplier_id' => $this->pro->id,
            'category_id' => null,           // SSR: whole-event bid
            'amount'      => 5000,
            'status'      => 'submitted',
        ]);

        $this->actingAs($this->client)->post(route('client.proposals.accept', $bid))->assertRedirect();
        $this->actingAs($this->client)->post(route('client.proposals.accept', $bid))->assertRedirect();

        $rows = Booking::where('event_id', $event->id)->where('supplier_id', $this->pro->id)->get();
        $this->assertCount(1, $rows);
        $this->assertNull($rows->first()->category_id);
    }

    // ── Awarding one service must not close the request (B6/A10) ──

    /**
     * The bug B6 exposed: with per-service awards, stamping the event's single
     * supplier_id on the FIRST award hid every still-open service. A two-service
     * request with one service awarded must stay open for the other.
     */
    public function test_awarding_one_of_two_services_keeps_the_request_open(): void
    {
        $event = $this->requesting($this->event(), $photo = $this->service('Photography'), $cater = $this->service('Catering'));
        $photoBid = $this->bid($event, $photo, 2400);
        $this->bid($event, $cater, 900);

        $this->actingAs($this->client)->post(route('client.proposals.accept', $photoBid))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->supplier_id, 'A half-awarded request must not be stamped to one pro.');
        $this->assertFalse($event->isFullyAwarded());
        $this->assertSame(RequestLifecycle::OPEN, RequestLifecycle::statusFor($event),
            'The catering service is still open for bids.');
    }

    /** When the last service is taken by the same pro, the request is awarded. */
    public function test_awarding_both_services_to_one_pro_closes_the_request(): void
    {
        $event = $this->requesting($this->event(), $photo = $this->service('Photography'), $cater = $this->service('Catering'));

        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid($event, $photo, 2400)));
        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid($event, $cater, 900)));

        $event->refresh();
        $this->assertTrue($event->isFullyAwarded());
        $this->assertSame($this->pro->id, $event->supplier_id, 'One pro won everything, so the event names them.');
        $this->assertSame(RequestLifecycle::AWARDED, RequestLifecycle::statusFor($event));
    }

    /**
     * Two pros, two services. Fully awarded, but no single supplier_id can name
     * both -- it stays null, and the request is closed by its bookings, not by a
     * stamped supplier. (The board still showing such an event is the logged
     * A10 remainder; the acute one-award-hides-the-rest bug is fixed.)
     */
    public function test_two_pros_splitting_the_services_is_not_stamped_to_one(): void
    {
        $event = $this->requesting($this->event(), $photo = $this->service('Photography'), $cater = $this->service('Catering'));

        $other = User::factory()->create();
        $other->assignRole('professional');

        $catererBid = Bid::create([
            'event_id' => $event->id, 'supplier_id' => $other->id,
            'category_id' => $cater->id, 'amount' => 900, 'status' => 'submitted',
        ]);

        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid($event, $photo, 2400)));
        $this->actingAs($this->client)->post(route('client.proposals.accept', $catererBid));

        $event->refresh();
        $this->assertTrue($event->isFullyAwarded());
        $this->assertNull($event->supplier_id, 'Two winners cannot be named by one column.');
    }

    /** A plain single-service award still stamps and closes, exactly as before. */
    public function test_a_single_service_award_still_closes_the_request(): void
    {
        $event = $this->requesting($this->event(), $photo = $this->service('Photography'));

        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid($event, $photo, 2400)));

        $event->refresh();
        $this->assertSame($this->pro->id, $event->supplier_id);
        $this->assertSame(RequestLifecycle::AWARDED, RequestLifecycle::statusFor($event));
    }
}
