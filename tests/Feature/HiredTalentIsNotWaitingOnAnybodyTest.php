<?php

namespace Tests\Feature;

use App\Domain\HiredTalent;
use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who you hired is a booking, and that was never the thing we were waiting on.
 *
 * The Calendar page carried "Hired Professionals" and "Hired Influencers" as
 * two switched-off tabs, and the reason written on the first was
 * "Professionals do not record their available dates yet". That is the reason
 * Check Availability is waiting. It is not the reason this one was: a client's
 * own bookings are something the system knows completely.
 *
 * Sir Peter asked for them as one page, "because once someone is hired, the
 * Client's purpose is essentially the same regardless of role", while his
 * sidebar and mockups show two. One page with a filter satisfies both.
 */
class HiredTalentIsNotWaitingOnAnybodyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Event $event;

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

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Mix Masters']);
        $this->pro->assignRole('professional');

        $this->event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(20),
            'city'       => 'Baltimore',
            'state'      => 'MD',
            'venue'      => 'Ellicott City Conference Center',
        ]);
    }

    private int $svc = 0;

    private function book(string $status = 'confirmed', ?User $supplier = null): Booking
    {
        // One booking per event, supplier and service: the table enforces it,
        // so a test wanting several gives each its own service.
        $this->svc++;
        $category = Category::create([
            'name' => 'Service ' . $this->svc,
            'slug' => 'svc-' . $this->svc,
            'kind' => Category::SERVICE_CATEGORY,
            'is_active' => true,
        ]);

        return Booking::create([
            'event_id'    => $this->event->id,
            'category_id' => $category->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => ($supplier ?? $this->pro)->id,
            'status'      => $status,
            'price'       => 1400,
        ]);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.hired-talent.index', $query))
            ->assertSuccessful()
            ->getContent();
    }

    /** The page exists and names who was booked. */
    public function test_it_lists_who_the_client_booked(): void
    {
        $this->book();

        $html = $this->page();

        $this->assertStringContainsString('Hired Talent', $html);
        $this->assertStringContainsString('Mix Masters', $html);
        $this->assertStringContainsString('Johnson Wedding', $html);
    }

    /** Grouped under the event, which is how the client thinks about it. */
    public function test_it_groups_by_the_event(): void
    {
        $this->book();

        $html = $this->page();

        $this->assertStringContainsString($this->event->starts_at->format('F j, Y'), $html);
        $this->assertStringContainsString('Ellicott City Conference Center', $html);
    }

    /**
     * A client is never their own supplier.
     *
     * An older self-booking would otherwise appear here as somebody they hired.
     */
    public function test_a_self_booking_is_not_somebody_you_hired(): void
    {
        // The model refuses to save one, which is right. Older rows predate
        // that rule, so this writes the row the way those arrived.
        \Illuminate\Support\Facades\DB::table('bookings')->insert([
            'event_id'    => $this->event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->client->id,
            'status'      => 'confirmed',
            'price'       => 1400,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $rows = HiredTalent::rows($this->client);

        $this->assertCount(0, $rows, 'The client booking themselves is bad data, not a hire.');
    }

    /** Finished work moves to Past, live work stays in Active. */
    public function test_active_and_past_are_told_apart(): void
    {
        $this->book('confirmed');
        $this->book('completed');
        $this->book('cancelled');

        $counts = HiredTalent::summary(HiredTalent::rows($this->client));

        $this->assertSame(3, $counts['all']);
        $this->assertSame(1, $counts['active']);
        $this->assertSame(2, $counts['past'], 'Completed and cancelled are both behind you.');
    }

    /**
     * The influencer filter is shown at zero, not hidden.
     *
     * "Nobody yet" is a real answer to "who have I hired", and hiding the
     * filter would make the page look as though influencers were never part
     * of it.
     */
    public function test_the_influencer_side_says_it_is_empty_rather_than_vanishing(): void
    {
        $this->book();

        $html = $this->page(['who' => 'influencers']);

        $this->assertStringContainsString('Influencers (0)', $html);
        $this->assertStringContainsString('No influencers yet', $html);
        $this->assertStringContainsString('cannot be hired through the site yet', $html);
    }

    /**
     * Nothing active is not the same as nothing found.
     *
     * Every booking being finished is good news; saying "nothing matches that"
     * reads like a broken filter.
     */
    public function test_finished_work_reads_as_finished_not_as_no_result(): void
    {
        $this->book('completed');

        $html = $this->page(['show' => 'active']);

        $this->assertStringContainsString('Nothing on right now', $html);
        $this->assertStringNotContainsString('Nothing matches that', $html);
    }

    /**
     * Money owed reads as owed, not as settled.
     *
     * The figures come from PaymentTracker, which already folds an agreement
     * and its booking into one row so the same money is never counted twice.
     */
    public function test_money_still_owed_says_so(): void
    {
        $this->book();

        $html = $this->page();

        // The label under the figure. "Due" where there is a balance,
        // "Nothing owed" where there is not.
        $this->assertStringContainsString('>Due', $html);
        $this->assertStringNotContainsString('Nothing owed', $html);
    }

    /**
     * The money is paired by the professional's id, never by their name.
     *
     * Two suppliers on one event sharing a name would otherwise be handed
     * each other's figures, and names are not ours to make unique.
     */
    public function test_two_suppliers_sharing_a_name_keep_their_own_money(): void
    {
        $twin = User::factory()->create(['primary_role' => 'professional', 'name' => 'Mix Masters']);
        $twin->assignRole('professional');

        $this->book('confirmed');                 // the original Mix Masters
        $this->book('confirmed', $twin);          // a different one, same name

        $rows = HiredTalent::rows($this->client);

        $this->assertCount(2, $rows);
        $this->assertNotSame(
            $rows[0]['supplier']['id'],
            $rows[1]['supplier']['id'],
            'Two different people, however they are named.',
        );
        foreach ($rows as $r) {
            $this->assertNotNull($r['money'], 'Each should have found its own payment row.');
        }
    }

    /**
     * Readiness is the agreement's own progress, not a guess.
     *
     * Sir Peter's mockup shows "3 of 5 complete, Next: Sound Check". Nothing
     * records steps towards an event, so that exact bar would be invented.
     * What IS recorded is the finalize wizard's seven steps, each with the
     * date it was completed, so the card is filled from those instead.
     */
    public function test_readiness_comes_from_the_agreement_steps(): void
    {
        $booking = $this->book();

        \App\Models\Finalization::create([
            'event_id'        => $this->event->id,
            'booking_id'      => $booking->id,
            'client_id'       => $this->client->id,
            'supplier_id'     => $this->pro->id,
            'bid_reviewed_at' => now(),
            'scope_agreed_at' => now(),
        ]);

        $row = HiredTalent::rows($this->client)->first();

        $this->assertSame(2, $row['readiness']['done']);
        $this->assertSame(count(\App\Models\Finalization::STEPS), $row['readiness']['total']);
        $this->assertSame('Price & Fees', $row['readiness']['next']);

        $html = $this->page();
        $this->assertStringContainsString('2 of 7 complete', $html);
        $this->assertStringContainsString('Next: <b>Price &amp; Fees</b>', $html);
    }

    /**
     * And where there is no agreement there is no bar.
     *
     * An empty one would read as "not started" rather than "not applicable".
     */
    public function test_a_booking_without_an_agreement_has_no_bar(): void
    {
        $this->book();

        $row = HiredTalent::rows($this->client)->first();

        $this->assertNull($row['readiness']);
        $this->assertStringContainsString('nothing to track here', $this->page());
    }

    /** The calendar's dead tab now goes somewhere. */
    public function test_the_calendar_tab_is_a_link_again(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(
            '<a href="' . route('client.hired-talent.index') . '" class="cal-tab">',
            $html,
        );
        $this->assertStringNotContainsString('<b>Hired Influencers</b>', $html);
    }

    /**
     * Check Availability is its own page now, and carries its own honesty.
     *
     * The old wording blamed the professional side for the Hired tab as well,
     * which was never its reason. Only the availability question waits on
     * them, and it now says so on arrival rather than as a tooltip on a tab
     * nobody can open.
     */
    public function test_check_availability_moved_its_honesty_onto_the_page(): void
    {
        $tab = $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('<b>Check Availability</b>', $tab);
        $this->assertStringNotContainsString('Waiting on their side', $tab);

        $page = $this->actingAs($this->client)
            ->get(route('client.check-availability.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('We can tell you who is busy, not who is free.', $page);
    }

    /** The page names which kind you are looking at, as his two pages do. */
    public function test_the_title_names_the_kind(): void
    {
        $this->book();

        $this->assertStringContainsString('Hired Professionals', $this->page(['who' => 'professionals']));
        $this->assertStringContainsString('Hired Influencers', $this->page(['who' => 'influencers']));
        $this->assertStringContainsString('>Hired Talent<', $this->page());
    }

    /** Each event says how many it holds, and folds away. */
    public function test_an_event_counts_its_people_and_can_be_folded(): void
    {
        $this->book();
        $this->book();

        $html = $this->page();

        $this->assertStringContainsString('2 providers', $html);
        $this->assertStringContainsString('data-ht-group', $html);
        $this->assertStringContainsString('.ht-ev[aria-expanded="false"] + .ht-ev-rows { display: none; }', $html);
    }

    /** Book Again sends them a fresh request, which is what it means here. */
    public function test_book_again_goes_to_a_direct_request_for_that_person(): void
    {
        $this->book();

        $this->assertStringContainsString(
            route('client.direct-offers.create', ['pro' => $this->pro->id]),
            $this->page(),
        );
    }

    /**
     * Release Milestone Payment is on his menu and is not on ours.
     *
     * Nothing in the system releases a milestone: there is no route, no model
     * and no money movement behind it. Drawing the item would be a menu entry
     * that does nothing.
     */
    public function test_no_milestone_release_is_offered(): void
    {
        $this->assertStringNotContainsString('Release Milestone', $this->page());
    }
}
