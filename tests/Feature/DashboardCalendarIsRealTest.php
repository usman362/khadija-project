<?php

namespace Tests\Feature;

use App\Models\{Event, Payment, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client dashboard's calendar and stat cards report the account, not a
 * drawing of one.
 *
 * Three things were decoration:
 *
 *  · ‹ › had no handler and Today / Month / Week were <span>s. Five controls,
 *    three of them dead, and the grid could only ever show this month.
 *  · Every entry was painted one colour under a legend naming Booked, Pending,
 *    On Hold and Unavailable — four states the calendar never drew.
 *  · Each stat card carried the same hand-drawn rising sparkline and "▲ 0%",
 *    so a card reading $0.00 sat under a line climbing to the right.
 *
 *  And Saved Professionals was the literal 0.
 */
class DashboardCalendarIsRealTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

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
    }

    private function event(string $title, string $status, bool $published, \Carbon\Carbon $when): Event
    {
        return Event::create([
            'title' => $title, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => $status,
            'is_published' => $published, 'starts_at' => $when,
        ]);
    }


    /**
     * created_at is not fillable on Payment, so passing it to create() is
     * quietly ignored and every seeded payment lands in the current month —
     * which would make a period test pass or fail for the wrong reason.
     */
    private function paymentOn(\Carbon\Carbon $when, float $amount): void
    {
        $payment = Payment::create([
            'user_id' => $this->client->id, 'status' => 'completed',
            'amount' => $amount, 'gateway' => 'manual', 'currency' => 'USD',
        ]);

        Payment::where('id', $payment->id)->update([
            'created_at' => $when, 'updated_at' => $when,
        ]);
    }

    private function dashboard(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.dashboard', $query))
            ->assertSuccessful()
            ->getContent();
    }

    /**
     * The calendar panel only.
     *
     * The same event title appears in Your Gigs Overview and Recent Activity
     * further down the page, so asserting against the whole document would
     * pass whatever the calendar drew.
     */
    private function calendar(array $query = []): string
    {
        $html = $this->dashboard($query);

        $from = strpos($html, 'id="calendar"');
        $to   = strpos($html, 'od-row-3', $from ?: 0);

        return $from === false ? '' : substr($html, $from, ($to ?: strlen($html)) - $from);
    }

    /** The colour and the legend are the stage the rest of the portal reports. */
    public function test_each_entry_is_coloured_by_its_real_stage(): void
    {
        $this->event('Booked Gala', 'confirmed', true, now()->startOfMonth()->addDays(4));
        $this->event('Open Wedding', 'published', true, now()->startOfMonth()->addDays(9));

        $cal = $this->calendar();

        $this->assertStringContainsString('Booked Gala: Booked', $cal);
        $this->assertStringContainsString('Open Wedding: Open for proposals', $cal);

        // The legend names exactly what is drawn — and not the four states it
        // used to claim.
        $this->assertStringContainsString('Open for proposals', $cal);
        $this->assertStringNotContainsString('On Hold', $cal);
        $this->assertStringNotContainsString('Unavailable', $cal);
    }

    /** A month with nothing in it explains no colours at all. */
    public function test_an_empty_month_shows_no_legend(): void
    {
        $html = $this->dashboard();

        // The class name is in the stylesheet either way; the rendered block
        // is the thing being asserted about.
        $this->assertStringNotContainsString('class="od-cal-legend"', $html);
        $this->assertStringContainsString('Nothing scheduled this month', $html);
    }

    public function test_the_arrows_move_the_calendar(): void
    {
        $lastMonth = now()->startOfMonth()->subMonth();
        $this->event('Last Month Job', 'confirmed', true, $lastMonth->copy()->addDays(3));

        // Not on this month's grid…
        $this->assertStringNotContainsString('Last Month Job', $this->calendar());

        // …and on the previous month's, which is what ‹ opens.
        $cal = $this->calendar(['cal' => $lastMonth->format('Y-m-d')]);

        $this->assertStringContainsString('Last Month Job', $cal);
        $this->assertStringContainsString($lastMonth->format('F Y'), $cal);
    }

    public function test_the_week_tab_shows_a_week(): void
    {
        $anchor = now()->startOfMonth()->addDays(9);

        $this->event('In This Week', 'confirmed', true, $anchor->copy());
        $this->event('Three Weeks Off', 'confirmed', true, $anchor->copy()->addWeeks(3));

        $cal = $this->calendar(['calview' => 'week', 'cal' => $anchor->format('Y-m-d')]);

        $this->assertStringContainsString('In This Week', $cal);
        $this->assertStringNotContainsString('Three Weeks Off', $cal);
    }

    /** A bad date in the address is this month, not a broken page. */
    public function test_a_nonsense_date_does_not_break_the_page(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.dashboard', ['cal' => 'not-a-date']))
            ->assertSuccessful();
    }

    /** No invented trend: a card with no history draws no line and no figure. */
    public function test_a_card_with_no_history_claims_nothing(): void
    {
        $html = $this->dashboard();

        $this->assertStringNotContainsString('class="od-stat-spark"', $html,
            'A sparkline was drawn for an account with nothing in it.');
        $this->assertStringNotContainsString('class="od-stat-delta', $html,
            'A percentage was shown with nothing to compare against.');
    }

    /** And a card with history draws its own. */
    public function test_a_card_with_history_draws_its_own_line(): void
    {
        foreach ([2, 1, 0] as $i => $back) {
            $this->paymentOn(now()->subMonths($back)->startOfMonth()->addDay(), 100 * ($i + 1));
        }

        $html = $this->dashboard();

        $this->assertStringContainsString('class="od-stat-spark"', $html);
        // 200 last month to 300 this month is +50%.
        $this->assertStringContainsString('50%', $html);
    }

    public function test_saved_professionals_counts_the_saved_professionals(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $this->client->savedProfessionals()->attach($pro->id);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression(
            '/Saved Professionals.*?od-stat-value">\s*1\s*</s',
            $html,
            'The card is still the literal 0.',
        );
    }

    /* ── The period selector above the cards ─────────────────── */

    /**
     * It was a <button> printing the current month with nothing behind it,
     * above four cards that all said "All time" — a filter that did not exist.
     */
    public function test_the_period_control_offers_periods(): void
    {
        $html = $this->dashboard();

        foreach (['All time', 'This month', 'Last month', 'Last 3 months', 'This year'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_the_period_narrows_what_the_cards_answer(): void
    {
        // Paid last month, not this one.
        $this->paymentOn(now()->startOfMonth()->subMonth()->addDay(), 250);

        $this->assertStringContainsString('$250.00', $this->dashboard(['period' => 'all']));
        $this->assertStringContainsString('$250.00', $this->dashboard(['period' => 'last']));

        // This month has none of it.
        $this->assertStringContainsString('$0.00', $this->dashboard(['period' => 'month']));
    }

    /** The label says which period is being answered for. */
    public function test_the_cards_say_which_period_they_answer_for(): void
    {
        $html = $this->dashboard(['period' => 'last']);

        $this->assertStringContainsString('>Last month<', $html);
    }

    /** A period that is not one of ours is All time, not a broken page. */
    public function test_a_nonsense_period_falls_back(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.dashboard', ['period' => 'whenever']))
            ->assertSuccessful()
            ->assertSee('All time');
    }

    /**
     * Every control that changes what is on screen is marked for the live
     * swap, so using one does not take the whole page with it.
     */
    public function test_the_controls_change_the_page_without_reloading_it(): void
    {
        $this->event('Booked Gala', 'confirmed', true, now()->startOfMonth()->addDays(4));

        $html = $this->dashboard();

        // ‹ › Today Month Week, and the five period options.
        $this->assertGreaterThanOrEqual(
            10,
            substr_count($html, 'data-live'),
            'A control was left as a plain link, so pressing it reloads the page.',
        );

        // And the regions those clicks replace.
        foreach (['id="odPeriod"', 'id="odStats"', 'id="odCalCard"'] as $panel) {
            $this->assertStringContainsString($panel, $html);
        }
    }

    /* ── Today ───────────────────────────────────────────────── */

    /**
     * "Today" is a day, listed.
     *
     * It used to jump the month grid to today's month — so pressing it while
     * looking at that month changed nothing at all, and it never showed the
     * day on its own.
     */
    public function test_today_shows_only_today_as_a_list(): void
    {
        $this->event('Morning Walkthrough', 'published', true, now()->setTime(11, 0));
        $this->event('Afternoon Tasting', 'confirmed', true, now()->setTime(15, 30));
        $this->event('Some Other Day', 'confirmed', true, now()->addDays(3)->setTime(12, 0));

        $cal = $this->calendar(['calview' => 'day', 'cal' => now()->format('Y-m-d')]);

        $this->assertStringContainsString('Morning Walkthrough', $cal);
        $this->assertStringContainsString('Afternoon Tasting', $cal);
        $this->assertStringNotContainsString('Some Other Day', $cal);

        // A list, not a seven-column grid holding one column.
        $this->assertStringContainsString('od-agenda-row', $cal);
        $this->assertStringNotContainsString('od-cal-dow', $cal);

        // Each line carries the time and the stage.
        $this->assertStringContainsString('11:00 AM', $cal);
        $this->assertStringContainsString('3:30 PM', $cal);
    }

    /** In time order, so the day reads top to bottom. */
    public function test_the_day_reads_in_time_order(): void
    {
        $this->event('Later', 'confirmed', true, now()->setTime(16, 0));
        $this->event('Earlier', 'confirmed', true, now()->setTime(9, 0));

        $cal = $this->calendar(['calview' => 'day', 'cal' => now()->format('Y-m-d')]);

        $this->assertLessThan(
            strpos($cal, 'Later'),
            strpos($cal, 'Earlier'),
            'The day is not in time order.',
        );
    }

    /** An empty day says so, and offers the month. */
    public function test_an_empty_day_says_so(): void
    {
        $cal = $this->calendar(['calview' => 'day', 'cal' => now()->format('Y-m-d')]);

        $this->assertStringContainsString('Nothing on today', $cal);
        $this->assertStringContainsString('See the month', $cal);
    }

    /** The arrows step a day at a time while a day is being shown. */
    public function test_the_arrows_step_one_day(): void
    {
        $this->event('Tomorrow Job', 'confirmed', true, now()->addDay()->setTime(10, 0));

        $today = $this->calendar(['calview' => 'day', 'cal' => now()->format('Y-m-d')]);
        $this->assertStringNotContainsString('Tomorrow Job', $today);

        $next = $this->calendar(['calview' => 'day', 'cal' => now()->addDay()->format('Y-m-d')]);
        $this->assertStringContainsString('Tomorrow Job', $next);
    }

    /**
     * The wait is answered on screen.
     *
     * On a slow connection a press sat for a second or two with nothing to
     * show for it, which reads as a dead button. The panels being fetched take
     * a quiet state with a moving bar — but only after a beat, because a
     * loading state that appears and disappears inside a tenth of a second is
     * a flicker, and worse than none.
     */
    public function test_the_page_has_something_to_show_while_it_waits(): void
    {
        $html = $this->dashboard();

        foreach (['od-live', 'is-busy', 'is-pending', 'odSweep', 'BUSY_AFTER_MS'] as $piece) {
            $this->assertStringContainsString($piece, $html,
                "The loading state is missing its {$piece}.");
        }

        // And it is not left on: something has to take it off again.
        $this->assertStringContainsString('busyOff', $html);
    }
}
