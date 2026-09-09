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

        $this->assertStringContainsString('Booked Gala — Booked', $cal);
        $this->assertStringContainsString('Open Wedding — Open for proposals', $cal);

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
            Payment::create([
                'user_id' => $this->client->id, 'status' => 'completed',
                'amount' => 100 * ($i + 1), 'gateway' => 'manual', 'currency' => 'USD',
                'created_at' => now()->subMonths($back)->startOfMonth()->addDay(),
                'updated_at' => now()->subMonths($back)->startOfMonth()->addDay(),
            ]);
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
}
