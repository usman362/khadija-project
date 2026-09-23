<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ali, 24 September: the Top Rated badge was on the search card and missing
 * from the profile that card opens.
 *
 * Two rules had been written for one badge. The card asked for a single
 * review averaging 4.5; the profile asked for five reviews AND all three
 * documents verified. A professional with four five-star reviews satisfied
 * one and not the other, so the badge appeared and then vanished on the way
 * from the card to the page.
 */
class TopRatedAgreesEverywhereTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();
    }

    private function professional(int $reviews, int $rating = 5): User
    {
        $pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Saffron Table']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Silver Spring',
        ]);

        for ($i = 0; $i < $reviews; $i++) {
            $reviewer = User::factory()->create(['primary_role' => 'client']);

            $event = \App\Models\Event::create([
                'title'      => 'Reception',
                'client_id'  => $reviewer->id,
                'created_by' => $reviewer->id,
                'status'     => 'completed',
                'starts_at'  => now()->subDays(30 + $i),
            ]);

            // A review belongs to the booking it is about.
            $booking = \App\Models\Booking::create([
                'event_id'    => $event->id,
                'client_id'   => $reviewer->id,
                'supplier_id' => $pro->id,
                'created_by'  => $reviewer->id,
                'status'      => 'completed',
                'price'       => 500,
            ]);

            Review::create([
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $pro->id,
                'booking_id'  => $booking->id,
                'rating'      => $rating,
                'title'       => 'Outstanding work',
                'comment'     => 'Everything ran exactly as agreed on the day.',
                'is_hidden'   => false,
            ]);
        }

        return $pro->fresh();
    }

    public function test_one_review_is_not_a_rating(): void
    {
        $this->assertFalse($this->professional(1)->isTopRated());
        $this->assertFalse($this->professional(4)->isTopRated());
    }

    public function test_enough_reviews_at_a_high_average_is(): void
    {
        $this->assertTrue($this->professional(5)->isTopRated());
    }

    public function test_paperwork_does_not_decide_a_rating_badge(): void
    {
        // No document of any kind, and the reviews still speak for themselves.
        $pro = $this->professional(6);

        $this->assertSame([], $pro->profile->verifiedBadges());
        $this->assertTrue($pro->isTopRated());
    }

    public function test_the_card_and_the_profile_say_the_same_thing(): void
    {
        $top = $this->professional(6);

        $profile = $this->actingAs($this->client)
            ->get(route('public.professional.show', $top->id))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Top Rated', $profile,
            'The profile leaves out a badge the search results show.');

        $browse = $this->actingAs($this->client)->get(route('public.browse'));

        if ($browse->getStatusCode() === 200 && str_contains($browse->getContent(), 'Saffron Table')) {
            $this->assertStringContainsString('Top Rated', $browse->getContent());
        }
    }
}
