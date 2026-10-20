<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * All three request forms ask where the event is, and all three check it.
 *
 * Sir Peter, 20 October, looking at the bidding request's Event Location card:
 * "should this also be included in the ER and DR webpages? so that the clients
 * are providing checkable locations for the professional/influencer user to
 * make sure that's not a fake post?"
 *
 * Two things were actually wrong when that was asked. The emergency form took
 * one box labelled "Venue or city", so a street with no town went through and
 * every step after it had to guess which town that was -- the fault he reported
 * on 27 September. The direct form was worse: its "Venue / Location" box had no
 * address check on it at all, so the fake address he typed in October passed on
 * that form while the bidding request refused it.
 *
 * These tests hold the behaviour rather than the markup: what a form accepts,
 * what it refuses, and what it stores.
 */
class EveryRequestAsksForACheckableAddressTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = $this->person('client');
        $this->pro    = $this->person('professional');
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

    /** A direct request that is complete apart from the address. */
    private function directOffer(array $address): array
    {
        return $address + [
            'professional_id'   => $this->pro->id,
            'service_single'    => 'Wedding Photography',
            'event_name'        => 'Rivera Wedding',
            'event_date'        => now()->addDays(40)->format('Y-m-d'),
            'organization_type' => 'individual',
            'description'       => 'Full day coverage, two shooters, album included.',
            'budget_min'        => 1500,
            'fee_agreed'        => 1,
        ];
    }

    // ── What the forms ask ───────────────────────────────────

    /** @return array<string, string> */
    public static function forms(): array
    {
        return [
            'emergency request' => ['client.esr.create'],
            'direct request'    => ['client.direct-offers.create'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forms')]
    public function test_the_form_asks_for_a_street_a_city_and_a_venue(string $route): void
    {
        $html = $this->actingAs($this->client)->get(route($route))->assertSuccessful()->getContent();

        foreach (['location', 'city', 'venue'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "No {$field} field.");
        }
    }

    /**
     * The state is shown, not asked.
     *
     * A state field stood on the bidding request once and was taken out because
     * choosing one changed nothing: every request is matched on the client's
     * own state, so a client who picked another would have posted a request no
     * professional could answer.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('forms')]
    public function test_the_state_is_shown_and_cannot_be_chosen(string $route): void
    {
        $html = $this->actingAs($this->client)->get(route($route))->assertSuccessful()->getContent();

        $this->assertStringContainsString('Maryland', $html, 'The client is not told which state they are served in.');
        $this->assertStringNotContainsString('name="state"', $html, 'The state is offered as a choice.');
    }

    // ── The direct request, which checked nothing ────────────

    /**
     * The form had no address check on it whatsoever before this.
     *
     * A PO Box is the clearest case: nobody sets up a wedding at one, and the
     * bidding request has refused them all along while this form took them.
     * What the free check can and cannot catch is set out on the rule itself --
     * it will never know that a well-formed address does not exist, and that
     * needs the paid lookup which is still waiting on an API account.
     */
    public function test_the_direct_request_refuses_an_address_nobody_can_turn_up_at(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer([
                'location' => 'PO Box 1182',
                'city'     => 'Baltimore',
            ]))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('events', ['title' => 'Rivera Wedding']);
    }

    /** And keyboard mash, which is the other thing a free check can see. */
    public function test_the_direct_request_refuses_mashed_input(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer([
                'location' => 'aaaaaaaa',
                'city'     => 'Baltimore',
            ]))
            ->assertSessionHasErrors('location');
    }

    public function test_the_direct_request_refuses_a_street_with_no_town(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer([
                'location' => '682 Kirkcaldy Way',
            ]))
            ->assertSessionHasErrors('city');

        $this->assertDatabaseMissing('events', ['title' => 'Rivera Wedding']);
    }

    /** A venue with no street is still fine: not everyone has settled on one. */
    public function test_the_direct_request_still_takes_a_venue_on_its_own(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer([
                'venue' => 'Oregon Ridge Park',
            ]))
            ->assertSessionHasNoErrors();

        $event = Event::where('title', 'Rivera Wedding')->firstOrFail();

        $this->assertSame('Oregon Ridge Park', $event->venue);
        $this->assertNull($event->location);
    }

    /** And a full address is kept in three pieces rather than one blob. */
    public function test_the_direct_request_stores_the_address_in_its_parts(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer([
                'location' => '1234 Garden Way',
                'city'     => 'Baltimore',
                'venue'    => 'Oregon Ridge Park',
            ]))
            ->assertSessionHasNoErrors();

        $event = Event::where('title', 'Rivera Wedding')->firstOrFail();

        $this->assertSame('1234 Garden Way', $event->location);
        $this->assertSame('Baltimore', $event->city);
        $this->assertSame('Oregon Ridge Park', $event->venue);
        $this->assertSame('MD', $event->state);
    }

    // ── The emergency request ────────────────────────────────

    /** An emergency request that is complete apart from the address. */
    private function emergency(array $address): array
    {
        $service = \App\Models\Category::create([
            'name' => 'Emergency Catering', 'slug' => 'emergency-catering-addr',
            'kind' => \App\Models\Category::SERVICE, 'is_active' => true,
        ]);

        return $address + [
            'scope'             => 'single',
            'reason'            => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
            'organization_type' => 'individual',
            'event_name'        => 'Rivera Rush',
            'needed_by'         => now()->addDays(3)->format('Y-m-d\TH:i'),
            'description'       => 'The original supplier cancelled and we need someone this week.',
            'budget_min'        => 900,
            'services'          => [$service->id],
            'fee_agreed'        => 1,
        ];
    }

    public function test_the_emergency_request_refuses_a_street_with_no_town(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->emergency(['location' => '682 Kirkcaldy Way']))
            ->assertSessionHasErrors('city');

        $this->assertDatabaseMissing('events', ['title' => 'Rivera Rush']);
    }

    public function test_the_emergency_request_stores_the_address_in_its_parts(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->emergency([
                'location' => '1234 Garden Way',
                'city'     => 'Baltimore',
                'venue'    => 'Oregon Ridge Park',
            ]))
            ->assertSessionHasNoErrors();

        $event = Event::where('title', 'Rivera Rush')->firstOrFail();

        $this->assertSame('1234 Garden Way', $event->location);
        $this->assertSame('Baltimore', $event->city);
        $this->assertSame('Oregon Ridge Park', $event->venue);
    }

    /**
     * A rush request can still be posted with no address at all.
     *
     * The point of the check is that what you give is checkable, not that an
     * emergency is held up over a postcode.
     */
    public function test_an_emergency_can_still_be_posted_without_an_address(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->emergency([]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', ['title' => 'Rivera Rush']);
    }

    // ── One definition, three forms ──────────────────────────

    /**
     * The sentence is the same wherever a street arrives with no town.
     *
     * It was written out by hand in the bidding controller. The ER and DR would
     * each have grown their own, which is how three forms end up explaining the
     * same rule three ways.
     */
    public function test_the_three_forms_explain_it_in_the_same_words(): void
    {
        $sentence = \App\Domain\Requests\EventAddress::cityMessage();

        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->directOffer(['location' => '682 Kirkcaldy Way']))
            ->assertSessionHasErrors(['city' => $sentence]);

        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->emergency(['location' => '682 Kirkcaldy Way']))
            ->assertSessionHasErrors(['city' => $sentence]);
    }
}
