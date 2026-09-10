<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Events changes in place, and its calendar is a real calendar.
 *
 * Ali, 2026-09-10: "functionality calendar ki aur ziada functional karo button
 * press karne me abhi reload bhi horaha hai is page saari functionality real
 * time chalni chaiye."
 *
 * The calendar was a month grid only: every entry the same orange, the arrows
 * reloading the page and dropping you back on the list, nothing to click but
 * an event's title. It is now a day, a week or a month, coloured by stage,
 * every day and "+N more" clickable — and every control on the page redraws
 * only what changed, without a reload.
 */
class MyEventsIsLiveTest extends TestCase
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
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    private function event(string $title, $startsAt, array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title' => $title, 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id, 'starts_at' => $startsAt,
        ]);
    }

    private function calendar(array $query = []): string
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.events.index', $query + ['tab' => 'calendar']))
            ->assertOk()->getContent();

        $a = strpos($html, 'id="mgCal"');
        $this->assertNotFalse($a, 'The calendar card is not on the page.');

        return substr($html, $a, strpos($html, 'id="tab-details"', $a) - $a);
    }

    // ── The calendar ─────────────────────────────────────────────────

    public function test_it_offers_today_week_and_month(): void
    {
        $cal = $this->calendar();

        foreach (['>Today<', '>Week<', '>Month<'] as $label) {
            $this->assertStringContainsString($label, $cal);
        }
    }

    public function test_entries_are_coloured_by_their_stage_and_the_legend_names_only_those(): void
    {
        $this->event('Booked Gala', now()->startOfMonth()->addDays(9), ['status' => 'confirmed']);
        $this->event('Open Brunch', now()->startOfMonth()->addDays(11));

        $cal = $this->calendar(['cal' => now()->format('Y-m-d')]);

        $this->assertStringContainsString('Booked Gala — Booked', $cal);
        $this->assertStringContainsString('Open Brunch — Open for proposals', $cal);
        $this->assertStringContainsString('#10b981', $cal);

        $legend = substr($cal, strpos($cal, 'ec-legend'));
        $this->assertStringContainsString('Booked', $legend);
        $this->assertStringNotContainsString('Cancelled', $legend);
    }

    public function test_the_week_view_holds_one_week(): void
    {
        $sunday = now()->startOfWeek(\Carbon\Carbon::SUNDAY)->addWeeks(2)->setTime(12, 0);
        $this->event('In That Week', $sunday->copy()->addDays(2));
        $this->event('A Week Later', $sunday->copy()->addDays(9));

        $cal = $this->calendar(['calview' => 'week', 'cal' => $sunday->format('Y-m-d')]);

        $this->assertStringContainsString('In That Week', $cal);
        $this->assertStringNotContainsString('A Week Later', $cal);
        $this->assertSame(7, substr_count($cal, 'class="tg-dayhead'));
    }

    public function test_the_day_view_lists_that_day(): void
    {
        $day = now()->addDays(5)->setTime(18, 30);
        $this->event('Evening Reception', $day);
        $this->event('Next Morning', $day->copy()->addDay()->setTime(9, 0));

        $cal = $this->calendar(['calview' => 'day', 'cal' => $day->format('Y-m-d')]);

        $this->assertStringContainsString('Evening Reception', $cal);
        $this->assertStringContainsString('6:30 PM', $cal);
        $this->assertStringNotContainsString('Next Morning', $cal);
    }

    /** Every day, and the "+N more" under a busy one, opens that day. */
    public function test_a_day_and_its_overflow_open_that_day(): void
    {
        $day = now()->startOfMonth()->addDays(14)->setTime(10, 0);
        foreach (range(1, 5) as $n) {
            $this->event("Busy Day {$n}", $day->copy()->addMinutes($n));
        }

        $cal = $this->calendar(['cal' => $day->format('Y-m-d')]);
        $this->assertStringContainsString('+2 more', $cal);

        // Compared by what the link asks for, not by its exact text — the
        // page is free to put the parameters in any order.
        preg_match_all('/href="([^"]+)"/', $cal, $m);
        $opensThatDay = array_filter($m[1], function ($href) use ($day) {
            parse_str((string) parse_url(html_entity_decode($href), PHP_URL_QUERY), $q);

            return ($q['calview'] ?? null) === 'day' && ($q['cal'] ?? null) === $day->format('Y-m-d')
                && ($q['tab'] ?? null) === 'calendar';
        });

        $this->assertGreaterThanOrEqual(2, count($opensThatDay), 'The day number and "+2 more" should both open the day.');
    }

    /** The arrows keep the calendar open — they used to drop you on the list. */
    public function test_the_arrows_stay_on_the_calendar_and_move_it(): void
    {
        $this->event('Last Month Job', now()->startOfMonth()->subMonth()->addDays(3));

        $cal = $this->calendar();
        $this->assertStringNotContainsString('Last Month Job', $cal);
        $this->assertStringContainsString('tab=calendar', $cal);

        $prev = $this->calendar(['cal' => now()->startOfMonth()->subMonth()->format('Y-m-d')]);
        $this->assertStringContainsString('Last Month Job', $prev);
        $this->assertStringContainsString(now()->startOfMonth()->subMonth()->format('F Y'), $prev);
    }

    /** Links from before, ?month=&year=, still land on the month they meant. */
    public function test_old_month_links_still_work(): void
    {
        $then = now()->startOfMonth()->subMonths(4);
        $this->event('Old Link Event', $then->copy()->addDays(2));

        $cal = $this->calendar(['month' => $then->month, 'year' => $then->year]);

        $this->assertStringContainsString($then->format('F Y'), $cal);
        $this->assertStringContainsString('Old Link Event', $cal);
    }

    public function test_a_nonsense_date_is_today_not_an_error(): void
    {
        $this->assertStringContainsString(now()->format('F Y'), $this->calendar(['cal' => 'not-a-date']));
    }

    /** One stage table, so the two calendars cannot colour the same event differently. */
    public function test_the_dashboard_and_my_events_share_one_colour_table(): void
    {
        $this->assertStringContainsString(
            'ClientCalendar::STAGES',
            file_get_contents(resource_path('views/client/dashboard.blade.php')),
        );
    }

    // ── In place, not reloaded ───────────────────────────────────────

    /** Each part that a control changes is a region the page can swap. */
    public function test_the_parts_that_change_are_swappable_regions(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.events.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-live-scope', $html);
        foreach (['mgFilters', 'mgListCard', 'mgCal', 'mgDetails', 'mgRail'] as $id) {
            $this->assertMatchesRegularExpression('/id="' . $id . '"[^>]*data-live-region|data-live-region[^>]*id="' . $id . '"/', $html, "#{$id} is not a live region.");
        }

        $this->assertStringContainsString('data-live-search', $html);
    }

    /**
     * form.submit() skips the submit event, so a dropdown using it reloads the
     * page however the page listens. Every auto-submitting control uses
     * requestSubmit().
     */
    public function test_no_control_submits_around_the_listener(): void
    {
        foreach (['index', '_period_select'] as $view) {
            $this->assertStringNotContainsString(
                'this.form.submit()',
                file_get_contents(resource_path("views/client/events/{$view}.blade.php")),
                "{$view} submits past the live listener.",
            );
        }
    }

    /**
     * The swapped parts are new nodes, so their buttons need listeners that
     * survive the swap — bound to the document, not to the button.
     */
    public function test_the_page_scripts_survive_a_swap(): void
    {
        $src = file_get_contents(resource_path('views/client/events/index.blade.php'));

        $this->assertStringNotContainsString("querySelectorAll('[data-subtab]').forEach(function (btn)", $src);
        $this->assertStringContainsString("closest('[data-subtab]')", $src);
        $this->assertStringContainsString("closest('[data-filter-toggle]')", $src);
    }

    /**
     * The rail's period picker keeps where the client is. Found in the
     * browser: changing it from the calendar dropped tab=calendar from the
     * address, so a reload opened the list instead.
     */
    public function test_the_period_picker_keeps_the_tab_and_the_calendars_place(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.events.index', ['tab' => 'calendar', 'calview' => 'week', 'cal' => '2026-10-04']))
            ->assertOk()->getContent();

        $rail = substr($html, strpos($html, 'id="mgRail"'));
        $form = substr($rail, 0, strpos($rail, '</form>'));

        $this->assertStringContainsString('name="tab" value="calendar"', $form);
        $this->assertStringContainsString('name="calview" value="week"', $form);
        $this->assertStringContainsString('name="cal" value="2026-10-04"', $form);
    }

    /** What the script fetches is the same page — a region request needs no second endpoint. */
    public function test_a_live_request_returns_the_same_regions(): void
    {
        $this->event('Fetched Event', now()->addDays(3));

        $html = $this->actingAs($this->client)
            ->get(route('client.events.index', ['search' => 'Fetched']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->getContent();

        $this->assertStringContainsString('id="mgListCard"', $html);
        $this->assertStringContainsString('Fetched Event', $html);
    }
}
