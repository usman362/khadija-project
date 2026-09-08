<?php

namespace Tests\Feature;

use App\Models\{Booking, Event, Review, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every option in Power Filters does what it says.
 *
 * Reported as "kuch bhi sahi nahi chal raha" — and two of them genuinely did
 * nothing usable:
 *
 *  · Rating & Reviews returned an empty list whatever you picked. The filter
 *    was a HAVING on a withAvg alias, which is a per-row sub-select and not a
 *    grouped aggregate, so the database rejected it outright; rewritten as a
 *    comparison it still failed, because a float binding arrives as text and
 *    5 >= '4.5' is false when a number is compared with a string.
 *
 *  · Near ZIP had it exactly backwards: a real ZIP emptied the page while a
 *    ZIP that could not be placed returned everybody — because a professional
 *    with no coordinates on file was dropped rather than kept.
 *
 * Each case here asserts the filter both keeps what it should and drops what
 * it should, since a filter that returns everything passes a "not empty" test
 * just as happily as one that works.
 */
class BrowseFiltersActuallyFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->viewer = User::factory()->create(['name' => 'The Viewer']);
        $this->viewer->assignRole('client');
        $this->viewer->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $this->viewer = $this->viewer->fresh();
    }

    private function pro(string $name, array $profile = []): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update(array_merge(
            ['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore'],
            $profile,
        ));

        return $u->fresh();
    }

    private function review(User $of, int $rating): void
    {
        $event = Event::create([
            'title' => 'Reviewed job', 'client_id' => $this->viewer->id,
            'created_by' => $this->viewer->id, 'status' => 'completed',
            'starts_at' => now()->subMonth(),
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->viewer->id,
            'supplier_id' => $of->id, 'created_by' => $this->viewer->id,
            'status' => 'completed', 'price' => 400,
        ]);

        Review::create([
            'booking_id' => $booking->id, 'reviewer_id' => $this->viewer->id,
            'reviewee_id' => $of->id, 'rating' => $rating,
            'comment' => 'ok', 'is_hidden' => false,
        ]);
    }

    /** @return array<int, string> names on the page, in the order they appear */
    private function listing(array $query): array
    {
        $html = $this->actingAs($this->viewer)
            ->get(route('public.browse', $query))
            ->assertSuccessful()
            ->getContent();

        $found = [];
        foreach (User::pluck('name', 'id') as $name) {
            $at = strpos($html, (string) $name);
            if ($at !== false) {
                $found[$name] = $at;
            }
        }
        unset($found[$this->viewer->name]);
        asort($found);

        return array_keys($found);
    }

    public function test_rating_keeps_the_well_reviewed_and_drops_the_rest(): void
    {
        $sam = $this->pro('Star Sam');
        $mo  = $this->pro('Meh Mo');
        $this->pro('No Reviews Nia');

        foreach ([5, 5, 5] as $r) {
            $this->review($sam, $r);
        }
        foreach ([3, 3] as $r) {
            $this->review($mo, $r);
        }

        $this->assertSame(['Star Sam'], $this->listing(['rating_min' => 5]));
        $this->assertSame(['Star Sam'], $this->listing(['rating_min' => 4.5]));

        // A professional with no reviews has no average, and "4.5 and up" is
        // a claim about reviews they do not have.
        $this->assertNotContains('No Reviews Nia', $this->listing(['rating_min' => 4.5]));

        $any = $this->listing(['rating_min' => 0]);
        $this->assertContains('Meh Mo', $any);
        $this->assertContains('No Reviews Nia', $any);
    }

    public function test_a_real_zip_does_not_empty_the_page(): void
    {
        $this->pro('Placeless Pia');

        $near = $this->listing(['zip' => '21201']);

        $this->assertContains('Placeless Pia', $near,
            'A professional with no coordinates was dropped, so a real ZIP returned nothing.');
    }

    public function test_a_zip_still_excludes_someone_too_far_to_travel(): void
    {
        $this->pro('Faraway Fay', [
            // On the other side of the country, willing to travel 10 miles.
            'origin_lat' => 37.7749, 'origin_lng' => -122.4194,
            'origin_precision' => \App\Domain\Geolocation\LocationPrecision::EXACT,
            'travel_radius_miles' => 10,
        ]);
        $this->pro('Placeless Pia');

        $near = $this->listing(['zip' => '21201']);

        $this->assertNotContains('Faraway Fay', $near);
        $this->assertContains('Placeless Pia', $near);
    }

    public function test_rate_insurance_and_availability_each_narrow(): void
    {
        $this->pro('Cheap Carl', ['hourly_rate' => 50, 'availability' => 'available']);
        $this->pro('Pricey Pat', ['hourly_rate' => 200, 'availability' => 'busy']);
        $this->pro('Insured Ivy', [
            'hourly_rate' => 75, 'availability' => 'busy',
            'liability_insurance_doc' => 'l.pdf',
            'liability_insurance_verified_at' => now(),
            'liability_insurance_expires_on' => now()->addYear(),
        ]);

        $cheap = $this->listing(['rate_max' => 75]);
        $this->assertContains('Cheap Carl', $cheap);
        $this->assertNotContains('Pricey Pat', $cheap);

        $this->assertSame(['Insured Ivy'], $this->listing(['insured' => 1]));
        $this->assertSame(['Cheap Carl'], $this->listing(['available' => 1]));
    }

    public function test_verified_means_the_documents_are_there_too(): void
    {
        $this->pro('Claims Cal', [
            // Approved, but nothing was ever uploaded.
            'trade_license_verified_at' => now(),
            'workers_comp_verified_at' => now(),
            'liability_insurance_verified_at' => now(),
        ]);
        $this->pro('Verified Vic', [
            'trade_license_doc' => 't.pdf', 'trade_license_verified_at' => now(),
            'workers_comp_doc' => 'w.pdf', 'workers_comp_verified_at' => now(),
            'liability_insurance_doc' => 'l.pdf',
            'liability_insurance_verified_at' => now(),
            'liability_insurance_expires_on' => now()->addYear(),
        ]);

        $this->assertSame(['Verified Vic'], $this->listing(['verified' => 1]));
    }

    public function test_newest_puts_the_newest_first(): void
    {
        $this->pro('First Fred');
        $this->pro('Second Sue');
        $this->pro('Last Lou');

        $order = $this->listing(['sort' => 'newest']);

        $this->assertSame('Last Lou', $order[0],
            'Accounts made in the same second came back in whatever order the database chose.');
    }

    public function test_rating_sort_puts_the_best_first(): void
    {
        $sam = $this->pro('Star Sam');
        $mo  = $this->pro('Meh Mo');
        foreach ([5, 5] as $r) {
            $this->review($sam, $r);
        }
        $this->review($mo, 3);

        $order = $this->listing(['sort' => 'rating']);

        $this->assertSame('Star Sam', $order[0]);
        $this->assertLessThan(array_search('Meh Mo', $order, true) + 1, 1 + array_search('Star Sam', $order, true) - 1);
    }
}
