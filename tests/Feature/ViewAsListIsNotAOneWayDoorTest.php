<?php

namespace Tests\Feature;

use App\Domain\Calendar\Availability;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sir Peter, 20 October, on My Availability's "View as list":
 *
 * "Once a user uses or clicks this button, the client can not go back to the
 * original calendar like setup, but why not?"
 *
 * He was right, and it was worse than it looked. The button sent you to the
 * CALENDAR page in list view, which is a different page with a different
 * purpose, and left no way back to the availability grid at all. The list is a
 * toggle on this page now.
 */
class ViewAsListIsNotAOneWayDoorTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.check-availability.index', $query + ['tab' => 'mine']))
            ->assertSuccessful()
            ->getContent();
    }

    // ── There and back ───────────────────────────────────────

    /** The grid is what you land on. */
    public function test_the_grid_is_the_default(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('<table class="av-grid">', $html);
        $this->assertStringNotContainsString('<div class="av-list">', $html);
    }

    /** And the list is a view of the same page, not another page. */
    public function test_the_list_renders_on_the_same_page(): void
    {
        $html = $this->page(['avview' => 'list']);

        $this->assertStringContainsString('<div class="av-list">', $html);
        $this->assertStringNotContainsString('<table class="av-grid">', $html);
        $this->assertStringContainsString('My Availability', $html, 'Still on the availability page.');
    }

    /**
     * The whole of his complaint: a way back.
     *
     * Against the markup rather than the word "Grid", which appears in the
     * stylesheet and the tips as well.
     */
    public function test_the_list_offers_a_way_back_to_the_grid(): void
    {
        $html = $this->page(['avview' => 'list']);

        $back = route('client.check-availability.index', ['tab' => 'mine']);

        $this->assertStringContainsString('>Grid</a>', $html);
        $this->assertStringContainsString($back, $html, 'The way back must point at the grid.');
    }

    /** And the grid offers the way out, so the toggle reads both ways. */
    public function test_the_grid_offers_the_way_to_the_list(): void
    {
        $this->assertStringContainsString('>List</a>', $this->page());
    }

    /** Whichever is open is marked as the one you are on. */
    public function test_the_open_view_is_marked(): void
    {
        $this->assertStringContainsString('aria-current="true">Grid</a>', $this->page());
        $this->assertStringContainsString('aria-current="true">List</a>', $this->page(['avview' => 'list']));
    }

    // ── The quick action that caused it ──────────────────────

    /**
     * It used to leave for the calendar page. Now it stays.
     *
     * That was the actual one-way door: not a missing back button, but a link
     * that quietly moved you to a different screen.
     */
    public function test_the_quick_action_stays_on_this_page(): void
    {
        $html = $this->page();

        // The month it carries comes from the calendar's own anchor, so only
        // the destination is asserted here.
        $this->assertMatchesRegularExpression(
            '~/client/availability\?[^"]*avview=list~',
            $html,
            'View as list must stay on the availability page.',
        );

        $this->assertStringNotContainsString(
            'calview=list',
            $html,
            'It must not send the client to the calendar page any more.',
        );
    }

    // ── The list is the same month, and still works ──────────

    /** Both views answer about the same days, and the list drops the padding. */
    public function test_the_list_holds_this_month_and_nothing_either_side(): void
    {
        $html = $this->page(['avview' => 'list', 'cal' => '2026-11-15']);

        $this->assertStringContainsString('Nov 1', $html);
        $this->assertStringContainsString('Nov 30', $html);
        // The grid pads its first and last rows with neighbouring days; a list
        // has no rows to fill, so they would only read as padding.
        $this->assertStringNotContainsString('Oct 31', $html);
        $this->assertStringNotContainsString('Dec 1', $html);
    }

    /** A day can still be set from the list, with the same one click. */
    public function test_a_day_can_be_marked_from_the_list(): void
    {
        $day = now()->startOfMonth()->addDays(9)->format('Y-m-d');

        $this->actingAs($this->client)
            ->post(route('client.calendar.availability'), [
                'day'   => $day,
                'state' => Availability::AVAILABLE,
                'back'  => route('client.check-availability.index', ['tab' => 'mine', 'avview' => 'list']),
            ])
            ->assertRedirect();

        $this->assertStringContainsString('Available', $this->page(['avview' => 'list']));
    }

    /** Marking a day comes back to the list, not to the grid. */
    public function test_marking_a_day_returns_to_the_view_it_came_from(): void
    {
        $back = route('client.check-availability.index', ['tab' => 'mine', 'avview' => 'list']);

        $this->actingAs($this->client)
            ->post(route('client.calendar.availability'), [
                'day'   => now()->startOfMonth()->addDays(4)->format('Y-m-d'),
                'state' => Availability::UNAVAILABLE,
                'back'  => $back,
            ])
            ->assertRedirect($back);
    }
}
