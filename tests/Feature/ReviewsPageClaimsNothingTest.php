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
 * Three things on the client's Reviews page that were not true, found by
 * walking the page's own controls rather than waiting for them to be
 * reported.
 *
 *   "Avg Response 14m"   typed in. The same figure for every client, on a
 *                        page about reviews, beside four figures counted
 *                        from the client's own reviews.
 *   "Verified Payout"    printed with a tick on every card, whatever had
 *                        happened. No payout has been made through this
 *                        platform at all.
 *   the kebab            a button with a style rule, no menu and no handler
 *                        anywhere: it did nothing when clicked.
 */
class ReviewsPageClaimsNothingTest extends TestCase
{
    use RefreshDatabase;

    private function pageWithAReview(): string
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
            'title' => 'Garden Reception', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'completed', 'is_published' => true, 'starts_at' => now()->subDays(20),
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
            'created_by' => $client->id, 'status' => 'completed', 'price' => 700,
        ]);

        Review::create([
            'reviewer_id' => $client->id, 'reviewee_id' => $pro->id, 'booking_id' => $booking->id,
            'rating' => 5, 'title' => 'Outstanding work',
            'comment' => 'Everything ran exactly as agreed on the day.', 'is_hidden' => false,
        ]);

        return $this->actingAs($client)->get('/client/reviews')->assertOk()->getContent();
    }

    public function test_it_does_not_print_a_reply_time_nobody_measured(): void
    {
        $html = $this->pageWithAReview();

        $this->assertStringNotContainsString('>14m<', $html);
        $this->assertStringNotContainsString('During event week', $html);
    }

    public function test_it_does_not_claim_a_payout_that_has_not_happened(): void
    {
        $html = $this->pageWithAReview();

        $this->assertStringNotContainsString('Verified Payout', $html);
    }

    public function test_the_card_has_no_button_that_does_nothing(): void
    {
        $html = $this->pageWithAReview();

        $this->assertStringNotContainsString('rv-rc-kebab', $html);
    }

    /** The review itself is still shown, and still says where it came from. */
    public function test_the_review_is_still_there(): void
    {
        $html = $this->pageWithAReview();

        $this->assertStringContainsString('Garden Reception', $html);
        $this->assertStringContainsString('From a completed booking', $html);
    }
}
