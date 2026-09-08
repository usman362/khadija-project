<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Idea 1, continued — the reference where a client actually needs it.
 *
 * Sir Peter listed the messaging information panel among the places the
 * GigResource ID should appear, and it is the strongest of them: two
 * professionals with the same name are told apart in the conversation, not on
 * a profile page somebody has to go and find, and it is the first thing
 * support asks for when a thread turns into a dispute.
 *
 * Placed above the email because it is the one field on that panel that never
 * changes.
 */
class ChatShowsGigResourceIdTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    public function test_the_panel_shows_the_professionals_reference(): void
    {
        $client = $this->client();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro = $pro->fresh();

        $event = Event::create([
            'title' => 'An event', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'open',
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id,
            'created_by' => $client->id, 'supplier_id' => $pro->id,
            'status' => 'confirmed', 'price' => 100, 'currency' => 'USD',
        ]);

        $conversation = Conversation::create([
            'created_by' => $client->id,
            'booking_id' => $booking->id,
            'event_id' => $event->id,
            'type' => 'direct',
        ]);

        $conversation->participants()->sync([$client->id, $pro->id]);

        $this->actingAs($client)
            ->get("/client/messages/{$conversation->id}")
            ->assertOk()
            ->assertSee('GigResource ID')
            ->assertSee($pro->public_id);
    }

    /**
     * And it is the OTHER party's, never the reader's own — a panel about the
     * person you are talking to that showed your own reference would be worse
     * than no panel.
     */
    public function test_it_is_the_other_party_not_yourself(): void
    {
        $client = $this->client();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro = $pro->fresh();

        $event = Event::create([
            'title' => 'An event', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'open',
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id,
            'created_by' => $client->id, 'supplier_id' => $pro->id,
            'status' => 'confirmed', 'price' => 100, 'currency' => 'USD',
        ]);

        $conversation = Conversation::create([
            'created_by' => $client->id, 'booking_id' => $booking->id,
            'event_id' => $event->id, 'type' => 'direct',
        ]);

        $conversation->participants()->sync([$client->id, $pro->id]);

        $info = $this->actingAs($client)
            ->get("/client/messages/{$conversation->id}")
            ->assertOk()
            ->viewData('info');

        $this->assertSame($pro->public_id, $info['public_id']);
        $this->assertNotSame($client->public_id, $info['public_id']);
    }
}
