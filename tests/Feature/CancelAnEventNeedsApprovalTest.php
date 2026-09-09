<?php

namespace Tests\Feature;

use App\Models\{CancellationRequest, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A client can ask to cancel a whole event, and an administrator decides.
 *
 * Sir Peter / Ali, 2026-09-09. Two things were missing:
 *
 *  · booking_id was NOT NULL, so a request posted to the board and never taken
 *    up could not be cancelled at all — there was no booking to point at, and
 *    the only way out was to leave it open.
 *  · Raising any cancellation told the client "our team will follow up" and
 *    there was no screen for the team to follow up on: status only moved
 *    between submitted and withdrawn, and actioned_by, actioned_at and
 *    resolution_note were columns nothing ever wrote.
 */
class CancelAnEventNeedsApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();

        $this->admin = User::factory()->create(['primary_role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->event = Event::create([
            'title' => 'Garden Reception', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'is_published' => true, 'starts_at' => now()->addMonths(3),
        ]);
    }

    private function ask(array $over = [])
    {
        return $this->actingAs($this->client)->post(route('cancellations.store'), array_merge([
            'kind'      => CancellationRequest::CLIENT_CANCELS_EVENT,
            'event_id'  => $this->event->id,
            'reason'    => 'The venue fell through and we are not going ahead this year.',
            'certified' => 1,
        ], $over));
    }

    public function test_a_client_can_ask_to_cancel_an_event_with_no_booking(): void
    {
        $this->ask()->assertSessionHasNoErrors();

        $cancellation = CancellationRequest::firstWhere('event_id', $this->event->id);

        $this->assertNotNull($cancellation);
        $this->assertNull($cancellation->booking_id, 'An event cancellation invented a booking.');
        $this->assertSame(CancellationRequest::SUBMITTED, $cancellation->status);
    }

    /** Nothing is cancelled by asking — that is what makes it an approval. */
    public function test_the_event_carries_on_until_someone_approves(): void
    {
        $this->ask();

        $this->assertSame('published', $this->event->fresh()->status);
        $this->assertTrue((bool) $this->event->fresh()->is_published);
    }

    public function test_approving_takes_the_event_down(): void
    {
        $this->ask();
        $cancellation = CancellationRequest::firstWhere('event_id', $this->event->id);

        $this->actingAs($this->admin)
            ->post(route('app.admin.cancellations.approve', $cancellation), [
                'resolution_note' => 'Confirmed with the client.',
            ])
            ->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertSame('cancelled', $event->status);
        // Both columns, because a cancelled event that still says published
        // shows as open on every list that reads the flag.
        $this->assertFalse((bool) $event->is_published);
        $this->assertSame('cancelled', $event->stage());

        $cancellation->refresh();
        $this->assertSame(CancellationRequest::APPROVED, $cancellation->status);
        $this->assertSame($this->admin->id, $cancellation->actioned_by);
        $this->assertNotNull($cancellation->actioned_at);
    }

    public function test_declining_leaves_the_event_exactly_as_it_was(): void
    {
        $this->ask();
        $cancellation = CancellationRequest::firstWhere('event_id', $this->event->id);

        $this->actingAs($this->admin)
            ->post(route('app.admin.cancellations.decline', $cancellation), [
                'resolution_note' => 'Two professionals are mid-proposal — speak to them first.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('published', $this->event->fresh()->status);
        $this->assertSame(CancellationRequest::DECLINED, $cancellation->fresh()->status);
    }

    /** Being told no without being told why is what becomes a support ticket. */
    public function test_a_decline_has_to_say_why(): void
    {
        $this->ask();
        $cancellation = CancellationRequest::firstWhere('event_id', $this->event->id);

        $this->actingAs($this->admin)
            ->post(route('app.admin.cancellations.decline', $cancellation), [])
            ->assertSessionHasErrors('resolution_note');

        $this->assertSame(CancellationRequest::SUBMITTED, $cancellation->fresh()->status);
    }

    public function test_only_your_own_event(): void
    {
        $other = User::factory()->create(['primary_role' => 'client']);
        $other->assignRole('client');
        $other->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->actingAs($other->fresh())
            ->post(route('cancellations.store'), [
                'kind' => CancellationRequest::CLIENT_CANCELS_EVENT,
                'event_id' => $this->event->id,
                'reason' => 'I would like this taken down please, thank you.',
                'certified' => 1,
            ])
            ->assertForbidden();
    }

    /** One open ask at a time, so an administrator never sees two for one event. */
    public function test_it_will_not_take_a_second_request_while_one_is_waiting(): void
    {
        $this->ask()->assertSessionHasNoErrors();
        $this->ask()->assertForbidden();

        $this->assertSame(1, CancellationRequest::where('event_id', $this->event->id)->count());
    }

    /** An event that is already over is a dispute, not a cancellation. */
    public function test_a_finished_event_cannot_be_cancelled(): void
    {
        $this->event->update(['status' => 'completed']);

        $this->ask()->assertForbidden();
    }

    /** An administrator cannot action the same one twice. */
    public function test_it_can_only_be_actioned_once(): void
    {
        $this->ask();
        $cancellation = CancellationRequest::firstWhere('event_id', $this->event->id);

        $this->actingAs($this->admin)
            ->post(route('app.admin.cancellations.approve', $cancellation), []);

        $this->actingAs($this->admin)
            ->post(route('app.admin.cancellations.decline', $cancellation), [
                'resolution_note' => 'Changed my mind about this one.',
            ])
            ->assertForbidden();

        $this->assertSame(CancellationRequest::APPROVED, $cancellation->fresh()->status);
    }

    public function test_the_queue_lists_what_is_waiting(): void
    {
        $this->ask();

        $this->actingAs($this->admin)
            ->get(route('app.admin.cancellations.index'))
            ->assertSuccessful()
            ->assertSee('Garden Reception')
            ->assertSee('I need to cancel this event');
    }

    /** And it is a staff screen. */
    public function test_a_client_cannot_open_the_queue(): void
    {
        $this->actingAs($this->client)
            ->get(route('app.admin.cancellations.index'))
            ->assertForbidden();
    }

    /** The client's own list says what it is waiting for. */
    public function test_the_client_sees_what_it_is_waiting_for(): void
    {
        $this->ask();

        $this->actingAs($this->client)
            ->get(route('cancellations.index'))
            ->assertSuccessful()
            ->assertSee('Waiting for approval')
            ->assertSee('Garden Reception');
    }
}
