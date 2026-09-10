<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Events, 2026-09-10: "kuch bhi static nahi" — nothing on it may be for show.
 *
 * What was for show, found by reading every control on the page:
 *   - Export was a button with no handler.
 *   - "Professional Schedule" and "Payment Tracker" were spans marked
 *     "(visual)" in the source.
 *   - Both "This Month" dropdowns had one option and changed nothing.
 *   - Search promised "events, professionals" and searched titles only.
 *   - Total Spent, every row's Spent, and the Payment Summary summed columns
 *     that do not exist, so they read $0 for every client — the same bug the
 *     dashboard had fixed on 2026-08-25.
 *   - "Rescheduled" was the literal 0 over a flag the schema does not have.
 *   - The Details view's Total Budget was the literal 0.
 *   - Both status dropdowns offered "In progress", which no event can be.
 * And the wording: this product has no "master list", and Post an Event was on
 * the page three times.
 */
class MyEventsNothingIsStaticTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['name' => 'Priya Raghavan']);
        $this->pro->assignRole('professional');

        $this->service = Category::create([
            'name' => 'Event Photography', 'slug' => 'my-events-photo',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    private function event(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title' => 'Garden Reception', 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'starts_at' => now()->addDays(20), 'budget' => 1500,
        ]);
    }

    private function book(Event $event, string $status, float $price): Booking
    {
        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'category_id' => $this->service->id,
            'status' => $status, 'price' => $price,
        ]);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)->get(route('client.events.index', $query))->assertOk()->getContent();
    }

    public function test_the_wording_is_this_products_and_post_an_event_appears_once(): void
    {
        $html = $this->page();

        $this->assertStringNotContainsString('Master List', $html);
        $this->assertStringNotContainsString('Create Master List', $html);
        $this->assertStringNotContainsString('Post New Event', $html);
        $this->assertStringContainsString('Events List', $html);

        // One button, in the filter row.
        $this->assertSame(1, substr_count($html, '>Post an Event</a>'));
    }

    /** The money was $0 for everyone because it read columns that do not exist. */
    public function test_money_is_read_from_the_price_column(): void
    {
        // One booking per professional per service per event, so two events.
        $this->book($this->event(['title' => 'Paid Event']), 'completed', 800);
        $this->book($this->event(['title' => 'Agreed Event']), 'confirmed', 450);

        $html = $this->page();

        // Total Spent tile and the row's Spent: the completed booking.
        $this->assertStringContainsString('$800', $html);
        // The Payment Summary's booked total: paid plus agreed.
        $this->assertStringContainsString('$1,250', $html);
    }

    public function test_search_finds_an_event_by_the_professional_on_it(): void
    {
        $mine  = $this->event(['title' => 'Anniversary Dinner']);
        $other = $this->event(['title' => 'Office Lunch']);
        $this->book($mine, 'confirmed', 300);

        $list = $this->listOf($this->page(['search' => 'Priya']));

        $this->assertStringContainsString('Anniversary Dinner', $list);
        $this->assertStringNotContainsString('Office Lunch', $list);
    }

    public function test_the_when_filter_separates_upcoming_from_past(): void
    {
        $this->event(['title' => 'Next Month Gala', 'starts_at' => now()->addMonth()]);
        $this->event(['title' => 'Last Year Party', 'starts_at' => now()->subYear(), 'status' => 'completed']);

        $upcoming = $this->listOf($this->page(['when' => 'upcoming']));
        $this->assertStringContainsString('Next Month Gala', $upcoming);
        $this->assertStringNotContainsString('Last Year Party', $upcoming);

        $past = $this->listOf($this->page(['when' => 'past']));
        $this->assertStringContainsString('Last Year Party', $past);
        $this->assertStringNotContainsString('Next Month Gala', $past);
    }

    /** Export downloads what is on screen — the filter applies to it too. */
    public function test_export_downloads_the_filtered_list(): void
    {
        $this->event(['title' => 'Wedding Brunch']);
        $this->event(['title' => 'Board Retreat']);

        $response = $this->actingAs($this->client)->get(route('client.events.export', ['search' => 'Brunch']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Wedding Brunch', $csv);
        $this->assertStringNotContainsString('Board Retreat', $csv);

        // And the page's own Export button carries the filter.
        $this->assertStringContainsString(
            e(route('client.events.export', ['search' => 'Brunch'])),
            $this->page(['search' => 'Brunch']),
        );
    }

    /** Another client's events never appear in yours. */
    public function test_export_is_only_your_own_events(): void
    {
        $stranger = User::factory()->create();
        Event::create([
            'title' => 'Someone Else Entirely', 'status' => 'published', 'is_published' => true,
            'client_id' => $stranger->id, 'created_by' => $stranger->id,
        ]);

        $csv = $this->actingAs($this->client)->get(route('client.events.export'))->streamedContent();

        $this->assertStringNotContainsString('Someone Else Entirely', $csv);
    }

    public function test_the_schedule_and_payment_tabs_show_real_bookings(): void
    {
        $event = $this->event(['title' => 'Charity Gala']);
        $this->book($event, 'confirmed', 2200);

        $html = $this->page();

        $schedule = $this->between($html, 'data-subpane="schedule"', 'data-subpane="payments"');
        $this->assertStringContainsString('Priya Raghavan', $schedule);
        $this->assertStringContainsString('Charity Gala', $schedule);

        $payments = $this->between($html, 'data-subpane="payments"', '</table>');
        $this->assertStringContainsString('$2,200', $payments);
        $this->assertStringContainsString('Agreed, not yet paid', $payments);
    }

    /** The period dropdown changes the rail, and only the rail. */
    public function test_the_period_selector_scopes_the_rail(): void
    {
        $old = $this->event(['title' => 'Old One']);
        $old->forceFill(['created_at' => now()->subYears(2)])->save();
        $this->event(['title' => 'New One']);

        $all   = $this->between($this->page(), 'mg-rail-title">Event Overview', 'mg-rail-title">Professional Status');
        $month = $this->between($this->page(['period' => 'month']), 'mg-rail-title">Event Overview', 'mg-rail-title">Professional Status');

        $this->assertStringContainsString('<span class="num">2</span>', $all);
        $this->assertStringContainsString('<span class="num">1</span>', $month);
    }

    /**
     * The Open tile and the donut's Open slice say the same number.
     *
     * Found in the browser, not by a test: the tile said Open 2 and the donut
     * beside it said Open 8, because the donut counted a published event whose
     * date had passed as still open. Same word, same screen, two answers.
     */
    public function test_the_donut_and_the_tile_agree_on_what_open_means(): void
    {
        $this->event(['title' => 'Still Ahead', 'starts_at' => now()->addWeek()]);
        $this->event(['title' => 'Already Over', 'starts_at' => now()->subWeek()]);

        $html = $this->page();

        $tile = $this->between($html, 'mg-stat-label">Open', '</div></div>');
        $this->assertStringContainsString('mg-stat-value">1<', $tile);

        $donut = $this->between($html, 'mg-rail-title">Event Overview', 'mg-rail-title">Professional Status');
        $this->assertMatchesRegularExpression('/lbl">Open<\/span><span class="val">1 \(/', $donut);
        $this->assertStringContainsString('Ended, not booked', $donut);
    }

    /** Numbers the system cannot back are not drawn. */
    public function test_nothing_is_a_hardcoded_zero(): void
    {
        $this->event(['budget' => 1500]);
        $this->event(['budget' => 500]);
        $this->event(['budget' => 9999, 'status' => 'cancelled']);

        $html = $this->page();

        $this->assertStringNotContainsString('Rescheduled', $html);
        $this->assertStringNotContainsString('In progress', $html);
        // Details view Total Budget: the two live events, not the cancelled one.
        $this->assertStringContainsString('$2,000.00', $html);
    }

    /** The Events List pane only — the rows the filters act on. */
    private function listOf(string $html): string
    {
        return $this->between($html, 'data-subpane="events"', 'data-subpane="schedule"');
    }

    private function between(string $html, string $from, string $to): string
    {
        $a = strpos($html, $from);
        $this->assertNotFalse($a, "Could not find {$from}");
        $b = strpos($html, $to, $a + strlen($from));

        return substr($html, $a, ($b === false ? strlen($html) : $b) - $a);
    }
}
