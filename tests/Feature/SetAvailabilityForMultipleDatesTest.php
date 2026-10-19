<?php

namespace Tests\Feature;

use App\Domain\Calendar\Availability;
use App\Models\{AvailabilityDay, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sir Peter's availability review, 20 October 2026.
 *
 * "We have two sections representing essentially the same feature. The bottom
 * section is called 'Block a stretch of dates.' The right sidebar Quick Actions
 * section is called 'Set a stretch of dates.' Both appear to serve the same
 * purpose, but the terminology is inconsistent. 'Block' suggests that dates can
 * only be marked Unavailable, whereas the existing dropdown allows users to
 * select an availability status."
 *
 * He was right about the name, and the name is where the missing third choice
 * came from: the form began as blocking, and blocking only ever means one
 * answer. A single day has cycled through all three by click since it was
 * built, so the range was the odd one out.
 */
class SetAvailabilityForMultipleDatesTest extends TestCase
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

    private function apply(string $from, string $to, string $state)
    {
        return $this->actingAs($this->client)->post(route('client.calendar.availability.range'), [
            'from'  => $from,
            'to'    => $to,
            'state' => $state,
        ]);
    }

    private function dayCount(): int
    {
        return AvailabilityDay::where('user_id', $this->client->id)->count();
    }

    // ── The three choices ────────────────────────────────────

    public function test_a_range_can_be_set_unavailable(): void
    {
        $this->apply('2026-11-02', '2026-11-04', Availability::UNAVAILABLE)->assertRedirect();

        $this->assertSame(3, $this->dayCount());
    }

    public function test_a_range_can_be_set_available(): void
    {
        $this->apply('2026-11-02', '2026-11-04', Availability::AVAILABLE)->assertRedirect();

        $this->assertSame(3, $this->dayCount());
        $this->assertSame(
            Availability::AVAILABLE,
            AvailabilityDay::where('user_id', $this->client->id)->first()->state,
        );
    }

    /**
     * The third, which the range never had.
     *
     * "The form must support three choices: Available, Unavailable, and Not
     * Set." Not set is the absence of an answer rather than an answer, so it
     * clears those days instead of writing a third state to them.
     */
    public function test_a_range_can_be_taken_back_to_not_set(): void
    {
        $this->apply('2026-11-02', '2026-11-06', Availability::AVAILABLE);
        $this->assertSame(5, $this->dayCount());

        $this->apply('2026-11-03', '2026-11-05', 'not_set')->assertRedirect();

        $this->assertSame(2, $this->dayCount(), 'Only the three in the middle were cleared.');

        $left = AvailabilityDay::where('user_id', $this->client->id)
            ->get()
            ->map(fn ($d) => $d->day->format('Y-m-d'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['2026-11-02', '2026-11-06'], $left);
    }

    /** Clearing days that had no answer is not an error, and says so. */
    public function test_clearing_days_that_had_no_answer_says_nothing_changed(): void
    {
        $this->apply('2026-11-02', '2026-11-04', 'not_set')
            ->assertRedirect()
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'nothing changed'));
    }

    /** Anything that is not one of the three is refused. */
    public function test_an_unknown_state_is_refused(): void
    {
        $this->apply('2026-11-02', '2026-11-04', 'maybe')
            ->assertSessionHasErrors('state');

        $this->assertSame(0, $this->dayCount());
    }

    // ── The same records as a single click ───────────────────

    /**
     * "Ensure the feature updates the same availability records used by
     * individual calendar-day selections."
     *
     * They always did, which is worth a test rather than a claim: a range that
     * wrote somewhere of its own would show one answer on the grid and another
     * in the totals.
     */
    public function test_a_range_and_a_click_write_to_the_same_place(): void
    {
        $this->apply('2026-11-10', '2026-11-10', Availability::UNAVAILABLE);

        $this->actingAs($this->client)->post(route('client.calendar.availability'), [
            'day'   => '2026-11-11',
            'state' => Availability::UNAVAILABLE,
        ]);

        $this->assertSame(2, $this->dayCount());

        // And a click can take back what a range set.
        $this->actingAs($this->client)->post(route('client.calendar.availability'), [
            'day'   => '2026-11-10',
            'state' => Availability::UNAVAILABLE,
        ]);

        $this->assertSame(1, $this->dayCount(), 'Clicking the state a day holds clears it, range or not.');
    }

    /** A range replaces what was there, which is what the screen now warns. */
    public function test_a_range_replaces_an_existing_answer(): void
    {
        $this->apply('2026-11-02', '2026-11-04', Availability::AVAILABLE);
        $this->apply('2026-11-02', '2026-11-04', Availability::UNAVAILABLE);

        $this->assertSame(3, $this->dayCount(), 'Replaced, not added to.');

        foreach (AvailabilityDay::where('user_id', $this->client->id)->get() as $day) {
            $this->assertSame(Availability::UNAVAILABLE, $day->state);
        }
    }

    // ── What the page says ───────────────────────────────────

    private function page(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.check-availability.index', ['tab' => 'mine']))
            ->assertSuccessful()
            ->getContent();
    }

    /** Renamed everywhere, as he asked. */
    public function test_the_feature_is_named_for_what_it_does(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Set availability for multiple dates', $html);
        $this->assertStringContainsString('Apply to selected dates', $html);
        $this->assertStringNotContainsString('Block a stretch of dates', $html);
        $this->assertStringNotContainsString('Apply to these days', $html);
    }

    /** One form, not two. The duplicate shortcut is gone. */
    public function test_the_feature_appears_once(): void
    {
        $html = $this->page();

        /*
         * One FORM, not one mention.
         *
         * The name appears twice: on the form, and in the tips card pointing
         * at it. That is not the duplication he objected to. His own words
         * allow it: "unless each placement serves a clearly different purpose."
         * A tip saying the feature exists is not a second copy of the control,
         * and counting the words rather than the forms would have made this
         * test demand the tip be deleted.
         */
        $this->assertSame(
            1,
            substr_count($html, route('client.calendar.availability.range')),
            'He asked for one date-range form, directly under the calendar.',
        );

        $this->assertStringNotContainsString('Set a stretch of dates', $html);
        $this->assertStringNotContainsString('Quick actions', $html);
    }

    /** All three choices are offered on the form itself. */
    public function test_the_form_offers_all_three_choices(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('value="' . Availability::AVAILABLE . '"', $html);
        $this->assertStringContainsString('value="' . Availability::UNAVAILABLE . '"', $html);
        $this->assertStringContainsString('value="not_set"', $html);
    }

    /**
     * "If a selected date already has an availability status, inform the user
     * that applying the new status will replace it. Do not silently modify
     * confirmed bookings or event records."
     */
    public function test_the_form_warns_that_it_replaces_and_leaves_bookings_alone(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('will be replaced', $html);
        $this->assertStringContainsString('events and bookings are not touched', $html);
    }

    /** First and last day stay, as he asked. */
    public function test_the_two_date_selectors_remain(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('First day', $html);
        $this->assertStringContainsString('Last day', $html);
    }

    // ── The totals he wanted refreshed ───────────────────────

    /**
     * "After applying the change, immediately refresh the calendar colors,
     * status indicators, and monthly availability totals."
     *
     * The form posts and comes back to the page, so there is nothing to
     * refresh asynchronously; what matters is that the figure is counted from
     * the same records rather than cached beside them.
     */
    public function test_the_monthly_total_follows_the_range_straight_away(): void
    {
        $first = now()->startOfMonth()->format('Y-m-d');
        $third = now()->startOfMonth()->addDays(2)->format('Y-m-d');

        $this->apply($first, $third, Availability::AVAILABLE);

        $this->assertStringContainsString('3</b>', $this->page());
    }
}
