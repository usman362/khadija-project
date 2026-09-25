<?php

namespace Tests\Feature;

use App\Domain\Requests\VenueRule;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #14: a booking's venue read "social.bxlpubcrawl.com".
 *
 * A web address is not somewhere anyone can turn up to. Printing one where
 * the venue goes tells a client to drive to a domain name, and a professional
 * reading the request has nothing to act on either.
 *
 * Two ends, because the rows already stored are not going to fix themselves:
 * nothing prints a link where a place belongs, and nothing new is stored.
 */
class PlaceIsAPlaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_web_address_is_not_a_place(): void
    {
        foreach (['social.bxlpubcrawl.com', 'https://example.com/hall', 'www.venue.org', 'venue.co.uk/book'] as $link) {
            $this->assertNull(VenueRule::place($link), "\"{$link}\" was taken for a place.");
        }
    }

    /** And a real venue keeps its dots, which is why the check is narrow. */
    public function test_a_real_place_survives_it(): void
    {
        foreach ([
            "St. Mary's Hall",
            'Suite 4.2, 100 Light St, Baltimore',
            'Grand Oak Banquet Hall',
            'Baltimore, MD',
        ] as $place) {
            $this->assertSame($place, VenueRule::place($place), "\"{$place}\" was mistaken for a link.");
        }
    }

    public function test_the_bookings_page_does_not_print_a_link_as_the_venue(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $client = User::findOrFail($client->id);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $event = Event::create([
            'title' => 'Pub crawl', 'status' => 'published', 'is_published' => true,
            'client_id' => $client->id, 'created_by' => $client->id,
            'starts_at' => now()->addDays(20), 'location' => 'social.bxlpubcrawl.com',
        ]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
            'created_by' => $client->id, 'status' => 'confirmed', 'price' => 900,
        ]);

        $html = $this->actingAs($client)->get(route('client.bookings.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('social.bxlpubcrawl.com', $html);
        $this->assertStringContainsString('Not set', $html);
    }

    public function test_a_link_cannot_be_saved_as_a_location(): void
    {
        $rule = new \App\Rules\PlaceNotALink;
        $refused = null;

        $rule->validate('location', 'social.bxlpubcrawl.com', function ($message) use (&$refused) {
            $refused = $message;
        });

        $this->assertNotNull($refused, 'A web address was accepted as a location.');
        $this->assertStringContainsString('not a web address', $refused);

        // And a real one goes through untouched.
        $rule->validate('location', 'Grand Oak Banquet Hall', function () {
            $this->fail('A real venue was refused.');
        });
    }
}
