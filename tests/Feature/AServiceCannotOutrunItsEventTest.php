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
