<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client inbox was missing what the professional one had. These cover the
 * pieces that carry real data — the details panel and the event filter — plus
 * two labels on the professional side that were saying things the data did not.
 */
class ChatParityTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        foreach ([['client', 'client'], ['pro', 'supplier']] as [$prop, $role]) {
            $u = User::factory()->create();
            $u->assignRole($role);
            $u->givePermissionTo('dashboard.view');
            $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
            $this->$prop = $u->fresh();
        }
    }

    /** A conversation between the two, optionally about a confirmed booking. */
    private function conversation(bool $withBooking = false): Conversation
    {
        $event = Event::create([
            'title'      => 'Garden Wedding',
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
            'starts_at'  => now()->addMonth(),
        ]);

        $booking = $withBooking ? Booking::create([
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'event_id'    => $event->id,
            'created_by'  => $this->client->id,
            'status'      => 'confirmed',
            'price'       => 3200,
        ]) : null;

        $c = Conversation::create([
            'type'       => $booking ? 'booking' : 'direct',
            'event_id'   => $event->id,
            'booking_id' => $booking?->id,
            'created_by' => $this->client->id,
        ]);
        $c->participants()->sync([$this->client->id, $this->pro->id]);

        return $c->fresh();
    }

    public function test_client_inbox_shows_who_they_are_talking_to(): void
    {
        $this->conversation();

        $info = $this->actingAs($this->client)
            ->get(route('client.chat.index'))
            ->assertSuccessful()
            ->viewData('info');

        $this->assertSame($this->pro->name, $info['name']);
        $this->assertSame($this->pro->email, $info['email']);
        $this->assertNull($info['booking'], 'a direct conversation has no booking to show');
    }

    public function test_client_details_panel_carries_the_real_booking(): void
    {
        $this->conversation(withBooking: true);

        $info = $this->actingAs($this->client)
            ->get(route('client.chat.index'))
            ->viewData('info');

        $this->assertNotNull($info['booking']);
        $this->assertSame(3200.0, $info['booking']['price']);
        $this->assertSame('Garden Wedding', $info['booking']['title']);
        $this->assertSame(1, $info['bookings'], 'one booking with this professional');
        $this->assertSame(3200.0, $info['spent']);
    }

    public function test_the_event_filter_only_offers_events_that_have_a_conversation(): void
    {
        $this->conversation();

        // An event with no conversation must not appear in the dropdown, or the
        // filter would offer a choice that returns an empty list.
        Event::create([
            'title'      => 'Unrelated Gala',
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
        ]);

        $filters = $this->actingAs($this->client)
            ->get(route('client.chat.index'))
            ->viewData('eventFilters');

        $this->assertCount(1, $filters);
        $this->assertSame('Garden Wedding', $filters[0]['title']);
    }

    public function test_the_professional_tag_says_secure_payment_not_escrow(): void
    {
        $this->conversation(withBooking: true);

        $tags = collect($this->actingAs($this->pro)
            ->get(route('professional.chat.index'))
            ->viewData('conversations'))
            ->first()['tags'];

        $names = array_column($tags, 0);

        $this->assertContains('In Secure Payment', $names);
        $this->assertNotContains('Escrow Active', $names, 'the Escrow badge is retired (Q14)');
    }

    public function test_no_conversation_claims_a_w9_status(): void
    {
        $this->conversation(withBooking: true);

        $tags = collect($this->actingAs($this->pro)
            ->get(route('professional.chat.index'))
            ->viewData('conversations'))
            ->first()['tags'];

        foreach (array_column($tags, 0) as $name) {
            // It read trade_license_verified_at and called it a W-9.
            $this->assertStringNotContainsStringIgnoringCase('W-9', $name);
        }
    }
}
