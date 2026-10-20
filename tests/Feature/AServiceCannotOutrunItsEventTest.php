<?php

namespace Tests\Feature;

use App\Domain\Requests\ServiceTimeline;
use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sir Peter, 18 October, with a screenshot of his own request:
 *
 * "As a client if I have a set End time that the event stops or should stop,
 * then it should not have allowed a client to select service time(s) to go past
 * it ... however it did allow the client to pass or go on to the next step of
 * the process. This system needs to catch that as an error and make the client
 * not skip or go forward until its fixed."
 *
 * His event ran 10:00 AM to 11:00 PM. Two services were set to end at 12:00 AM
 * and step 7 let him continue.
 *
 * There were two faults behind that, and the second is the one that would have
 * made a careless fix wrong: 12:00 AM is numerically SMALLER than 11:00 PM, so
 * comparing the times as written says midnight comes first and lets exactly the
 * case he reported through. Everything is resolved against the event day first.
 */
class AServiceCannotOutrunItsEventTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $parent = Category::firstOrCreate(
            ['slug' => 'lighting-cat'],
            ['name' => 'Lighting', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        $this->service = Category::create([
            'name' => 'Uplighting & Ambient Lighting',
            'slug' => 'uplighting-' . uniqid(),
            'kind' => Category::SERVICE,
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @param array<string, string> $times */
    private function submit(array $times, string $eventEnd = '23:00')
    {
        return $this->actingAs($this->client)->post(
            route('client.bsr.save', 'availability'),
            [
                'event_date'       => now()->addDays(20)->toDateString(),
                'event_start_time' => '10:00',
                'event_end_time'   => $eventEnd,
                'services'         => [$this->service->id],
                'service_times'    => [$this->service->id => $times],
            ],
        );
    }

    // ── His case ─────────────────────────────────────────────

    /** The exact combination from his screenshot. */
    public function test_a_service_ending_at_midnight_is_refused_when_the_event_ends_at_eleven(): void
    {
        $this->submit(['start' => '13:00', 'end' => '00:00'])
            ->assertSessionHasErrors("service_times.{$this->service->id}.end");
    }

    /** And the message says what to do about it, with the real time in it. */
    public function test_the_error_names_the_event_end_time(): void
    {
        $this->submit(['start' => '13:00', 'end' => '00:00']);

        $errors = session('errors')->get("service_times.{$this->service->id}.end");

        $this->assertStringContainsString('11:00 PM', $errors[0]);
        $this->assertStringContainsString('Uplighting & Ambient Lighting', $errors[0]);
    }

    /**
     * His second screenshot exactly: outside at BOTH ends.
     *
     * "It allowed ... the services to pick times prior and after the event that
     * went BEFORE and AFTER the initial timeframe." Event 10:00 AM to 11:00 PM,
     * Country Club Venues 9:00 AM to 12:00 AM. Both edges are wrong and both
     * are reported, because fixing one would still leave the other.
     */
    public function test_a_service_outside_at_both_ends_is_refused_at_both(): void
    {
        $this->submit(['start' => '09:00', 'end' => '00:00'])
            ->assertSessionHasErrors([
                "service_times.{$this->service->id}.start",
                "service_times.{$this->service->id}.end",
            ]);
    }

    /** A service that finishes within the event goes through. */
    public function test_a_service_inside_the_event_is_allowed(): void
    {
        $this->submit(['start' => '13:00', 'end' => '22:00'])
            ->assertSessionHasNoErrors();
    }

    /** Ending exactly when the event ends is inside it, not past it. */
    public function test_a_service_ending_exactly_with_the_event_is_allowed(): void
    {
        $this->submit(['start' => '13:00', 'end' => '23:00'])
            ->assertSessionHasNoErrors();
    }

    // ── The other end of the window ──────────────────────────

    public function test_a_service_starting_before_the_event_is_refused(): void
    {
        $this->submit(['start' => '08:00', 'end' => '12:00'])
            ->assertSessionHasErrors("service_times.{$this->service->id}.start");
    }

    // ── Where the rule must not apply ────────────────────────

    /**
     * An event that runs past midnight is normal, and a service inside it must
     * still pass. A reception from 6pm to 2am with a DJ until 1am is fine.
     */
    public function test_a_service_inside_an_event_that_crosses_midnight_is_allowed(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '18:00',
            'event_end_time'   => '02:00',
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '20:00', 'end' => '01:00']],
        ])->assertSessionHasNoErrors();
    }

    /**
     * And a service held in the small hours of that event passes too.
     *
     * Reported 20 October as "why does this error come": an event running
     * 10:00 PM to 1:13 AM, with a service inside it, refused for starting
     * before the event began. Every service time was resolved against the day
     * the event STARTED, so 12:30 AM came out fourteen hours before a 10:00 PM
     * start rather than two and a half hours after it. The end time already
     * had this roll; the start never did.
     */
    public function test_a_service_in_the_small_hours_of_a_late_event_is_allowed(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '22:00',
            'event_end_time'   => '01:13',
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '00:30', 'end' => '01:00']],
        ])->assertSessionHasNoErrors();
    }

    /** The roll does not excuse a service that genuinely overruns. */
    public function test_a_service_in_the_small_hours_is_still_refused_when_it_overruns(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '22:00',
            'event_end_time'   => '01:13',
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '00:30', 'end' => '02:00']],
        ])->assertSessionHasErrors("service_times.{$this->service->id}.end");
    }

    /**
     * And on an event that does NOT cross midnight it is still refused.
     *
     * This is the half the roll could easily have broken. A 10:00 AM to 11:00
     * PM event has no small hours, so a service at 12:30 AM is outside it, and
     * that is the case Sir Peter reported in the first place.
     */
    public function test_a_small_hours_service_is_still_refused_on_a_daytime_event(): void
    {
        $this->submit(['start' => '00:30', 'end' => '01:00'])
            ->assertSessionHasErrors("service_times.{$this->service->id}.start");
    }

    /**
     * When the event ends on the following day, the message says so.
     *
     * "runs past the end of your event, which ends at 1:13 AM" beside a service
     * finishing at 11:00 PM reads as a contradiction, because 11 PM is plainly
     * before 1 AM. The client is then being asked to fix something that looks
     * already correct.
     */
    public function test_the_message_says_when_the_end_is_on_the_next_day(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '22:00',
            'event_end_time'   => '01:13',
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '00:30', 'end' => '02:00']],
        ]);

        $this->assertStringContainsString(
            '1:13 AM the next day',
            session('errors')->first("service_times.{$this->service->id}.end"),
        );
    }

    /** A same-day end is not padded with a day that is not there. */
    public function test_the_message_does_not_say_next_day_when_it_is_the_same_day(): void
    {
        $this->submit(['start' => '13:00', 'end' => '00:00']);

        $this->assertStringNotContainsString(
            'the next day',
            session('errors')->first("service_times.{$this->service->id}.end"),
        );
    }

    /**
     * No end time on the event constrains nothing.
     *
     * An open-ended request is the client saying they do not know yet.
     * Inventing a boundary for it would reject timings that are perfectly fine.
     */
    public function test_an_event_with_no_end_time_constrains_nothing(): void
    {
        $this->submit(['start' => '13:00', 'end' => '00:00'], eventEnd: '')
            ->assertSessionHasNoErrors();
    }

    /** A service with nothing entered follows the event and cannot breach it. */
    public function test_a_service_with_no_times_is_left_alone(): void
    {
        $this->submit(['start' => '', 'end' => ''])
            ->assertSessionHasNoErrors();
    }

    // ── Backup dates, which he asked for separately ──────────

    /**
     * His second screenshot and his words: "even if its on the optional or
     * backup dates."
     *
     * A backup date carries its own hours. Accepting one whose window the
     * services do not fit would produce a booking nobody can honour, so it is
     * checked exactly like the preferred date.
     */
    public function test_a_service_outside_a_backup_date_is_refused(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '10:00',
            'event_end_time'   => '23:00',
            // The backup day is shorter than the main one.
            'backup_dates'     => [[
                'date'  => now()->addDays(21)->toDateString(),
                'start' => '10:00',
                'end'   => '18:00',
            ]],
            'services'      => [$this->service->id],
            // Fine on the main date, past the end of the backup.
            'service_times' => [$this->service->id => ['start' => '13:00', 'end' => '22:00']],
        ])->assertSessionHasErrors("service_times.{$this->service->id}.end");
    }

    /** And the message says which day it breached. */
    public function test_the_error_names_the_backup_date(): void
    {
        $backup = now()->addDays(21);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '10:00',
            'event_end_time'   => '23:00',
            'backup_dates'     => [['date' => $backup->toDateString(), 'start' => '10:00', 'end' => '18:00']],
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '13:00', 'end' => '22:00']],
        ]);

        $message = session('errors')->get("service_times.{$this->service->id}.end")[0];

        $this->assertStringContainsString('backup date of ' . $backup->format('M j'), $message);
        $this->assertStringContainsString('6:00 PM', $message);
    }

    /** A backup date the services do fit inside is allowed. */
    public function test_a_service_inside_every_offered_date_is_allowed(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '10:00',
            'event_end_time'   => '23:00',
            'backup_dates'     => [['date' => now()->addDays(21)->toDateString(), 'start' => '10:00', 'end' => '23:00']],
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '13:00', 'end' => '22:00']],
        ])->assertSessionHasNoErrors();
    }

    /**
     * A backup date with blank hours inherits the main ones.
     *
     * Which is what the form pre-fills, so most backups are the same window as
     * the event and must not be reported as a second breach of it.
     */
    public function test_a_backup_date_with_no_hours_of_its_own_uses_the_events(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '10:00',
            'event_end_time'   => '23:00',
            'backup_dates'     => [['date' => now()->addDays(21)->toDateString(), 'start' => '', 'end' => '']],
            'services'         => [$this->service->id],
            'service_times'    => [$this->service->id => ['start' => '13:00', 'end' => '22:00']],
        ])->assertSessionHasNoErrors();
    }

    /**
     * One wrong service is one message, however many dates it breaks.
     *
     * Three messages under one box reads as three problems to fix.
     */
    public function test_a_service_wrong_on_several_dates_is_reported_once(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(20)->toDateString(),
            'event_start_time' => '10:00',
            'event_end_time'   => '18:00',
            'backup_dates'     => [
                ['date' => now()->addDays(21)->toDateString(), 'start' => '10:00', 'end' => '17:00'],
                ['date' => now()->addDays(22)->toDateString(), 'start' => '10:00', 'end' => '16:00'],
            ],
            'services'      => [$this->service->id],
            'service_times' => [$this->service->id => ['start' => '13:00', 'end' => '22:00']],
        ]);

        $this->assertCount(1, session('errors')->get("service_times.{$this->service->id}.end"));
    }

    // ── The second fault, underneath his report ──────────────

    /**
     * Midnight used to be stamped at the START of the event day.
     *
     * fromInput put every time on the event's own date with no past-midnight
     * roll, which the event's own end time has done since it was written. So
     * "1:00 PM to 12:00 AM" stored an end ten hours BEFORE the service began:
     * its duration came out null and it sat outside the event it belongs to.
     */
    public function test_a_service_ending_after_midnight_is_stored_on_the_next_day(): void
    {
        $resolved = ServiceTimeline::fromInput(
            [$this->service->id => ['start' => '13:00', 'end' => '00:00']],
            [$this->service->id],
            Carbon::parse('2026-10-22 10:00'),
        );

        $this->assertSame('2026-10-22 13:00:00', $resolved[$this->service->id]['starts_at']->toDateTimeString());
        $this->assertSame(
            '2026-10-23 00:00:00',
            $resolved[$this->service->id]['ends_at']->toDateTimeString(),
            'Midnight belongs to the day after, not to the start of the event day.',
        );
    }

    /** An ordinary same-day service is untouched by that roll. */
    public function test_an_ordinary_service_keeps_its_own_day(): void
    {
        $resolved = ServiceTimeline::fromInput(
            [$this->service->id => ['start' => '10:00', 'end' => '13:00']],
            [$this->service->id],
            Carbon::parse('2026-10-22 10:00'),
        );

        $this->assertSame('2026-10-22 13:00:00', $resolved[$this->service->id]['ends_at']->toDateTimeString());
    }
}
