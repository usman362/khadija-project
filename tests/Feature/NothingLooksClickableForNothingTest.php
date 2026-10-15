<?php

namespace Tests\Feature;

use App\Models\{Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A square that shows a pointer has somewhere to go.
 *
 * Sir Peter: "if there are those small cards in the main center body, if they
 * need to be clicked for any purpose, please make sure that each one actually
 * works or leads to the correct next step or webpage."
 *
 * Walking the client pages in a browser for elements with a pointer cursor and
 * no link, button or handler behind them turned up exactly two, and both were
 * calendar squares: the mini calendar on the dashboard and the month grid on
 * the Calendar tab. Each showed a pointer and a hover border and then did
 * nothing at all when clicked.
 *
 * The two were answered differently because they are different questions. On
 * the dashboard there is somewhere to go, so the date opens the calendar on
 * that day. On the calendar you are already there, so the square stops
 * claiming to be a control and the entries inside it stay the links.
 */
class NothingLooksClickableForNothingTest extends TestCase
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
            'starts_at'  => now()->addDays(9),
            'city'       => 'Baltimore',
            'state'      => 'MD',
        ]);
    }

    private function page(string $route): string
    {
        return $this->actingAs($this->client)
            ->get(route($route))
            ->assertSuccessful()
            ->getContent();
    }

    /** On the dashboard, the date goes to that day on the calendar. */
    public function test_a_date_on_the_mini_calendar_opens_that_day(): void
    {
        $html = $this->page('client.dashboard');

        $this->assertStringContainsString('<a class="od-cal-num" href="', $html);
        $this->assertStringContainsString(
            route('client.calendar.index', ['cal' => now()->format('Y-m-d')]),
            $html,
            "Today's square should link to the calendar on today.",
        );
    }

    /**
     * The square around it does not pretend to be a control.
     *
     * The pointer was the whole complaint: it promised a click that no code
     * was listening for.
     */
    public function test_the_mini_calendar_square_is_not_a_button(): void
    {
        $html = $this->page('client.dashboard');

        $rule = substr($html, strpos($html, '.od-cal-day {'));
        $rule = substr($rule, 0, strpos($rule, '}'));

        $this->assertStringNotContainsString(
            'cursor: pointer',
            $rule,
            'The mini calendar square shows a pointer again and still does nothing.',
        );
    }

    /** Same on the calendar page's own month grid. */
    public function test_the_month_grid_square_is_not_a_button_either(): void
    {
        $html = $this->page('client.calendar.index');

        // The base rule, not the two light-theme overrides above it, each of
        // which also contains ".cl-calendar-day {" and would be measured
        // instead by a plain search.
        $at = strpos($html, "\n        .cl-calendar-day {");
        $this->assertNotFalse($at, 'The calendar day rule has moved.');

        $rule = substr($html, $at, strpos(substr($html, $at), '}'));

        $this->assertStringContainsString('height: 80px', $rule, 'Measured the wrong rule.');
        $this->assertStringNotContainsString('cursor: pointer', $rule);
    }

    /**
     * My Availability is left alone.
     *
     * That grid is a different one, made of real buttons, and clicking a date
     * there cycles it between available, unavailable and not set. Taking the
     * pointer off the Calendar tab must not reach it.
     */
    public function test_setting_your_own_availability_still_clicks(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.check-availability.index', ['tab' => 'mine']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('av-day', $html);
        $this->assertMatchesRegularExpression(
            '/<button[^>]*av-(day|cell|btn)/i',
            $html,
            'A date on My Availability should still be a button.',
        );
    }
}
