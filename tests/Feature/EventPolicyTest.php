<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_client_can_create_event(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $response = $this->actingAs($client)->post(route('events.store'), [
            'title' => 'Client event',
            'status' => 'pending',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('events', [
            'title' => 'Client event',
            'client_id' => $client->id,
        ]);
    }

    public function test_supplier_cannot_create_event(): void
    {
        $supplier = User::factory()->create();
        $supplier->assignRole(RoleName::SUPPLIER->value);

        $response = $this->actingAs($supplier)->post(route('events.store'), [
            'title' => 'Supplier event',
        ]);

        $response->assertForbidden();
    }

    public function test_client_cannot_view_other_clients_event(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::CLIENT->value);

        $otherClient = User::factory()->create();
        $otherClient->assignRole(RoleName::CLIENT->value);

        $event = Event::query()->create([
            'title' => 'Private event',
            'status' => 'pending',
            'created_by' => $owner->id,
            'client_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherClient)->get(route('events.show', $event));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_any_event(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMIN->value);

        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $event = Event::query()->create([
            'title' => 'To delete',
            'status' => 'confirmed',
            'created_by' => $client->id,
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('events.destroy', $event));

        $response->assertOk();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
