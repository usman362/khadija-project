<?php

namespace Tests\Feature;

use App\Domain\Calendar\Availability;
use App\Models\AvailabilityDay;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 24 September: "i noticed that it says unavailable at the bottom,
 * but why not Available as well?"
 *
 * Because nothing recorded it. Now something does, and the thing worth
 * holding is the distinction underneath: there are three answers, not two.
 * Available and unavailable are both things a person said. A day nobody has
 * answered is UNKNOWN, and a calendar that paints unanswered days green is
 * telling a client something nobody told it.
 */
class ClientAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    private function mark(string $day, string $state): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)
            ->post(route('client.calendar.availability'), ['day' => $day, 'state' => $state]);
    }

    /** A day takes an answer, and it is kept. */
    public function test_a_day_can_be_marked(): void
    {
        $this->mark('2026-11-04', Availability::AVAILABLE)->assertRedirect();

        $this->assertDatabaseHas('availability_days', [
            'user_id' => $this->client->id,
            'state'   => Availability::AVAILABLE,
        ]);
    }

    /** The control that sets an answer removes it. */
    public function test_marking_the_same_state_twice_clears_the_day(): void
    {
        $this->mark('2026-11-04', Availability::AVAILABLE);
        $this->mark('2026-11-04', Availability::AVAILABLE);

        $this->assertSame(0, AvailabilityDay::where('user_id', $this->client->id)->count(),
            'Pressing the same answer again should take it back.');
    }

    /** One answer per day: saying the other thing replaces it. */
    public function test_a_day_holds_one_answer(): void
    {
        $this->mark('2026-11-04', Availability::AVAILABLE);
        $this->mark('2026-11-04', Availability::UNAVAILABLE);

        $rows = AvailabilityDay::where('user_id', $this->client->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame(Availability::UNAVAILABLE, $rows->first()->state);
    }

    /** A day nobody answered is not free. It is nothing. */
    public function test_an_unanswered_day_has_no_answer(): void
    {
        $days = Availability::between($this->client, now(), now()->addDays(20));

        $this->assertTrue($days->isEmpty());
        $this->assertNull($days->get(now()->addDay()->format('Y-m-d'))?->state);
    }

    /** Blocking a stretch writes every day in it. */
    public function test_a_range_can_be_blocked_in_one_go(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.calendar.availability.range'), [
                'from' => '2026-12-01', 'to' => '2026-12-05', 'state' => Availability::UNAVAILABLE,
            ])
            ->assertRedirect();

        $this->assertSame(5, AvailabilityDay::where('user_id', $this->client->id)
            ->where('state', Availability::UNAVAILABLE)->count());
    }

    /** A range given backwards is still the range the person meant. */
    public function test_a_backwards_range_is_read_the_right_way_round(): void
    {
        Availability::markRange($this->client, '2026-12-05', '2026-12-01', Availability::UNAVAILABLE);

        $this->assertSame(5, AvailabilityDay::where('user_id', $this->client->id)->count());
    }

    /** A decade is not a holiday. */
    public function test_an_absurd_range_is_capped(): void
    {
        $written = Availability::markRange($this->client, '2026-01-01', '2050-01-01', Availability::UNAVAILABLE);

        $this->assertLessThanOrEqual(367, $written);
    }

    /** It is this client's calendar, and nobody writes to it but them. */
    public function test_one_client_cannot_mark_anothers_calendar(): void
    {
        $other = User::factory()->create(['primary_role' => 'client']);
        $other->assignRole('client');

        $this->mark('2026-11-04', Availability::AVAILABLE);

        $this->assertSame(0, AvailabilityDay::where('user_id', $other->id)->count());
        $this->assertSame(1, AvailabilityDay::where('user_id', $this->client->id)->count());
    }

    /** The marked days show on the page, in the legend's own words. */
    public function test_the_page_shows_what_was_marked(): void
    {
        $this->mark(now()->addDay()->format('Y-m-d'), Availability::UNAVAILABLE);

        $this->actingAs($this->client)
            ->get(route('client.calendar.index', ['tab' => 'availability']))
            ->assertOk()
            ->assertSee('Available')
            ->assertSee('Unavailable')
            ->assertSee('Not set');   // renamed with Sir Peter's 4 Oct design: one click, three states
    }

    /** Nonsense is refused rather than stored. */
    public function test_a_state_that_is_not_a_state_is_refused(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.calendar.availability'), ['day' => '2026-11-04', 'state' => 'maybe'])
            ->assertSessionHasErrors('state');

        $this->assertSame(0, AvailabilityDay::count());
    }

    /**
     * My Availability is a month, whatever the other tab was left on.
     *
     * The grid is built to be one — it loops first to last and greys the days
     * either side — but it took whichever view the calendar tab happened to
     * carry. Arriving from a day or a week drew a single strip of seven days,
     * with "Previous month" on an arrow that moved by one week, which is not
     * a screen for saying which days of the year suit you.
     */
    public function test_the_availability_tab_is_always_a_month(): void
    {
        foreach (['day', 'week', 'month', null] as $view) {
            $html = $this->actingAs($this->client)
                ->get('/client/calendar?tab=availability' . ($view ? '&calview=' . $view : ''))
                ->assertSuccessful()->getContent();

            $grid = $this->grid($html);

            // A month is five or six rows of seven, never one.
            $rows = substr_count($grid, '<tr>');
            $this->assertGreaterThanOrEqual(
                4,
                $rows,
                'Arriving with calview=' . ($view ?? 'none') . ' drew ' . $rows . ' week(s), not a month.'
            );

            $this->assertStringContainsString(now()->format('F Y'), $html, 'The heading does not name the month.');
        }
    }

    /** And its arrows move by a month, which is what they say they do. */
    public function test_the_availability_arrows_move_a_month(): void
    {
        $html = $this->actingAs($this->client)
            ->get('/client/calendar?tab=availability&calview=day')
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString(
            'cal=' . now()->copy()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            $html,
            'Next month does not go to the next month.'
        );
    }

    /** The rail says the range in words rather than naming the view. */
    public function test_the_rail_describes_the_range_in_english(): void
    {
        $html = $this->actingAs($this->client)
            ->get('/client/calendar?calview=day')
            ->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('any days this day', $html);
        $this->assertStringContainsString('any days today', $html);
    }

    /** The availability grid, without the month calendar on the other tab. */
    private function grid(string $html): string
    {
        $start = strpos($html, 'class="av-grid"');
        $this->assertNotFalse($start, 'No availability grid on the page.');

        $end = strpos($html, '</table>', $start);

        return substr($html, $start, $end - $start);
    }
}
