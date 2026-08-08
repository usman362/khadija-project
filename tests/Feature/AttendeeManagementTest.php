<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R60, locked 2026-08-07 — Attendee Management belongs to an event.
 *
 * It replaces a dashboard widget that was an account-wide flat table:
 * 120/75/10/35 hardcoded, five invented guests with invented email addresses,
 * and Add / Import / edit / delete buttons wired to nothing. The fabrication
 * was bad; the shape was worse. A client running two weddings the same month
 * had one undifferentiated list with nothing saying which guest was for which
 * event — Developer Checklist row 223, open since 2026-08-06.
 *
 * R60's answer: the list lives on the event, the dashboard shows a summary
 * that links back, only data with a real event use is collected, and the
 * professional sees it only if the client says so, per event.
 */
class AttendeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo([
            'dashboard.view', 'events.view', 'events.view_any', 'events.update',
        ]);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function event(string $title = 'Harbour Wedding'): Event
    {
        return Event::create([
            'title'      => $title,
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
            'status'     => 'confirmed',
            'starts_at'  => now()->addMonth(),
        ]);
    }

    /** Book the professional onto the event, so they are the one working it. */
    private function award(Event $event): void
    {
        $event->update(['supplier_id' => $this->pro->id]);
        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 900, 'currency' => 'USD',
        ]);
    }

    public function test_a_guest_is_added_to_one_event(): void
    {
        $event = $this->event();

        $this->actingAs($this->client)
            ->post(route('client.attendees.store', $event), ['name' => 'Sarah Johnson'])
            ->assertRedirect();

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id, 'name' => 'Sarah Johnson', 'rsvp_status' => 'no_response',
        ]);
    }

    public function test_two_events_keep_separate_lists(): void
    {
        // The whole of row 223 in one test. Under the old widget both of these
        // landed in the same undifferentiated table.
        $first  = $this->event('June Wedding');
        $second = $this->event('September Wedding');

        $this->actingAs($this->client)->post(route('client.attendees.store', $first), ['name' => 'Guest A']);
        $this->actingAs($this->client)->post(route('client.attendees.store', $second), ['name' => 'Guest B']);

        $this->assertSame(['Guest A'], $first->attendees()->pluck('name')->all());
        $this->assertSame(['Guest B'], $second->attendees()->pluck('name')->all());
    }

    public function test_the_summary_counts_the_three_rsvp_states(): void
    {
        $event = $this->event();

        foreach ([['A', 'confirmed'], ['B', 'confirmed'], ['C', 'cancelled'], ['D', 'no_response']] as [$name, $status]) {
            $event->attendees()->create(['name' => $name, 'rsvp_status' => $status]);
        }

        $this->assertSame(
            ['total' => 4, 'confirmed' => 2, 'cancelled' => 1, 'no_response' => 1],
            EventAttendee::summaryFor($event),
        );
    }

    public function test_the_rsvp_can_be_changed(): void
    {
        $event = $this->event();
        $guest = $event->attendees()->create(['name' => 'Sarah Johnson']);

        $this->actingAs($this->client)->patch(
            route('client.attendees.update', [$event, $guest]),
            ['name' => 'Sarah Johnson', 'rsvp_status' => 'confirmed'],
        );

        $this->assertSame('confirmed', $guest->fresh()->rsvp_status);
    }

    public function test_a_guest_can_be_removed(): void
    {
        $event = $this->event();
        $guest = $event->attendees()->create(['name' => 'Sarah Johnson']);

        $this->actingAs($this->client)
            ->delete(route('client.attendees.destroy', [$event, $guest]));

        $this->assertDatabaseMissing('event_attendees', ['id' => $guest->id]);
    }

    public function test_an_attendee_cannot_be_edited_through_a_different_event(): void
    {
        // Both ids are in the URL and nothing else relates them. Without the
        // check, someone else's guest is editable through an event you own.
        $mine    = $this->event('Mine');
        $theirs  = $this->event('Theirs');
        $guest   = $theirs->attendees()->create(['name' => 'Not Yours']);

        $this->actingAs($this->client)
            ->patch(route('client.attendees.update', [$mine, $guest]), ['name' => 'Renamed'])
            ->assertNotFound();

        $this->assertSame('Not Yours', $guest->fresh()->name);
    }

    public function test_another_client_cannot_touch_this_guest_list(): void
    {
        $event = $this->event();
        $stranger = $this->account('client');

        $this->actingAs($stranger)
            ->post(route('client.attendees.store', $event), ['name' => 'Intruder'])
            ->assertForbidden();

        $this->assertSame(0, $event->attendees()->count());
    }

    public function test_a_pasted_list_is_imported(): void
    {
        $event = $this->event();

        $this->actingAs($this->client)->post(route('client.attendees.import', $event), [
            'list' => "Sarah Johnson, sarah@example.com, 555 0134\nMichael Brown\n\nEmily Davis, emily@example.com",
        ]);

        $this->assertSame(3, $event->attendees()->count());
        $this->assertSame('555 0134', $event->attendees()->where('name', 'Sarah Johnson')->value('phone'));
        $this->assertNull($event->attendees()->where('name', 'Michael Brown')->value('email'));
    }

    public function test_one_bad_address_does_not_lose_the_whole_paste(): void
    {
        // A typo in row 40 should not cost the client the other 39 names.
        $event = $this->event();

        $this->actingAs($this->client)->post(route('client.attendees.import', $event), [
            'list' => "Good Guest, good@example.com\nTypo Guest, not-an-email\nAnother, fine@example.com",
        ]);

        $this->assertSame(3, $event->attendees()->count());
        $this->assertNull($event->attendees()->where('name', 'Typo Guest')->value('email'));
    }

    /* ── Professional access ───────────────────────────────── */

    public function test_the_guest_list_is_private_by_default(): void
    {
        $event = $this->event();
        $event->attendees()->create(['name' => 'Sarah Johnson']);
        $this->award($event);

        $this->assertFalse($event->fresh()->share_attendees);

        $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $event))
            ->assertSuccessful()
            ->assertDontSee('Sarah Johnson');
    }

    public function test_the_client_can_share_it_and_take_it_back(): void
    {
        $event = $this->event();
        $event->attendees()->create(['name' => 'Sarah Johnson']);
        $this->award($event);

        $this->actingAs($this->client)
            ->post(route('client.attendees.share', $event), ['share' => '1']);

        $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $event))
            ->assertSee('Sarah Johnson');

        // Revocable, which an emailed PDF would not be.
        $this->actingAs($this->client)
            ->post(route('client.attendees.share', $event), ['share' => '0']);

        $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $event))
            ->assertDontSee('Sarah Johnson');
    }

    public function test_a_professional_who_only_bid_never_sees_it(): void
    {
        // Sharing is with the professional working the event, not with
        // everyone who showed interest in it.
        $event = $this->event();
        $event->update(['is_published' => true, 'status' => 'published', 'share_attendees' => true]);
        $event->attendees()->create(['name' => 'Sarah Johnson']);

        $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $event))
            ->assertSuccessful()
            ->assertDontSee('Sarah Johnson');
    }

    /* ── The dashboard widget it replaces ──────────────────── */

    public function test_the_dashboard_shows_a_summary_per_event_not_a_flat_list(): void
    {
        $event = $this->event('Harbour Wedding');
        $event->attendees()->create(['name' => 'Sarah Johnson', 'rsvp_status' => 'confirmed']);

        $page = $this->actingAs($this->client)->get(route('client.dashboard'));

        $page->assertSuccessful();
        // The event is named, and the row links to that event's own list.
        $page->assertSee('Harbour Wedding');
        $page->assertSee(route('client.events.show', ['event' => $event->id, 'tab' => 'attendees']), false);
        // A guest's name has no business on the dashboard.
        $page->assertDontSee('Sarah Johnson');
    }

    public function test_the_dashboard_no_longer_carries_the_invented_guests(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.dashboard'));

        foreach (['sarah.j@email.com', 'michael.b@email.com', 'jessica.t@email.com'] as $invented) {
            $page->assertDontSee($invented);
        }
    }

    public function test_a_client_with_no_guest_lists_is_told_where_to_add_them(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->assertSee('each event keeps its own list');
    }

    public function test_the_event_page_has_an_attendees_tab(): void
    {
        $event = $this->event();
        $event->attendees()->create(['name' => 'Sarah Johnson']);

        $this->actingAs($this->client)
            ->get(route('client.events.show', ['event' => $event, 'tab' => 'attendees']))
            ->assertSuccessful()
            ->assertSee('Guest list')
            ->assertSee('Sarah Johnson');
    }

    public function test_only_data_with_an_event_use_is_accepted(): void
    {
        // R60's purpose test. There is no address column and no free-text
        // notes column, because an event does not need either — collecting
        // personal data with nowhere to send it is the defect the rule names.
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('event_attendees');

        $this->assertSame([
            'id', 'event_id', 'name', 'email', 'phone',
            'rsvp_status', 'dietary', 'accessibility',
            'created_at', 'updated_at',
        ], $columns);
    }
}
