<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Agreement;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $supplier;
    private Event $event;
    private Booking $booking;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->client = User::factory()->create();
        $this->client->assignRole(RoleName::CLIENT->value);

        $this->supplier = User::factory()->create();
        $this->supplier->assignRole(RoleName::SUPPLIER->value);

        $this->event = Event::create([
            'title' => 'Test Event',
            'description' => 'A test event',
            'status' => 'draft',
            'is_published' => true,
            'created_by' => $this->client->id,
            'client_id' => $this->client->id,
            'supplier_id' => $this->supplier->id,
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(8),
        ]);

        $this->booking = Booking::create([
            'event_id' => $this->event->id,
            'client_id' => $this->client->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->client->id,
            'status' => 'requested',
            'notes' => 'Test booking',
        ]);

        $this->conversation = Conversation::create([
            'type' => 'booking',
            'booking_id' => $this->booking->id,
            'created_by' => $this->client->id,
        ]);

        $this->conversation->participants()->attach([
            $this->client->id => ['joined_at' => now()],
            $this->supplier->id => ['joined_at' => now()],
        ]);

        // Add some messages to the conversation
        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->client->id,
            'recipient_id' => $this->supplier->id,
            'body' => 'Hi, I need a DJ for my wedding on March 20th. Budget is $500.',
        ]);

        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->supplier->id,
            'recipient_id' => $this->client->id,
            'body' => 'Sounds great! I can do that for $500. I will bring my own equipment.',
        ]);
    }

    // ── Index ─────────────────────────────────────────────

    public function test_agreements_page_loads_for_client(): void
    {
        $response = $this->actingAs($this->client)
            ->get('/app/agreements');

        $response->assertOk();
        $response->assertViewIs('dashboard.agreements.index');
    }

    public function test_agreements_page_loads_for_supplier(): void
    {
        $response = $this->actingAs($this->supplier)
            ->get('/app/agreements');

        $response->assertOk();
    }

    public function test_agreements_page_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/app/agreements');

        $response->assertOk();
    }

    // ── Generate ──────────────────────────────────────────

    public function test_client_can_generate_agreement(): void
    {
        $response = $this->actingAs($this->client)
            ->post("/app/agreements/generate/{$this->booking->id}");

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('agreements', [
            'booking_id' => $this->booking->id,
            'generated_by' => $this->client->id,
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);
    }

    public function test_supplier_can_generate_agreement(): void
    {
        $response = $this->actingAs($this->supplier)
            ->post("/app/agreements/generate/{$this->booking->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('agreements', [
            'booking_id' => $this->booking->id,
            'generated_by' => $this->supplier->id,
        ]);
    }

    public function test_generate_fails_without_conversation_messages(): void
    {
        // Create a booking without conversation messages
        $booking2 = Booking::create([
            'event_id' => $this->event->id,
            'client_id' => $this->client->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->client->id,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($this->client)
            ->post("/app/agreements/generate/{$booking2->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_generate_when_fully_accepted_exists(): void
    {
        Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Existing Agreement',
            'content' => '<p>Test</p>',
            'status' => 'fully_accepted',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->client)
            ->post("/app/agreements/generate/{$this->booking->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── Show ──────────────────────────────────────────────

    public function test_client_can_view_agreement(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Agreement content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->client)
            ->get("/app/agreements/{$agreement->id}");

        $response->assertOk();
        $response->assertViewIs('dashboard.agreements.show');
        $response->assertSee('Test Agreement');
    }

    // ── Accept ────────────────────────────────────────────

    public function test_client_can_accept_agreement(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $agreement->refresh();
        $this->assertNotNull($agreement->client_accepted_at);
        $this->assertEquals('client_accepted', $agreement->status);
    }

    public function test_supplier_can_accept_agreement(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->supplier)
            ->post("/app/agreements/{$agreement->id}/accept");

        $response->assertRedirect();

        $agreement->refresh();
        $this->assertNotNull($agreement->supplier_accepted_at);
        $this->assertEquals('supplier_accepted', $agreement->status);
    }

    public function test_both_parties_accept_makes_fully_accepted(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        // Client accepts first
        $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        // Supplier accepts second
        $this->actingAs($this->supplier)
            ->post("/app/agreements/{$agreement->id}/accept");

        $agreement->refresh();
        $this->assertEquals('fully_accepted', $agreement->status);
        $this->assertNotNull($agreement->client_accepted_at);
        $this->assertNotNull($agreement->supplier_accepted_at);
    }

    public function test_fully_accepted_auto_confirms_booking(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        $this->actingAs($this->supplier)
            ->post("/app/agreements/{$agreement->id}/accept");

        $this->booking->refresh();
        $this->assertEquals('confirmed', $this->booking->status);
    }

    public function test_cannot_accept_twice(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        $response = $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        $response->assertSessionHas('error');
    }

    // ── Reject ────────────────────────────────────────────

    public function test_client_can_reject_agreement(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/reject", [
                'rejection_reason' => 'Terms are not fair',
            ]);

        $response->assertRedirect();

        $agreement->refresh();
        $this->assertEquals('rejected', $agreement->status);
        $this->assertNotNull($agreement->rejected_at);
        $this->assertEquals('Terms are not fair', $agreement->rejection_reason);
    }

    // ── Regenerate ────────────────────────────────────────

    public function test_regenerate_creates_new_version(): void
    {
        // First generate
        $this->actingAs($this->client)
            ->post("/app/agreements/generate/{$this->booking->id}");

        // Regenerate
        $this->actingAs($this->client)
            ->post("/app/agreements/regenerate/{$this->booking->id}");

        $agreements = Agreement::where('booking_id', $this->booking->id)->get();
        $this->assertGreaterThanOrEqual(2, $agreements->count());
    }

    // ── Admin ─────────────────────────────────────────────

    public function test_admin_can_force_accept(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/app/agreements/{$agreement->id}/accept");

        $response->assertRedirect();

        $agreement->refresh();
        $this->assertEquals('fully_accepted', $agreement->status);
        $this->assertNotNull($agreement->client_accepted_at);
        $this->assertNotNull($agreement->supplier_accepted_at);
    }

    // ── Agreement Log ─────────────────────────────────────

    public function test_generate_creates_agreement_log(): void
    {
        $this->actingAs($this->client)
            ->post("/app/agreements/generate/{$this->booking->id}");

        $agreement = Agreement::where('booking_id', $this->booking->id)->first();

        $this->assertDatabaseHas('agreement_logs', [
            'subject_type' => 'agreement',
            'subject_id' => $agreement->id,
            'to_status' => 'pending_review',
            'changed_by' => $this->client->id,
        ]);
    }

    public function test_accept_creates_agreement_log(): void
    {
        $agreement = Agreement::create([
            'booking_id' => $this->booking->id,
            'conversation_id' => $this->conversation->id,
            'generated_by' => $this->client->id,
            'title' => 'Test Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $this->actingAs($this->client)
            ->post("/app/agreements/{$agreement->id}/accept");

        $this->assertDatabaseHas('agreement_logs', [
            'subject_type' => 'agreement',
            'subject_id' => $agreement->id,
            'from_status' => 'pending_review',
            'to_status' => 'client_accepted',
            'changed_by' => $this->client->id,
        ]);
    }

    // ── Model Helpers ─────────────────────────────────────

    public function test_agreement_status_helpers(): void
    {
        $agreement = new Agreement(['status' => 'draft']);
        $this->assertTrue($agreement->isDraft());
        $this->assertFalse($agreement->isFullyAccepted());

        $agreement->status = 'pending_review';
        $this->assertTrue($agreement->isPendingReview());

        $agreement->status = 'fully_accepted';
        $this->assertTrue($agreement->isFullyAccepted());

        $agreement->status = 'rejected';
        $this->assertTrue($agreement->isRejected());
    }

    public function test_agreement_status_labels_and_colors(): void
    {
        $agreement = new Agreement(['status' => 'pending_review']);
        $this->assertEquals('Pending Review', $agreement->statusLabel());
        $this->assertEquals('warning', $agreement->statusColor());

        $agreement->status = 'fully_accepted';
        $this->assertEquals('Fully Accepted', $agreement->statusLabel());
        $this->assertEquals('success', $agreement->statusColor());

        $agreement->status = 'rejected';
        $this->assertEquals('Rejected', $agreement->statusLabel());
        $this->assertEquals('danger', $agreement->statusColor());
    }

    // ── Status Filter ─────────────────────────────────────

    public function test_agreements_can_be_filtered_by_status(): void
    {
        Agreement::create([
            'booking_id' => $this->booking->id,
            'generated_by' => $this->client->id,
            'title' => 'Pending Agreement',
            'content' => '<p>Content</p>',
            'status' => 'pending_review',
            'version' => 1,
            'source' => 'ai',
        ]);

        $response = $this->actingAs($this->client)
            ->get('/app/agreements?status=pending_review');

        $response->assertOk();
        $response->assertSee('Pending Agreement');
    }
}
