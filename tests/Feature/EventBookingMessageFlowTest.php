<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventBookingMessageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_event_publish_booking_and_message_flow_works(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $supplier = User::factory()->create();
        $supplier->assignRole(RoleName::SUPPLIER->value);

        $createEvent = $this->actingAs($client)->post(route('events.store'), [
            'title' => 'Wedding Event',
            'description' => 'Main event',
            'supplier_id' => $supplier->id,
        ]);

        $createEvent->assertCreated();
        $eventId = (int) $createEvent->json('id');

        $publishEvent = $this->actingAs($client)->post(route('events.publish', $eventId));
        $publishEvent->assertOk();

        $booking = $this->actingAs($client)->post(route('bookings.store'), [
            'event_id' => $eventId,
            'notes' => 'Need full package',
        ]);

        $booking->assertCreated();
        $bookingId = (int) $booking->json('id');

        $message = $this->actingAs($client)->post(route('messages.store'), [
            'booking_id' => $bookingId,
            'body' => 'Please share package details.',
        ]);

        $message->assertCreated();

        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'event_id' => $eventId,
            'status' => 'requested',
        ]);

        $this->assertDatabaseHas('messages', [
            'booking_id' => $bookingId,
            'body' => 'Please share package details.',
        ]);
    }

    public function test_user_cannot_list_other_peoples_messages(): void
    {
        $clientA = User::factory()->create();
        $clientA->assignRole(RoleName::CLIENT->value);

        $clientB = User::factory()->create();
        $clientB->assignRole(RoleName::CLIENT->value);

        $supplier = User::factory()->create();
        $supplier->assignRole(RoleName::SUPPLIER->value);

        $event = $this->actingAs($clientA)->post(route('events.store'), [
            'title' => 'Private Event',
            'supplier_id' => $supplier->id,
        ])->json();

        $this->actingAs($clientA)->post(route('events.publish', (int) $event['id']))->assertOk();

        $booking = $this->actingAs($clientA)->post(route('bookings.store'), [
            'event_id' => (int) $event['id'],
        ])->json();

        $this->actingAs($clientA)->post(route('messages.store'), [
            'booking_id' => (int) $booking['id'],
            'body' => 'Client A message',
        ])->assertCreated();

        $listForClientB = $this->actingAs($clientB)->get(route('messages.index'));

        $listForClientB->assertOk();
        $this->assertCount(0, $listForClientB->json('data'));
    }

    public function test_event_details_api_returns_full_event_with_bookings_and_messages(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $supplier = User::factory()->create();
        $supplier->assignRole(RoleName::SUPPLIER->value);

        $createEvent = $this->actingAs($client)->post(route('events.store'), [
            'title' => 'Detail Test Event',
            'supplier_id' => $supplier->id,
        ]);
        $createEvent->assertCreated();
        $eventId = (int) $createEvent->json('id');

        $this->actingAs($client)->post(route('events.publish', $eventId))->assertOk();

        $booking = $this->actingAs($client)->post(route('bookings.store'), [
            'event_id' => $eventId,
            'notes' => 'Test booking',
        ]);
        $booking->assertCreated();
        $bookingId = (int) $booking->json('id');

        $this->actingAs($client)->post(route('messages.store'), [
            'event_id' => $eventId,
            'booking_id' => $bookingId,
            'body' => 'Test message body',
        ])->assertCreated();

        $details = $this->actingAs($client)->get(route('events.details', $eventId));
        $details->assertOk();
        $data = $details->json();
        $this->assertSame('Detail Test Event', $data['title']);
        $this->assertArrayHasKey('bookings', $data);
        $this->assertCount(1, $data['bookings']);
        $this->assertSame($bookingId, $data['bookings'][0]['id']);
        $this->assertArrayHasKey('messages', $data);
        $this->assertGreaterThanOrEqual(1, count($data['messages']));
    }

    public function test_agreement_log_entry_created_on_booking_create_and_status_change(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $supplier = User::factory()->create();
        $supplier->assignRole(RoleName::SUPPLIER->value);

        $event = $this->actingAs($client)->post(route('events.store'), [
            'title' => 'Agreement Log Event',
            'supplier_id' => $supplier->id,
        ])->json();
        $this->actingAs($client)->post(route('events.publish', (int) $event['id']))->assertOk();

        $this->assertDatabaseCount('agreement_log', 0);

        $booking = $this->actingAs($client)->post(route('bookings.store'), [
            'event_id' => (int) $event['id'],
        ])->json();
        $bookingId = (int) $booking['id'];

        $this->assertDatabaseHas('agreement_log', [
            'subject_type' => 'booking',
            'subject_id' => $bookingId,
            'from_status' => null,
            'to_status' => 'requested',
            'changed_by' => $client->id,
        ]);

        $this->actingAs($supplier)->patch(route('bookings.update', $bookingId), [
            'status' => 'confirmed',
        ])->assertOk();

        $this->assertDatabaseHas('agreement_log', [
            'subject_type' => 'booking',
            'subject_id' => $bookingId,
            'from_status' => 'requested',
            'to_status' => 'confirmed',
            'changed_by' => $supplier->id,
        ]);
    }
}
