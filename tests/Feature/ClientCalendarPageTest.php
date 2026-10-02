<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 24 September: "add a left column webtitle as Calendar &
 * Availability for a full webpage instead of search around my events webpage
 * to find it, this way its a simple more direct way."
 *
 * The calendar already existed, inside My Events and on the dashboard. What
 * it lacked was an address, so there was no way to arrive at it directly.
 *
 * The part this holds is the part that would otherwise rot: both screens draw
 * the SAME calendar, from one partial, so a colour or a stage cannot come to
 * mean one thing on one page and something else on the other. That is the
 * fault this project keeps finding, and it is cheaper to hold than to find.
 */
class ClientCalendarPageTest extends TestCase
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

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(3),
        ], $attributes));
    }

    /** It has an address of its own, and the client's events are on it. */
    public function test_the_calendar_is_a_page_in_its_own_right(): void
    {
        $this->event();

        $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertOk()
            ->assertSee('Calendar &amp; Availability', false)
            ->assertSee('Rivera Wedding');
    }

    /** And it is reachable from the left menu, which was the whole request. */
    public function test_the_left_menu_links_to_it(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee(route('client.calendar.index'), false);
    }

    /** Somebody else's event is not on it. */
    public function test_it_shows_only_this_clients_events(): void
    {
        $other = User::factory()->create(['primary_role' => 'client']);
        $other->assignRole('client');

        Event::create([
            'title'      => 'Someone Elses Party',
            'client_id'  => $other->id,
            'created_by' => $other->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(3),
        ]);

        $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertOk()
            ->assertDontSee('Someone Elses Party');
    }

    /**
     * One calendar, drawn once. If this fails, somebody has written a second
     * copy of the month grid and the two will drift.
     */
    public function test_both_screens_draw_the_same_calendar(): void
    {
        $partial = base_path('resources/views/client/_calendar.blade.php');

        $this->assertFileExists($partial, 'The shared calendar partial is gone.');

        foreach ([
            'resources/views/client/events/index.blade.php',
            'resources/views/client/calendar/index.blade.php',
        ] as $view) {
            $markup = file_get_contents(base_path($view));

            $this->assertStringContainsString("client._calendar", $markup,
                "{$view} no longer includes the shared calendar.");

            $this->assertStringNotContainsString('cl-calendar-day ', $markup,
                "{$view} has its own copy of the month grid again.");
        }
    }

    /** The tabs that have no data say so rather than opening onto nothing. */
    public function test_the_unbuilt_tabs_are_honest_about_it(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertOk()
            ->assertSee('My Availability')
            ->assertSee('Waiting on their side')
            ->assertSee('Nothing on GigResource records when somebody is free', false);
    }
}
