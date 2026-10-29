<?php

namespace Tests\Feature;

use App\Domain\AttendanceChase;
use App\Domain\PointOfContact;
use App\Models\{AttendanceReminder, Booking, Category, Event, Finalization, StaffMember, User};
use App\Notifications\NobodyHasSaidWhoIsComing;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Chasing the answer a professional promised to come back with.
 *
 * Sir Peter, 29 October, point 8: when a professional says they will decide
 * nearer the time, "require confirmation at least 24 hours before the event,
 * send reminders, and flag missed deadlines".
 *
 * The thing these tests are really protecting is that nobody is chased twice.
 * A chaser that arrives four times is read as a fault in the site rather than
 * as something to act on, and the command runs twice a day for ever.
 */
class NobodyHasSaidWhoIsComingTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-11-01 09:00:00'));

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    /**
     * A booking whose agreement left the answer open, on an event that many
     * days away.
     */
    private function owing(int $daysAway, string $attendance = PointOfContact::LATER, bool $signed = true): Booking
    {
        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays($daysAway),
        ]);

        $booking = Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $service->id,
            'status'      => 'confirmed',
            'price'       => 4000,
        ]);

        Finalization::create([
            'event_id'      => $event->id,
            'category_id'   => $service->id,
            'client_id'     => $this->client->id,
            'supplier_id'   => $this->professional->id,
            'booking_id'    => $booking->id,
            'terms_version' => 1,
            'agreed_price'  => 4000,
            'scope'         => 'Six hours of music.',
            'attendance'    => $attendance,
            'client_signature'      => $signed ? $this->client->name : null,
            'client_signed_at'      => $signed ? now() : null,
            'client_signed_version' => $signed ? 1 : null,
        ]);

        return $booking;
    }

    // ── Who is chased, and when ──────────────────────────────

    public function test_nothing_is_due_a_long_way_out(): void
    {
        $this->owing(daysAway: 30);

        $this->assertCount(0, AttendanceChase::due());
    }

    public function test_a_week_out_the_first_reminder_is_due(): void
    {
        $this->owing(daysAway: 6);

        $due = AttendanceChase::due();

        $this->assertCount(1, $due);
        $this->assertSame(AttendanceChase::FIRST, $due[0]['stage']);
    }

    public function test_two_days_out_the_final_one_is_due(): void
    {
        $this->owing(daysAway: 2);

        $this->assertSame(AttendanceChase::FINAL, AttendanceChase::due()[0]['stage']);
    }

    /**
     * Past the deadline it is a miss, not another reminder.
     *
     * The deadline is 24 hours before, so an event tomorrow morning is
     * already past it.
     */
    public function test_past_the_deadline_it_is_a_miss(): void
    {
        $this->owing(daysAway: 0);

        $this->assertSame(AttendanceChase::MISSED, AttendanceChase::due()[0]['stage']);
    }

    /**
     * A booking added late gets the stage it has reached, not the first one.
     *
     * Otherwise a booking made two days before its event receives the
     * week-out reminder and the real one never arrives.
     */
    public function test_a_late_booking_starts_at_the_stage_it_has_reached(): void
    {
        $this->owing(daysAway: 2);

        $this->assertSame(AttendanceChase::FINAL, AttendanceChase::due()[0]['stage']);
    }

    // ── Who is not chased ────────────────────────────────────

    public function test_an_answered_agreement_is_not_chased(): void
    {
        foreach ([PointOfContact::PROFESSIONAL, PointOfContact::STAFF] as $answer) {
            $this->owing(daysAway: 2, attendance: $answer);
        }

        $this->assertCount(0, AttendanceChase::due());
    }

    /** Nor an unsigned one: there is no promise to chase yet. */
    public function test_an_unsigned_agreement_is_not_chased(): void
    {
        $this->owing(daysAway: 2, signed: false);

        $this->assertCount(0, AttendanceChase::due());
    }

    public function test_once_it_is_settled_the_chasing_stops(): void
    {
        $booking = $this->owing(daysAway: 6);

        PointOfContact::set($booking, null, $this->professional);

        $this->assertCount(0, AttendanceChase::due());
    }

    public function test_a_cancelled_booking_is_not_chased(): void
    {
        $booking = $this->owing(daysAway: 2);
        $booking->forceFill(['status' => 'cancelled'])->save();

        $this->assertCount(0, AttendanceChase::due());
    }

    public function test_an_event_with_no_date_is_not_chased(): void
    {
        $booking = $this->owing(daysAway: 2);
        $booking->event->forceFill(['starts_at' => null])->save();

        $this->assertCount(0, AttendanceChase::due());
    }

    // ── Sent once, and only once ─────────────────────────────

    public function test_the_professional_is_told(): void
    {
        Notification::fake();
        $this->owing(daysAway: 6);

        AttendanceChase::run();

        Notification::assertSentTo($this->professional, NobodyHasSaidWhoIsComing::class);
    }

    /**
     * And the client is not.
     *
     * A decision, not an oversight. The obligation is the professional's and
     * so is the screen that settles it; telling a client the day before their
     * wedding that nobody has confirmed gives them alarm and nothing to do
     * with it. Whether they should hear about a MISS is Sir Peter's call.
     */
    public function test_the_client_is_not_alarmed_by_it(): void
    {
        Notification::fake();
        $this->owing(daysAway: 6);

        AttendanceChase::run();

        Notification::assertNotSentTo($this->client, NobodyHasSaidWhoIsComing::class);
    }

    /** The command runs twice a day for ever. It must not chase twice. */
    public function test_running_it_again_sends_nothing(): void
    {
        $this->owing(daysAway: 6);

        $this->assertSame(1, AttendanceChase::run());
        $this->assertSame(0, AttendanceChase::run());
        $this->assertSame(0, AttendanceChase::run());
    }

    /** But the next stage still arrives when it is reached. */
    public function test_the_next_stage_still_comes(): void
    {
        $booking = $this->owing(daysAway: 6);

        $this->assertSame(1, AttendanceChase::run());

        Carbon::setTestNow(now()->addDays(5));

        $this->assertSame(1, AttendanceChase::run());
        $this->assertSame(
            [AttendanceChase::FIRST, AttendanceChase::FINAL],
            AttendanceReminder::where('booking_id', $booking->id)->orderBy('id')->pluck('stage')->all(),
        );
    }

    public function test_a_dry_run_counts_without_sending(): void
    {
        Notification::fake();
        $this->owing(daysAway: 6);

        $this->assertSame(1, AttendanceChase::run(dryRun: true));

        Notification::assertNothingSent();
        $this->assertSame(0, AttendanceReminder::count());
    }

    // ── What it says ─────────────────────────────────────────

    /** It names the action, including the one people forget is an action. */
    public function test_the_reminder_says_that_choosing_yourself_counts(): void
    {
        $booking = $this->owing(daysAway: 6);

        $sentence = (new NobodyHasSaidWhoIsComing(
            $booking, AttendanceChase::FIRST, PointOfContact::confirmBy($booking),
        ))->sentence();

        $this->assertStringContainsString('Choosing yourself counts', $sentence);
    }

    /** Only the miss says something has gone wrong. */
    public function test_only_the_miss_says_the_deadline_has_passed(): void
    {
        $booking = $this->owing(daysAway: 6);

        $this->assertStringContainsString(
            'deadline has passed',
            (new NobodyHasSaidWhoIsComing($booking, AttendanceChase::MISSED))->sentence(),
        );

        $this->assertStringNotContainsString(
            'deadline has passed',
            (new NobodyHasSaidWhoIsComing($booking, AttendanceChase::FIRST))->sentence(),
        );
    }

    // ── The screen says it too ───────────────────────────────

    public function test_the_gig_page_flags_a_missed_deadline(): void
    {
        $booking = $this->owing(daysAway: 0);

        $this->assertTrue(AttendanceChase::overdue($booking));

        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $booking->event_id))
            ->assertSuccessful()
            ->assertSee('You have not said who is attending', false);
    }

    /**
     * And with nobody on the team, the answer is still answerable.
     *
     * Without this the only way to settle it would be to hire somebody.
     */
    public function test_a_professional_with_no_crew_can_still_say_it_is_them(): void
    {
        $booking = $this->owing(daysAway: 2);

        $this->assertSame(0, StaffMember::where('supplier_id', $this->professional->id)->count());

        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $booking->event_id))
            ->assertSuccessful()
            ->assertSee('I am attending myself', false);

        $this->actingAs($this->professional)
            ->post(route('professional.gigs.point-of-contact', $booking->event_id), ['staff_id' => null])
            ->assertRedirect();

        $this->assertTrue(PointOfContact::settled($booking->fresh()));
    }

    public function test_the_command_runs(): void
    {
        $this->owing(daysAway: 6);

        $this->artisan('attendance:chase')
            ->expectsOutputToContain('1 reminder(s) sent.')
            ->assertSuccessful();
    }
}
