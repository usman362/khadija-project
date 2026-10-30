<?php

namespace Tests\Feature;

use App\Domain\Messaging\GroupedByEvent;
use App\Domain\Messaging\TidyFinishedThreads;
use App\Models\{Booking, Category, Conversation, DisputeCase, Event, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The left-hand message column, gathered by event and tidied when jobs end.
 *
 * Sir Peter, 30 October: the column "seems to always be full of those that
 * relate to the same project", and he suggested archiving anything a week
 * old. Khadijah's correction is what is built, and she was right twice: a
 * quiet booking can still be live, so age alone would hide the thread
 * somebody needs; and the agreement being signed is where coordination
 * STARTS, so archiving there would hide a thread at the moment it begins to
 * be used.
 *
 * Her rule: archive once the event is completed or the booking is closed or
 * cancelled, provided no dispute is outstanding, and let people archive and
 * restore by hand.
 */
class TheMessageColumnTidiesItselfTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function event(string $title, string $status = 'published'): Event
    {
        return Event::create([
            'title'      => $title,
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => $status,
            'starts_at'  => now()->addDays(20),
        ]);
    }

    /**
     * One supplier per event per service: the table says so, which is why a
     * second booking on one event needs a second professional and service.
     */
    private function booking(Event $event, string $status = 'confirmed', ?User $supplier = null, string $service = 'wedding-dj'): Booking
    {
        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => ($supplier ?? $this->professional)->id,
            'category_id' => Category::firstOrCreate(
                ['slug' => $service],
                ['name' => ucwords(str_replace('-', ' ', $service)), 'kind' => Category::SERVICE, 'is_active' => true],
            )->id,
            'status'      => $status,
            'price'       => 4000,
        ]);
    }

    private function thread(Event $event, ?Booking $booking = null): Conversation
    {
        $c = Conversation::create([
            'type'       => $booking ? 'booking' : 'event',
            'event_id'   => $event->id,
            'booking_id' => $booking?->id,
            'created_by' => $this->client->id,
        ]);
        $c->participants()->attach([$this->client->id, $this->professional->id]);

        Message::create([
            'conversation_id' => $c->id,
            'sender_id'       => $this->client->id,
            'body'            => 'Are we set for the night?',
        ]);

        return $c->fresh();
    }

    private function isArchivedFor(Conversation $c, User $u): bool
    {
        return (bool) $c->participants()->where('users.id', $u->id)->first()?->pivot?->archived_at;
    }

    // ── Gathering by event ───────────────────────────────────

    public function test_threads_about_one_event_sit_together(): void
    {
        $rows = [
            ['event' => ['id' => 1, 'title' => 'Rivera Wedding'], 'sortAt' => 100],
            ['event' => ['id' => 2, 'title' => 'TechCorp Gala'],  'sortAt' => 300],
            ['event' => ['id' => 1, 'title' => 'Rivera Wedding'], 'sortAt' => 500],
        ];

        $grouped = GroupedByEvent::of($rows);

        $this->assertCount(2, $grouped);
        $this->assertSame('Rivera Wedding', $grouped[0]['title'], 'The newest message decides the order.');
        $this->assertCount(2, $grouped[0]['rows']);
        $this->assertSame('TechCorp Gala', $grouped[1]['title']);
    }

    public function test_within_a_group_the_newest_is_first(): void
    {
        $grouped = GroupedByEvent::of([
            ['event' => ['id' => 1, 'title' => 'Rivera Wedding'], 'sortAt' => 100, 'id' => 'old'],
            ['event' => ['id' => 1, 'title' => 'Rivera Wedding'], 'sortAt' => 900, 'id' => 'new'],
        ]);

        $this->assertSame(['new', 'old'], array_column($grouped[0]['rows'], 'id'));
    }

    /**
     * Conversations about no event go last, under their own heading.
     *
     * They are real, and burying them in an "Other" at the top would put the
     * least urgent thing in the most urgent place.
     */
    public function test_threads_about_no_event_go_last(): void
    {
        $grouped = GroupedByEvent::of([
            ['event' => null, 'sortAt' => 9999],
            ['event' => ['id' => 1, 'title' => 'Rivera Wedding'], 'sortAt' => 1],
        ]);

        $this->assertSame('Rivera Wedding', $grouped[0]['title']);
        $this->assertSame(GroupedByEvent::LOOSE, $grouped[1]['title']);
    }

    public function test_the_clients_column_shows_the_event_headings(): void
    {
        $this->thread($this->event('Rivera Wedding'));

        $this->actingAs($this->client)
            ->get(route('client.chat.index'))
            ->assertSuccessful()
            ->assertSee('cm-ev-h', false)
            ->assertSee('Rivera Wedding', false);
    }

    public function test_the_professionals_column_shows_them_too(): void
    {
        $this->thread($this->event('Rivera Wedding'));

        $this->actingAs($this->professional)
            ->get(route('professional.chat.index'))
            ->assertSuccessful()
            ->assertSee('pm-ev-h', false)
            ->assertSee('Rivera Wedding', false);
    }

    // ── Tidying, on what happened ────────────────────────────

    public function test_a_live_booking_is_left_alone(): void
    {
        $event = $this->event('Rivera Wedding');
        $this->thread($event, $this->booking($event));

        $this->assertSame(0, TidyFinishedThreads::run());
    }

    /**
     * Not on the agreement, which was the point Khadijah corrected.
     *
     * A confirmed booking is one with an agreement behind it, and that is
     * where the real coordination starts.
     */
    public function test_a_signed_agreement_does_not_archive_the_thread(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'confirmed'));

        TidyFinishedThreads::run();

        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->client));
    }

    public function test_a_completed_booking_is_put_away_for_both_sides(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'completed'));

        $this->assertSame(1, TidyFinishedThreads::run());

        $c = $c->fresh();
        $this->assertTrue($this->isArchivedFor($c, $this->client));
        $this->assertTrue($this->isArchivedFor($c, $this->professional));
    }

    public function test_a_cancelled_booking_is_too(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'cancelled'));

        TidyFinishedThreads::run();

        $this->assertTrue($this->isArchivedFor($c->fresh(), $this->client));
    }

    /** A request that never became a booking follows its own event. */
    public function test_a_cancelled_request_takes_its_threads_with_it(): void
    {
        $c = $this->thread($this->event('Rivera Wedding', 'cancelled'));

        TidyFinishedThreads::run();

        $this->assertTrue($this->isArchivedFor($c->fresh(), $this->client));
    }

    // ── Khadijah's exception ─────────────────────────────────

    public function test_an_open_dispute_keeps_the_thread_in_the_inbox(): void
    {
        $event   = $this->event('Rivera Wedding');
        $booking = $this->booking($event, 'completed');
        $c       = $this->thread($event, $booking);

        DisputeCase::create([
            'booking_id'      => $booking->id,
            'filed_by'        => $this->client->id,
            'client_id'       => $this->client->id,
            'professional_id' => $this->professional->id,
            'severity'        => 2,
            'taxonomy'        => 'poor_workmanship',
            'summary'         => 'The sound cut out.',
        ]);

        $this->assertSame(0, TidyFinishedThreads::run());
        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->client));
    }

    /**
     * A dispute about one service keeps the whole event's threads to hand.
     *
     * The client arguing about the day needs the day's conversations, not
     * only the one with the supplier being argued with.
     */
    public function test_a_dispute_on_one_booking_holds_the_whole_event(): void
    {
        $event  = $this->event('Rivera Wedding');
        $argued = $this->booking($event, 'completed');
        $other  = $this->booking($event, 'completed', $this->person('professional'), 'florist');

        $florist = $this->thread($event, $other);

        DisputeCase::create([
            'booking_id'      => $argued->id,
            'filed_by'        => $this->client->id,
            'client_id'       => $this->client->id,
            'professional_id' => $this->professional->id,
            'severity'        => 2,
            'taxonomy'        => 'poor_workmanship',
            'summary'         => 'The sound cut out.',
        ]);

        TidyFinishedThreads::run();

        $this->assertFalse($this->isArchivedFor($florist->fresh(), $this->client));
    }

    // ── And it never fights the person ───────────────────────

    /**
     * The sweep runs once per conversation, for ever.
     *
     * Without that, somebody who restores a thread would have it archived
     * again the next night, and would lose.
     */
    public function test_a_restored_thread_stays_restored(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'completed'));

        TidyFinishedThreads::run();

        // They pull it back.
        $this->actingAs($this->client)
            ->post(route('conversations.archive', $c))
            ->assertRedirect();

        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->client));

        $this->assertSame(0, TidyFinishedThreads::run(), 'The sweep should not come back for it.');
        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->client));
    }

    /** A thread somebody had already filed away keeps the date they filed it. */
    public function test_an_earlier_hand_archive_is_not_overwritten(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'completed'));

        $this->actingAs($this->client)->post(route('conversations.archive', $c));
        $theirs = $c->fresh()->participants()->where('users.id', $this->client->id)->first()->pivot->archived_at;

        TidyFinishedThreads::run();

        $this->assertEquals(
            $theirs,
            $c->fresh()->participants()->where('users.id', $this->client->id)->first()->pivot->archived_at,
        );
    }

    public function test_a_dry_run_archives_nothing(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event, 'completed'));

        $this->assertSame(1, TidyFinishedThreads::run(dryRun: true));
        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->client));
        $this->assertNull($c->fresh()->auto_archived_at);
    }

    public function test_the_command_runs(): void
    {
        $event = $this->event('Rivera Wedding');
        $this->thread($event, $this->booking($event, 'completed'));

        $this->artisan('conversations:tidy')
            ->expectsOutputToContain('1 conversation(s) archived.')
            ->assertSuccessful();
    }

    // ── By hand, on both sides ───────────────────────────────

    /**
     * The professional's Archived tab showed nothing whatever was in it, so
     * that side had a drawer it could not open and could not fill. It matters
     * more now that finished jobs file themselves.
     */
    public function test_the_professional_can_archive_and_restore(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event));

        $this->actingAs($this->professional)->post(route('conversations.archive', $c));
        $this->assertTrue($this->isArchivedFor($c->fresh(), $this->professional));

        $this->actingAs($this->professional)->post(route('conversations.archive', $c));
        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->professional));
    }

    /** And one person archiving does not archive it for the other. */
    public function test_archiving_is_one_persons_own_business(): void
    {
        $event = $this->event('Rivera Wedding');
        $c     = $this->thread($event, $this->booking($event));

        $this->actingAs($this->client)->post(route('conversations.archive', $c));

        $this->assertTrue($this->isArchivedFor($c->fresh(), $this->client));
        $this->assertFalse($this->isArchivedFor($c->fresh(), $this->professional));
    }
}
