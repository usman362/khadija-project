<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Idea 1, continued — the reference in the four places it is needed.
 *
 * Sir Peter listed the messaging panel, transactions and support requests
 * alongside the profile. Each is a place where a name is not enough: two
 * professionals share one, a payment has to be matched to a person, and a
 * support thread is answered by somebody who was not in it.
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
class GigResourceIdIsShownWhereNeededTest extends TestCase
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

    /* ── The other two places Sir Peter named ───────────────── */

    /**
     * A transaction record. A payment query is the case where a name is least
     * use — this is the field support and accounting match a payment to a
     * person with.
     */
    public function test_the_transaction_page_shows_the_professionals_reference(): void
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

        $this->actingAs($client)
            ->get("/client/payments/{$booking->id}")
            ->assertOk()
            ->assertSee('GigResource ID')
            ->assertSee($pro->public_id);
    }

    /**
     * A support request. The form already carries its own reference; this one
     * identifies the person, which is the half staff otherwise look up.
     */
    public function test_a_support_request_shows_the_senders_reference(): void
    {
        $client = $this->client();

        $submission = \App\Models\FormSubmission::create([
            'form_key' => 'support_request',
            'submitted_by' => $client->id,
            'payload' => ['subject' => 'A question', 'message' => 'Hello'],
            'status' => 'open',
        ]);

        $this->actingAs($client)
            ->get("/requests-submissions/{$submission->id}")
            ->assertOk()
            ->assertSee('GigResource ID')
            ->assertSee($client->public_id);
    }

    /**
     * The trap that cost the chat panel its first run, kept as its own test:
     * a narrowed eager load drops public_id and the field renders blank,
     * which reads as "not built" rather than as a bug.
     */
    public function test_a_narrowed_eager_load_still_carries_the_reference(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional'])->fresh();

        $event = Event::create([
            'title' => 'An event', 'client_id' => $this->client()->id,
            'created_by' => $pro->id, 'status' => 'open',
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $event->client_id,
            'created_by' => $event->client_id, 'supplier_id' => $pro->id,
            'status' => 'confirmed', 'price' => 100, 'currency' => 'USD',
        ]);

        $loaded = Booking::with('supplier:id,name,avatar,public_id')->find($booking->id);

        $this->assertSame($pro->public_id, $loaded->supplier->public_id);
    }
}
