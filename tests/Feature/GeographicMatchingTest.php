<?php

namespace Tests\Feature;

use App\Domain\Geolocation\Geocoder;
use App\Domain\Geolocation\LocationPrecision;
use App\Domain\Geolocation\ZipCentroidTable;
use App\Models\Event;
use App\Models\User;
use App\Support\DirectoryEligibility;
use App\Support\Haversine;
use App\Support\RadiusMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Geographic matching V1 — geodesic radius inside R38, fail-closed geocode.
 */
class GeographicMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role, string $state = 'MD', string $city = 'Baltimore'): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo(['dashboard.view', 'bookings.view_any', 'bookings.update', 'events.create']);
        $user->getOrCreateProfile()->update([
            'country'             => 'US',
            'state'               => $state,
            'city'                => $city,
            'service_area_status' => 'supported',
        ]);

        return $user->fresh();
    }

    private function placeOrigin(User $pro, string $zip, int $radius, ?string $precision = LocationPrecision::ZIP): User
    {
        $row = ZipCentroidTable::find($zip);
        $this->assertNotNull($row, $zip.' missing from centroid table');

        $pro->profile->update([
            'origin_lat'           => $row['lat'],
            'origin_lng'           => $row['lng'],
            'origin_precision'     => $precision,
            'travel_radius_miles'  => $radius,
            'service_origin_zip'   => $zip,
            'service_origin_state' => $pro->profile->state,
            'service_origin_city'  => $row['city'] ?? $pro->profile->city,
        ]);

        return $pro->fresh();
    }

    private function gig(User $client, string $zip, string $state = 'MD'): Event
    {
        $row = ZipCentroidTable::find($zip);

        return Event::create([
            'title'               => 'Garden party',
            'created_by'          => $client->id,
            'client_id'           => $client->id,
            'is_published'        => true,
            'status'              => 'published',
            'state'               => $state,
            'location'            => $zip,
            'location_zip'        => $zip,
            'location_lat'        => $row['lat'],
            'location_lng'        => $row['lng'],
            'location_precision'  => LocationPrecision::ZIP,
            'starts_at'           => now()->addMonth(),
        ]);
    }

    public function test_zip_fallback_uses_the_centroid_and_not_a_guess(): void
    {
        $placed = app(Geocoder::class)->fromZip('21201');

        $this->assertTrue($placed->isMatchable());
        $this->assertSame(LocationPrecision::ZIP, $placed->precision);
        $this->assertEqualsWithDelta(39.2904, $placed->lat, 0.001);
    }

    public function test_a_broad_zip_is_unresolved_not_a_silent_point(): void
    {
        $placed = app(Geocoder::class)->fromZip('26807');

        $this->assertFalse($placed->isMatchable());
        $this->assertSame(LocationPrecision::UNRESOLVED, $placed->precision);
        $this->assertNull($placed->lat);
        $this->assertStringContainsString('too large', strtolower($placed->message));
    }

    public function test_an_unknown_zip_is_unresolved(): void
    {
        $placed = app(Geocoder::class)->fromZip('00000');

        $this->assertFalse($placed->isMatchable());
        $this->assertNull($placed->lat);
    }

    public function test_radius_matching_is_geodesic_and_same_state(): void
    {
        $client = $this->user('client', 'MD');
        $near   = $this->placeOrigin($this->user('professional', 'MD'), '21201', 10);
        $far    = $this->placeOrigin($this->user('professional', 'MD', 'Frederick'), '22201', 10);
        $other  = $this->placeOrigin($this->user('professional', 'VA', 'Arlington'), '22201', 200);

        $event = $this->gig($client, '21202');

        $this->assertTrue(RadiusMatching::allows($near, $event));
        $this->assertFalse(RadiusMatching::allows($far, $event), 'Arlington is more than 10 miles from Baltimore');
        $this->assertFalse(RadiusMatching::allows($other, $event), 'R38 still wins over a huge radius');
    }

    public function test_an_unresolved_event_never_matches(): void
    {
        $client = $this->user('client');
        $pro    = $this->placeOrigin($this->user('professional'), '21201', 40);

        $event = Event::create([
            'title'              => 'Mystery venue',
            'created_by'         => $client->id,
            'client_id'          => $client->id,
            'is_published'       => true,
            'status'             => 'published',
            'state'              => 'MD',
            'location'           => 'somewhere nearby',
            'location_precision' => LocationPrecision::UNRESOLVED,
            'starts_at'          => now()->addMonth(),
        ]);

        $this->assertTrue($event->locationPlacementFailed());
        $this->assertFalse(RadiusMatching::allows($pro, $event));
    }

    public function test_saving_a_known_zip_on_the_origin_places_it(): void
    {
        $pro = $this->user('professional');
        $pro->profile->update([
            'service_origin_zip'  => '21201',
            'travel_radius_miles' => 25,
        ]);

        $pro = $pro->fresh();
        $this->assertSame(LocationPrecision::ZIP, $pro->profile->origin_precision);
        $this->assertNotNull($pro->profile->origin_lat);
    }

    public function test_saving_a_broad_origin_zip_does_not_invent_coordinates(): void
    {
        $pro = $this->user('professional', 'WV', 'Franklin');
        $pro->profile->update([
            'service_origin_zip'  => '26807',
            'travel_radius_miles' => 15,
        ]);

        $pro = $pro->fresh();
        $this->assertSame(LocationPrecision::UNRESOLVED, $pro->profile->origin_precision);
        $this->assertNull($pro->profile->origin_lat);
    }

    public function test_browse_does_not_call_an_unplaceable_zip_an_empty_market(): void
    {
        $client = $this->user('client');

        $this->actingAs($client)
            ->get(route('public.browse', ['zip' => '00000']))
            ->assertOk()
            ->assertSee('We could not place this location')
            ->assertDontSee('No professionals available in this area for this request');
    }

    public function test_a_login_locked_professional_does_not_count_toward_the_city_directory(): void
    {
        $a = $this->user('professional');
        $b = $this->user('professional');
        $b->forceFill(['login_locked_at' => now()])->save();

        $this->assertTrue(DirectoryEligibility::qualifies($a->fresh()));
        $this->assertFalse(DirectoryEligibility::qualifies($b->fresh()));

        $cities = collect($this->get(route('landing'))->assertOk()->viewData('popularCities'));
        $this->assertTrue($cities->isEmpty(), 'one eligible Baltimore pro is below the threshold of 2');
    }

    public function test_haversine_baltimore_to_dc_is_about_thirty_five_miles(): void
    {
        $b = ZipCentroidTable::find('21201');
        $d = ZipCentroidTable::find('20001');

        $miles = Haversine::miles($b['lat'], $b['lng'], $d['lat'], $d['lng']);
        $this->assertEqualsWithDelta(35, $miles, 8);
    }

    public function test_direct_offer_is_not_dropped_by_radius(): void
    {
        $client = $this->user('client');
        $pro    = $this->placeOrigin($this->user('professional'), '21201', 1);
        $event  = $this->gig($client, '22201');
        $event->update(['source' => 'direct_offer', 'supplier_id' => $pro->id]);

        $this->assertTrue(RadiusMatching::allows($pro->fresh(), $event->fresh()));
    }
}
