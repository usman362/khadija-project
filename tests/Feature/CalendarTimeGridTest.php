<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\ClientCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Today and Week are drawn as a time grid.
 *
 * Ali, 2026-09-10: "yeh today aur week wale ki ui sahi krdo". Both were
 * month-style boxes: a four-hour reception and a ten-minute call were the same
 * small pill at the top of the day, and no time appeared anywhere. Earlier, on
 * the dashboard, he had asked for Today "line by line", the way calendars are.
 */
class CalendarTimeGridTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client = $this->client->fresh();
    }

    private function event(string $title, Carbon $start, ?Carbon $end = null): Event
    {
        return Event::create([
            'title' => $title, 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'starts_at' => $start, 'ends_at' => $end,
        ]);
    }

    private function grid(string $view, Carbon $day): array
    {
        return ClientCalendar::timeGrid(ClientCalendar::build($this->client, $view, $day->format('Y-m-d')));
    }

    /** Placed at its start, as tall as it lasts. */
    public function test_an_event_sits_at_its_time_and_is_as_long_as_it_lasts(): void
    {
        $day = now()->addDays(3)->startOfDay();
        $this->event('Reception', $day->copy()->setTime(10, 0), $day->copy()->setTime(12, 0));

        $g = $this->grid('day', $day);
        $it = $g['cols'][0]['items'][0];

        // 7 AM is the top; 10 AM is three hours down; two hours tall.
        $this->assertSame(ClientCalendar::DAY_STARTS, $g['from']);
        $this->assertSame(180, $it['start']);
        $this->assertSame(120, $it['stop'] - $it['start']);
    }

    /** Two at once share the column instead of covering each other. */
    public function test_overlapping_events_sit_side_by_side(): void
    {
        $day = now()->addDays(4)->startOfDay();
        $this->event('First', $day->copy()->setTime(14, 0), $day->copy()->setTime(16, 0));
        $this->event('Second', $day->copy()->setTime(15, 0), $day->copy()->setTime(17, 0));
        $this->event('Later', $day->copy()->setTime(18, 0), $day->copy()->setTime(19, 0));

        $col = $this->grid('day', $day)['cols'][0];

        $this->assertSame(2, $col['lanes']);
        $this->assertSame([0, 1, 0], array_column($col['items'], 'lane'));
    }

    /** The hours stretch to include an event outside the usual day. */
    public function test_the_hours_widen_to_fit_an_early_or_late_event(): void
    {
        $day = now()->addDays(5)->startOfDay();
        $this->event('Sunrise Shoot', $day->copy()->setTime(5, 0), $day->copy()->setTime(6, 0));
        $this->event('Late Set', $day->copy()->setTime(22, 0), $day->copy()->setTime(23, 30));

        $g = $this->grid('day', $day);

        $this->assertSame(5, $g['from']);
        $this->assertSame(24, $g['to']);
    }

    /** No end, or an end that is not after the start, is drawn an hour — never zero. */
    public function test_an_event_with_no_real_end_is_drawn_an_hour(): void
    {
        $day = now()->addDays(6)->startOfDay();
        $this->event('Open Ended', $day->copy()->setTime(9, 0));

        $it = $this->grid('day', $day)['cols'][0]['items'][0];

        $this->assertSame(60, $it['stop'] - $it['start']);
    }

    public function test_the_week_is_seven_columns_with_each_day_on_its_own(): void
    {
        $sunday = now()->startOfWeek(Carbon::SUNDAY)->addWeek();
        $this->event('Tuesday Lunch', $sunday->copy()->addDays(2)->setTime(12, 0), $sunday->copy()->addDays(2)->setTime(13, 0));

        $g = $this->grid('week', $sunday);

        $this->assertCount(7, $g['cols']);
        $this->assertCount(1, $g['cols'][2]['items']);
        $this->assertCount(0, $g['cols'][0]['items']);
    }

    /** The page draws it: hours down the side, the event's time on the event. */
    public function test_the_page_draws_the_grid(): void
    {
        $day = now()->addDays(3)->startOfDay();
        $this->event('Garden Party', $day->copy()->setTime(13, 30), $day->copy()->setTime(16, 0));

        $html = $this->actingAs($this->client)
            ->get(route('client.events.index', ['tab' => 'calendar', 'calview' => 'day', 'cal' => $day->format('Y-m-d')]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('class="tg tg-day"', $html);
        $this->assertStringContainsString('>1 PM<', $html);
        $this->assertStringContainsString('1:30 PM – 4:00 PM', $html);
        // 1:30 PM is 6.5 hours below 7 AM at 48px an hour.
        $this->assertStringContainsString('top: 312px; height: 120px;', $html);
        // The day view names the stage on the event; no separate legend.
        $this->assertStringNotContainsString('class="ec-legend"', $html);
    }

    public function test_the_now_line_is_only_on_today(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(11, 0));

        $today = $this->actingAs($this->client)
            ->get(route('client.events.index', ['tab' => 'calendar', 'calview' => 'day', 'cal' => now()->format('Y-m-d')]))
            ->getContent();
        $other = $this->actingAs($this->client)
            ->get(route('client.events.index', ['tab' => 'calendar', 'calview' => 'day', 'cal' => now()->addDays(2)->format('Y-m-d')]))
            ->getContent();

        $this->assertStringContainsString('class="tg-now"', $today);
        $this->assertStringNotContainsString('class="tg-now"', $other);
    }
}
