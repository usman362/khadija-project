<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->client = User::factory()->create();
        $this->client->assignRole(RoleName::CLIENT->value);

        $this->supplier = User::factory()->create();
        $this->supplier->assignRole(RoleName::SUPPLIER->value);
    }

    public function test_user_can_create_direct_conversation(): void
    {
        $response = $this->actingAs($this->client)
            ->postJson('/conversations', [
                'type' => 'direct',
                'participant_ids' => [$this->supplier->id],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('type', 'direct');

        $this->assertDatabaseHas('conversations', ['type' => 'direct']);
        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $this->client->id,
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $this->supplier->id,
        ]);
    }

    public function test_duplicate_direct_conversation_returns_existing(): void
    {
        // Create first
        $this->actingAs($this->client)
            ->postJson('/conversations', [
                'type' => 'direct',
                'participant_ids' => [$this->supplier->id],
            ]);

        // Create duplicate — should return same conversation
        $response = $this->actingAs($this->client)
            ->postJson('/conversations', [
                'type' => 'direct',
                'participant_ids' => [$this->supplier->id],
            ]);

        $response->assertOk();
        $this->assertEquals(1, Conversation::count());
    }

    public function test_user_can_send_message_in_conversation(): void
    {
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->client->id,
        ]);
        $conv->addParticipant($this->client);
        $conv->addParticipant($this->supplier);

        $response = $this->actingAs($this->client)
            ->postJson("/conversations/{$conv->id}/messages", [
                'body' => 'Hello from client!',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('body', 'Hello from client!');
        $response->assertJsonPath('sender_id', $this->client->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'body' => 'Hello from client!',
        ]);
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->client->id,
        ]);
        $conv->addParticipant($this->client);
        $conv->addParticipant($this->admin);

        // Supplier is NOT a participant
        $response = $this->actingAs($this->supplier)
            ->getJson("/conversations/{$conv->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_conversation(): void
    {
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->client->id,
        ]);
        $conv->addParticipant($this->client);
        $conv->addParticipant($this->supplier);

        // Admin is NOT a participant but can view due to Gate::before
        $response = $this->actingAs($this->admin)
            ->getJson("/conversations/{$conv->id}");

        $response->assertOk();
    }

    public function test_non_participant_cannot_send_message(): void
    {
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->client->id,
        ]);
        $conv->addParticipant($this->client);
        $conv->addParticipant($this->admin);

        $response = $this->actingAs($this->supplier)
            ->postJson("/conversations/{$conv->id}/messages", [
                'body' => 'Intruder!',
            ]);

        $response->assertForbidden();
    }

    public function test_mark_as_read(): void
    {
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->client->id,
        ]);
        $conv->addParticipant($this->client);
        $conv->addParticipant($this->supplier);

        // Client sends a message
        $this->actingAs($this->client)
            ->postJson("/conversations/{$conv->id}/messages", [
                'body' => 'Read this!',
            ]);

        // Supplier marks as read
        $response = $this->actingAs($this->supplier)
            ->postJson("/conversations/{$conv->id}/read");

        $response->assertOk();
        $response->assertJsonPath('read_count', 1);

        $this->assertDatabaseHas('message_reads', [
            'user_id' => $this->supplier->id,
        ]);
    }

    public function test_conversation_list_returns_user_conversations_only(): void
    {
        // Conv between client and supplier
        $conv1 = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $conv1->addParticipant($this->client);
        $conv1->addParticipant($this->supplier);

        // Conv between client and admin
        $conv2 = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $conv2->addParticipant($this->client);
        $conv2->addParticipant($this->admin);

        // Supplier should see only conv1
        $response = $this->actingAs($this->supplier)
            ->getJson('/conversations');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($conv1->id, $response->json('data.0.id'));
    }

    public function test_chat_page_loads(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/chat');

        $response->assertOk();
        $response->assertViewIs('dashboard.chat.index');
    }

    public function test_old_messages_route_redirects_to_chat(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/messages');

        $response->assertRedirect('/app/chat');
    }
}
