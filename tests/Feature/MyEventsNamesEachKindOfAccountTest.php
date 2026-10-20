<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\{ServiceArea, SupplierProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * My Events stops calling everybody a professional.
 *
 * Sir Peter, 20 October: "Influencer schedule will be add on my events like
 * professional schedule."
 *
 * Checking what was actually wrong: the bookings were already there. The query
 * behind that tab is every booking the client holds, with no filter on the
 * other party's role, so an influencer booking would have appeared the day one
 * existed. What was wrong is that the tab was called "Professional Schedule",
 * the column was headed "Professional", the empty state said "No professionals
 * booked yet", and nothing on the row said which kind of account it was. An
 * influencer would have been shown, and shown as a professional.
 *
 * Hired Talent already did this properly, with its Everyone / Professionals /
 * Influencers tabs and a noun per supplier. My Events was the screen left
 * behind.
 *
 * The second half of his sentence is NOT done, and cannot be done from the
 * client side: there is no way to book an influencer yet. That is held in
 * test_the_schedule_is_ready_for_influencers_that_do_not_exist_yet below, which
 * is deliberately written so it keeps passing once they do.
 */
class MyEventsNamesEachKindOfAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = $this->person('client');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function bookingWith(User $supplier): Booking
    {
        $service = Category::firstOrCreate(
            ['slug' => 'brand-partnership'],
            ['name' => 'Brand Partnership', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
        ]);

        return Booking::create([
            'client_id'   => $this->client->id,
            'supplier_id' => $supplier->id,
            'event_id'    => $event->id,
            'category_id' => $service->id,
            'status'      => 'confirmed',
            'created_by'  => $this->client->id,
        ]);
    }

    private function page(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.events.index'))
            ->assertSuccessful()
            ->getContent();
    }

    // ── What the screen calls things ─────────────────────────

    /**
     * No heading on the page claims everyone booked is a professional.
     *
     * The screen as a whole, not one string: the tab, the column headers, the
     * status rails and the empty state each said it in their own words, which
     * is why fixing one of them would have left the screen still saying it.
     */
    public function test_no_heading_files_everybody_as_a_professional(): void
    {
        $this->bookingWith($this->person('influencer'));

        $html = $this->page();

        foreach ([
            'Professional Schedule',
            'Professional Status',
            'Recent Professional Activity',
            'No professionals booked yet',
            'Professionals booked',
        ] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "The screen still says \"{$claim}\".");
        }
    }

    /** An influencer the client booked is named as an influencer. */
    public function test_a_booked_influencer_is_shown_as_an_influencer(): void
    {
        $influencer = $this->person('influencer');

        $this->bookingWith($influencer);

        $html = $this->page();

        $this->assertStringContainsString($influencer->name, $html, 'The influencer is not on the schedule at all.');
        $this->assertStringContainsString('Influencer', $html, 'They are on it, but not named as one.');
    }

    /** And a professional is still named as a professional. */
    public function test_a_booked_professional_is_still_shown_as_a_professional(): void
    {
        $pro = $this->person('professional');

        $this->bookingWith($pro);

        $html = $this->page();

        $this->assertStringContainsString($pro->name, $html);
        $this->assertStringContainsString('Professional', $html);
    }

    /**
     * Both kinds on one schedule, each named correctly.
     *
     * This is the case the screen could not show before: it had one word for
     * everybody, so the two were indistinguishable once booked.
     */
    public function test_the_two_kinds_appear_together_and_are_told_apart(): void
    {
        $pro        = $this->person('professional');
        $influencer = $this->person('influencer');

        $this->bookingWith($pro);
        $this->bookingWith($influencer);

        $html = $this->page();

        $this->assertStringContainsString($pro->name, $html);
        $this->assertStringContainsString($influencer->name, $html);

        $this->assertSame(
            ['Influencer', 'Professional'],
            $this->chipsOn($html),
            'The schedule does not tell the two kinds apart.',
        );
    }

    /** The distinct words the role chips on the page are showing. */
    private function chipsOn(string $html): array
    {
        preg_match_all('/<span class="role-chip"[^>]*>\s*([A-Za-z]+)\s*<\/span>/', $html, $m);

        $words = array_values(array_unique($m[1]));
        sort($words);

        return $words;
    }

    /**
     * The chip is in that account type's own colour, from the one table.
     *
     * Sir Peter set these in stone on 23 September, and the point of reading
     * them from RoleColours is that a change of mind is one edit.
     */
    public function test_the_chip_uses_the_colour_set_for_that_role(): void
    {
        $this->bookingWith($this->person('influencer'));

        $html = $this->page();

        $this->assertStringContainsString(\App\Support\RoleColours::strongFor('influencer'), $html);
    }

    // ── One source for the word ──────────────────────────────

    /**
     * The schedule and the profile link cannot disagree about who somebody is.
     *
     * Both read SupplierProfile, so a row cannot call somebody an influencer
     * and then offer to open their professional profile.
     */
    public function test_the_word_comes_from_the_same_place_as_the_profile_link(): void
    {
        $influencer = $this->person('influencer');

        $this->assertSame('influencer', SupplierProfile::noun($influencer));

        $rows = \App\Domain\Finance\PaymentTracker::rows($this->client);

        $this->bookingWith($influencer);

        $rows = \App\Domain\Finance\PaymentTracker::rows($this->client->fresh());

        $this->assertNotEmpty($rows);
        $this->assertSame('influencer', $rows[0]['noun']);
    }

    // ── The half that is not done ────────────────────────────

    /**
     * The schedule is ready for influencers. Nothing can book one yet.
     *
     * There is no influencer account on the platform, no influencer package,
     * and the request forms do not reach influencers. So the screen is correct
     * and will stay empty of them until the influencer side opens, which is not
     * a client-side change.
     *
     * Written to keep passing once that lands: it asserts that the schedule
     * takes whatever the booking table holds rather than asserting a count.
     */
    public function test_the_schedule_is_ready_for_influencers_that_do_not_exist_yet(): void
    {
        $influencer = $this->person('influencer');

        $this->assertSame(
            0,
            Booking::where('client_id', $this->client->id)->count(),
            'A client now has a booking before anything created one.',
        );

        // The table has never filtered on the other party's role, which is why
        // the fix was the words rather than the query.
        $this->bookingWith($influencer);

        $this->assertStringContainsString($influencer->name, $this->page());
    }
}
