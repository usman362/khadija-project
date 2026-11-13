<?php

namespace Tests\Feature;

use App\Domain\Geolocation\LocationPrecision;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\OpportunityCard;
use App\Support\RadiusMatching;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three checks under each opportunity card.
 *
 * Khadijah's reference screen shows "Matches your services", "Within your
 * travel radius" and "Verified Client" as green ticks beneath every job. Each
 * is a claim about this professional and this job, so each has to come from a
 * record. The point of these tests is that none of them can quietly become a
 * tick that is always there.
 */
class EachOpportunitySaysWhyItSuitsYouTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Category $service;

    /** Baltimore, where the demo professionals work out of. */
    private const ORIGIN = [39.2904, -76.6122];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Niamh Castellane');
        $this->client = $this->person('client', 'Everett Shaw');

        $this->service = Category::create([
            'name' => 'Wedding DJs', 'slug' => 'wedding-djs-'.uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->syncWithoutDetaching([$this->service->id]);

        $this->pro->getOrCreateProfile()->forceFill([
            'origin_lat'          => self::ORIGIN[0],
            'origin_lng'          => self::ORIGIN[1],
            'origin_precision'    => LocationPrecision::ZIP,
            'travel_radius_miles' => 30,
        ])->save();

        $this->pro = User::findOrFail($this->pro->id);
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /** @param array{0: float, 1: float}|null $at */
    private function request(?array $at = null, array $over = []): Event
    {
        $event = Event::create(array_merge([
            'title'              => 'Shaw summer party',
            'client_id'          => $this->client->id,
            'created_by'         => $this->client->id,
            'status'             => 'published',
            'is_published'       => true,
            'starts_at'          => now()->addDays(30),
            'proposal_deadline'  => now()->addDays(10),
            'location'           => 'Baltimore, MD',
            'state'              => 'MD',
            'location_lat'       => $at[0] ?? null,
            'location_lng'       => $at[1] ?? null,
            'location_precision' => $at ? LocationPrecision::ZIP : LocationPrecision::UNRESOLVED,
        ], $over));

        $event->categories()->syncWithoutDetaching([$this->service->id]);

        return $event->fresh('categories');
    }

    /** A job down the road is inside a thirty mile radius. */
    public function test_a_nearby_job_is_within_the_radius(): void
    {
        // Roughly seven miles north.
        $card = OpportunityCard::for($this->request([39.39, -76.66]), $this->pro, true);

        $this->assertSame('yes', $card['travel']['state']);
        $this->assertSame('Within your travel radius', $card['travel']['text']);
        $this->assertNotNull($card['distance']);
        $this->assertStringEndsWith(' mi', $card['distance']);
    }

    /** And one two hundred miles away is not, whatever the fit score says. */
    public function test_a_distant_job_is_outside_the_radius(): void
    {
        // Roughly three degrees north, about two hundred miles.
        $card = OpportunityCard::for($this->request([42.29, -76.61]), $this->pro, true);

        $this->assertSame('no', $card['travel']['state']);
        $this->assertSame('Beyond your travel radius', $card['travel']['text']);
    }

    /**
     * An address that could not be placed says so.
     *
     * This is the one the card must never fake. Printing "0 mi" for a request
     * with no coordinates claims the job is next door, which is the single
     * answer the data definitely does not support.
     */
    public function test_an_unplaced_job_does_not_invent_a_distance(): void
    {
        $card = OpportunityCard::for($this->request(), $this->pro, true);

        $this->assertNull($card['distance']);
        $this->assertSame('unknown', $card['travel']['state']);
        $this->assertSame('Distance not known yet', $card['travel']['text']);
    }

    /** A professional who has not said where they work from is told to. */
    public function test_a_professional_with_no_origin_is_asked_for_one(): void
    {
        $this->pro->profile->forceFill([
            'origin_lat' => null, 'origin_lng' => null,
            'origin_precision' => LocationPrecision::UNRESOLVED,
        ])->save();

        $card = OpportunityCard::for($this->request([39.39, -76.66]), $this->pro->fresh(), true);

        $this->assertSame('unknown', $card['travel']['state']);
        $this->assertSame('Set where you travel from', $card['travel']['text']);
        $this->assertNull($card['distance']);
    }

    /** The distance the card prints is the distance the board measures. */
    public function test_the_card_and_the_board_measure_the_same_thing(): void
    {
        $event = $this->request([39.39, -76.66]);

        $this->assertSame(
            RadiusMatching::milesBetween($this->pro, $event),
            OpportunityCard::for($event, $this->pro, true)['travel']['miles'],
        );
    }

    /** A client nobody has worked for and who is unchecked is simply new. */
    public function test_an_unknown_client_is_called_new(): void
    {
        $card = OpportunityCard::for($this->request([39.39, -76.66]), $this->pro, true);

        $this->assertSame('new', $card['client']['state']);
        $this->assertSame('New client', $card['client']['text']);
    }

    /** Having hired this professional before outranks anything else. */
    public function test_a_returning_client_is_named_as_one(): void
    {
        $past = $this->request([39.39, -76.66], ['title' => 'Last summer']);

        Booking::create([
            'event_id' => $past->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'completed', 'price' => 1200, 'currency' => 'USD',
        ]);

        $card = OpportunityCard::for($this->request([39.39, -76.66]), $this->pro, true);

        $this->assertSame('yes', $card['client']['state']);
        $this->assertSame('You have worked for them before', $card['client']['text']);
    }

    /** A checked identity is the next best thing we can honestly say. */
    public function test_a_checked_client_is_named_as_verified(): void
    {
        $this->client->profile->forceFill(['identity_verified_at' => now()->subMonth()])->save();

        $card = OpportunityCard::for($this->request([39.39, -76.66]), $this->pro->fresh(), true);

        $this->assertSame('yes', $card['client']['state']);
        $this->assertSame('Identity verified', $card['client']['text']);
    }

    /** The services line follows the block the card is in, nothing else. */
    public function test_the_services_check_follows_the_block(): void
    {
        $event = $this->request([39.39, -76.66]);

        $this->assertSame('Matches your services', OpportunityCard::for($event, $this->pro, true)['services']['text']);
        $this->assertSame('Outside your listed services', OpportunityCard::for($event, $this->pro, false)['services']['text']);
    }

    /** The card reaches the dashboard with its checks on it. */
    public function test_the_dashboard_draws_the_checks(): void
    {
        $this->request([39.39, -76.66], ['title' => 'Shaw summer party']);

        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Shaw summer party')
            ->assertSee('Matches your services')
            ->assertSee('Within your travel radius')
            ->assertSee('New client')
            // The service it names, as a chip.
            ->assertSee('Wedding DJs');
    }
}
