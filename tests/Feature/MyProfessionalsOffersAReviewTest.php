<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah, 30 September: "there should be button to add review, then that
 * review will appear on Reviews page."
 *
 * It is offered on the professional's card, and only where it can honestly be
 * offered: a review belongs to a booking that finished, not to a person, and
 * there is one review per booking. So the button follows the work. No finished
 * job, no button; already reviewed, no button.
 */
class MyProfessionalsOffersAReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

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

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $this->pro->assignRole('professional');
    }

    private function booking(string $status): Booking
    {
        $event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->subDays(10),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->pro->id,
            'status'      => $status,
            'price'       => 1200,
        ]);
    }

    private function page(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->get(route('client.saved-professionals.index'));
    }

    /** Work that finished can be reviewed, from the card. */
    public function test_a_finished_booking_is_offered_a_review(): void
    {
        $booking = $this->booking('completed');

        $this->page()
            ->assertOk()
            ->assertSee('Write a review')
            ->assertSee(route('client.reviews.store', $booking), false);
    }

    /** Work still to come is not. */
    public function test_an_unfinished_booking_is_not(): void
    {
        $this->booking('confirmed');

        $this->page()->assertOk()->assertDontSee('Write a review');
    }

    /** And it is offered once: a booking takes one review. */
    public function test_it_is_not_offered_twice_for_the_same_booking(): void
    {
        $booking = $this->booking('completed');

        Review::create([
            'reviewer_id' => $this->client->id,
            'reviewee_id' => $this->pro->id,
            'booking_id'  => $booking->id,
            'rating'      => 5,
            'comment'     => 'Faultless.',
        ]);

        $this->page()->assertOk()->assertDontSee('Write a review');
    }

    /** What is posted from the card lands on the Reviews page. */
    public function test_the_review_posted_there_shows_on_the_reviews_page(): void
    {
        $booking = $this->booking('completed');

        $this->actingAs($this->client)
            ->post(route('client.reviews.store', $booking), [
                'rating'  => 5,
                'comment' => 'They arrived early and set up without being asked.',
            ])
            ->assertRedirect();

        $this->actingAs($this->client)
            ->get(route('client.reviews.index'))
            ->assertOk()
            ->assertSee('They arrived early and set up without being asked.');
    }
}
