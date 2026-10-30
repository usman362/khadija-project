<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Raising a problem begins at the job it is about.
 *
 * Sir Peter, 30 October: "I had a big issue trying to file my own dispute
 * (button) or leading a dispute page, can we make it easier somewhere logical
 * to get to".
 *
 * Both the page and the menu entry already existed. What was missing was any
 * way in from the job: somebody with a problem had to go to Disputes cold and
 * find their own booking in a dropdown, which on an event with five
 * professionals is the hardest question on the form.
 *
 * So the link is on the booking, on the event beside the professional it
 * concerns, and on the professional's own gig page — and it arrives knowing
 * which booking it means.
 */
class ReportingAProblemStartsFromTheJobTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Event $event;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $this->event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(20),
        ]);

        $this->booking = $this->booking('confirmed');
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

    private function booking(string $status, ?User $supplier = null, string $service = 'wedding-dj'): Booking
    {
        return Booking::create([
            'event_id'    => $this->event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => ($supplier ?? $this->professional)->id,
            'category_id' => Category::firstOrCreate(
                ['slug' => $service],
                ['name' => ucwords(str_replace('-', ' ', $service)), 'kind' => Category::SERVICE, 'is_active' => true],
            )->id,
            'status'      => $status,
            'price'       => 4000,
        ]);
    }

    private function link(?Booking $booking = null): string
    {
        return route('disputes.create', ['booking' => ($booking ?? $this->booking)->id]);
    }

    // ── A way in from the job ────────────────────────────────

    public function test_the_booking_card_offers_it(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertSee('Report a problem', false)
            ->assertSee($this->link(), false);
    }

    /** On the event, beside the professional it concerns. */
    /**
     * On the event, beside the professional it concerns.
     *
     * Under Event Details, because that is the tab the booking rows live on:
     * the event opens on Proposals, which lists bids rather than bookings,
     * and there is nothing there to file a case against.
     */
    public function test_the_event_offers_it_on_the_right_row(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.events.show', ['event' => $this->event, 'tab' => 'overview']))
            ->assertSuccessful()
            ->assertSee($this->link(), false);
    }

    public function test_the_professionals_gig_page_offers_it(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $this->event))
            ->assertSuccessful()
            ->assertSee('Report a problem', false)
            ->assertSee($this->link(), false);
    }

    /** Not on a booking no case can be filed against. */
    public function test_a_booking_still_being_requested_does_not_offer_it(): void
    {
        $this->booking->forceFill(['status' => 'requested'])->save();

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertDontSee($this->link());
    }

    // ── And it knows which job ───────────────────────────────

    public function test_the_form_arrives_with_the_booking_already_chosen(): void
    {
        $html = $this->actingAs($this->client)
            ->get($this->link())
            ->assertSuccessful()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="' . $this->booking->id . '"[^>]*\bselected\b/',
            $html,
            'Arriving from a booking should not ask which booking.',
        );
    }

    /**
     * Picking the wrong one is the hardest part of the form on a big event,
     * which is why it is worth preselecting at all.
     */
    public function test_the_other_professional_on_the_same_event_is_not_chosen(): void
    {
        $other = $this->booking('confirmed', $this->person('professional'), 'florist');

        $html = $this->actingAs($this->client)->get($this->link())->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<option value="' . $other->id . '"[^>]*\bselected\b/',
            $html,
        );
    }

    /**
     * A booking number in the address is not an invitation.
     *
     * It is read from the list this person may already file against, so
     * somebody else's booking cannot be preselected by typing its number.
     */
    public function test_a_strangers_booking_number_selects_nothing(): void
    {
        $stranger = $this->person('client');

        $html = $this->actingAs($stranger)
            ->get($this->link())
            ->assertSuccessful()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<option value="' . $this->booking->id . '"[^>]*\bselected\b/',
            $html,
        );
    }

    /** With no booking named, the form still works as it always did. */
    public function test_coming_in_cold_still_works(): void
    {
        $this->actingAs($this->client)
            ->get(route('disputes.create'))
            ->assertSuccessful()
            ->assertSee('Which booking is this about?', false);
    }
}
