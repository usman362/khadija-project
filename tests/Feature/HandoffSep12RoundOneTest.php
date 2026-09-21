<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use App\Models\{Booking, Event, User};
use App\Support\{GigResourceId, ServiceArea};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Round one from the Developer Handoff v4 of 12 September. */
class HandoffSep12RoundOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore', 'service_area_status' => ServiceArea::SUPPORTED]);

        return $u->fresh();
    }

    private function selfBooking(User $client): Booking
    {
        $event = Event::create(['title' => 'Test', 'client_id' => $client->id, 'created_by' => $client->id, 'status' => 'published', 'starts_at' => now()->addMonth()]);

        return Booking::withoutEvents(fn () => Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $client->id,
            'created_by' => $client->id, 'status' => 'requested', 'price' => 100,
        ]));
    }

    /** OA-154: an old self-booking no longer shows a raw error, and is not listed. */
    public function test_an_old_self_booking_is_hidden_and_does_not_error(): void
    {
        $client = $this->user('client');
        $b = $this->selfBooking($client);

        $b->update(['status' => 'cancelled']);   // no exception now
        $this->assertSame('cancelled', $b->fresh()->status);

        $this->actingAs($client)->get(route('client.bookings.index'))
            ->assertOk()->assertDontSee('A booking needs a client and a different professional.');
    }

    public function test_the_purge_command_lists_then_deletes(): void
    {
        $b = $this->selfBooking($this->user('client'));

        $this->artisan('bookings:purge-self-referential')->assertSuccessful();
        $this->assertNotNull($b->fresh());

        $this->artisan('bookings:purge-self-referential', ['--force' => true])->assertSuccessful();
        $this->assertNull($b->fresh());
    }

    /**
     * DIR-38 put a "GR-" in front of every ID; Sir Peter took it back off on
     * 2026-09-22, "completely everywhere". Anything stored with it still
     * reads without it.
     */
    public function test_ids_are_shown_without_the_gr_prefix(): void
    {
        $this->assertSame('PRO-123456', GigResourceId::display('PRO-123456'));
        $this->assertSame('CL-123456', GigResourceId::display('GR-CL-123456'));
        $this->assertNull(GigResourceId::display(null));
    }

    /** OA-158: the free tier reads Free. */
    public function test_the_free_tier_is_called_free(): void
    {
        $this->assertSame('Free', config('toolkit-tiers.tiers.manual'));
        $this->assertSame('Free', config('ai-levels.labels.manual'));
    }

    /** DIR-32: Share Your Story is not under Safety & Support, and is offered after a review. */
    public function test_share_your_story_moved_to_after_a_review(): void
    {
        $this->assertNotContains('testimonial', FormRegistry::GROUPS['safety']['keys']);
        $this->assertStringContainsString("FormRegistry::url('testimonial')", file_get_contents(resource_path('views/client/reviews/index.blade.php')));
    }

    /** #74: required fields are marked. */
    public function test_required_fields_are_marked(): void
    {
        $this->actingAs($this->user('client'))->get(route('forms.create', FormRegistry::slugFor('support_request')))
            ->assertOk()->assertSee('<span style="color:#dc2626;" aria-hidden="true"> *</span>', false);
    }

    /** OA-157: Review Builder starts blank. */
    public function test_review_builder_starts_blank(): void
    {
        $this->actingAs($this->user('client'))->get(route('ai-tools.review-writer'))
            ->assertOk()->assertDontSee('value="Sarah Bennett Photography"', false);
    }
}
