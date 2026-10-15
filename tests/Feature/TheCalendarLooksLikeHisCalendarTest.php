<?php

namespace Tests\Feature;

use App\Models\{Event, User};
use App\Support\ClientCalendar;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The calendar brought up to the screen Sir Peter drew.
 *
 * Ali, 12 October: "bhai pages to dekh kitna difference hai." It was a fair
 * complaint. His image has a page header with Create Event on it, a List view
 * beside Month and Week, each day showing when and where rather than a bare
 * title, and a tips card down the side. All of that is on real data and is
 * now there.
 *
 * Two things on his image are deliberately NOT: Sync to Google / Outlook, and
 * Report Issue. Neither exists. A client has no route to a dispute at all,
 * disputes being an admin surface, and there is no calendar sync of any kind.
 * Drawing those buttons would be exactly the thing he complained about three
 * days earlier: something that looks clickable and does nothing.
 */
class TheCalendarLooksLikeHisCalendarTest extends TestCase
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
        $this->client = User::findOrFail($this->client->id);

        Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'is_published' => true,
            'starts_at'  => now()->startOfMonth()->addDays(9)->setTime(17, 0),
            'ends_at'    => now()->startOfMonth()->addDays(9)->setTime(23, 0),
            'city'       => 'Ellicott City',
            'state'      => 'MD',
        ]);
    }

    private function page(array $q = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.calendar.index', $q))
            ->assertSuccessful()
            ->getContent();
    }

    /**
     * My Availability, which lives on the Availability page since 12 October.
     *
     * Sir Peter: "I want the clients calendar, Availability, and Hired as
     * separate webpages", with the three availabilities as tabs on the second
     * one. It was the calendar's second tab until then.
     */
    private function mine(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.check-availability.index', ['tab' => 'mine']))
            ->assertSuccessful()
            ->getContent();
    }

    /** A header with the one action that belongs on a calendar. */
    public function test_the_page_has_a_header_and_a_way_to_create_an_event(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('cal-page-head', $html);
        $this->assertStringContainsString('Create Event', $html);
        $this->assertStringContainsString(route('client.post-event.choose'), $html);
    }

    /** A day says when, what and where, which is what his image shows. */
    public function test_a_day_shows_the_time_and_the_place_not_just_a_title(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Johnson Wedding', $html);
        $this->assertStringContainsString('ec-ev-t', $html);
        $this->assertStringContainsString('ec-ev-w', $html);
        $this->assertStringContainsString('Ellicott City, MD', $html);
    }

    /**
     * The calendar query carries the columns the cell prints.
     *
     * They were not selected before, so asking for a place would have fired a
     * query for every event on the month, or printed nothing.
     */
    public function test_the_calendar_loads_where_as_well_as_when(): void
    {
        $built = ClientCalendar::build($this->client, 'month', now()->toDateString());
        $event = $built['byDate']->flatten()->first();

        $this->assertNotNull($event);
        foreach (['city', 'state', 'location'] as $column) {
            $this->assertArrayHasKey($column, $event->getAttributes(), "The calendar did not load {$column}.");
        }
    }

    /** List is offered, and it lists. */
    public function test_a_month_can_be_read_as_a_list(): void
    {
        $this->assertStringContainsString('>List</a>', $this->page());

        $html = $this->page(['calview' => 'list']);

        $this->assertStringContainsString('cll-day', $html);
        $this->assertStringContainsString('Johnson Wedding', $html);
    }

    /**
     * And the figures under it name the range they counted.
     *
     * A list spans the month it is listing. Before this it fell through to
     * the day label and said "Events today" over a month's worth of them.
     */
    public function test_the_list_counts_a_month_and_says_so(): void
    {
        $html = $this->page(['calview' => 'list']);

        $this->assertStringContainsString('Events this month', $html);
        $this->assertStringNotContainsString('Events today', $html);
    }

    /** The tips card is there, and says different things on each tab. */
    public function test_each_tab_has_its_own_tips(): void
    {
        $this->assertStringContainsString('Calendar tips', $this->page());
        $this->assertStringContainsString('the easiest view on a phone', $this->page());

        $this->assertStringContainsString('Not set is not the same as free', $this->mine());
    }

    /** Quick actions, on the tab they belong to and nowhere else. */
    public function test_quick_actions_belong_to_my_availability(): void
    {
        $this->assertStringNotContainsString('Quick actions', $this->page());

        $avail = $this->mine();
        $this->assertStringContainsString('Quick actions', $avail);
        $this->assertStringContainsString('Set a stretch of dates', $avail);
        $this->assertStringContainsString('View as list', $avail);
    }

    /** The key sits above the grid it explains. */
    public function test_the_availability_key_is_above_the_calendar(): void
    {
        $html = $this->mine();

        // Against the grid ELEMENT, not the string "av-grid", which appears
        // first in the stylesheet at the top of the page.
        $grid = strpos($html, '<table class="av-grid">');
        $key  = strpos($html, '<div class="av-legend">');

        $this->assertNotFalse($grid);
        $this->assertNotFalse($key);
        $this->assertLessThan(
            $grid,
            $key,
            'The legend explains colours the reader meets below it, so it goes first.',
        );
    }

    /**
     * Nothing on the page promises something that does not exist.
     *
     * Sir Peter's image has Sync to Google / Outlook and Report Issue. There
     * is no calendar sync, and a client has no route to a dispute. Both would
     * be dead buttons, which is the thing he asked us to stop shipping.
     */
    public function test_no_button_is_drawn_for_a_feature_we_do_not_have(): void
    {
        foreach ([$this->page(), $this->mine()] as $html) {
            foreach (['Sync to Google', 'Sync Calendar', 'Report Issue'] as $absent) {
                $this->assertStringNotContainsString($absent, $html);
            }
        }
    }
}
