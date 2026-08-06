<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * From the 2026-08-04 screenshot review: the Reviews & Reputation page
 * described a whole feature set with no paper trail behind it.
 *
 *   The Midnight Trigger  a 12:00 AM job. There is no scheduled job.
 *   The Echo Effect       the client gets a reward token by text so they book
 *                         you again. No token table, no SMS anywhere.
 *   Safe · Fair · Anonymous, encrypted
 *                         reviews store reviewer_id; they are neither.
 *   Re-Shape              adjust a posted score. There is no update route.
 *   Vanish                hide a review in a 48-hour holding tank. No such state.
 *   Peer Mediate          escalate to a mediation panel. No panel, and the
 *                         dispute module is not built.
 *
 * The anonymity claim is the one that changes behaviour: a professional writes
 * differently believing nobody can tell it was them.
 */
class ReviewsPageIsHonestTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create();
        $this->pro->assignRole('professional');
        $this->pro->givePermissionTo('dashboard.view');
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    private function completedGig(): Booking
    {
        $client = User::factory()->create();

        $event = Event::create([
            'title'      => 'Gig',
            'created_by' => $client->id,
            'client_id'  => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->subWeek(),
            'ends_at'    => now()->subWeek()->addHours(4),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $client->id,
            'status'      => 'completed',
            'price'       => 500,
            'currency'    => 'USD',
        ]);
    }

    public function test_the_page_promises_nothing_that_is_not_built(): void
    {
        $this->completedGig();

        $page = $this->actingAs($this->pro)->get(route('professional.reviews.index'));

        $page->assertSuccessful();

        foreach ([
            'Echo Effect', 'reward token', 'Midnight Trigger',
            'Anonymous', 'encrypted', 'RE-SHAPE', 'VANISH', 'PEER MEDIATE',
        ] as $claim) {
            $page->assertDontSee($claim);
        }
    }

    public function test_it_says_plainly_that_a_review_is_not_anonymous(): void
    {
        $this->completedGig();

        $this->actingAs($this->pro)
            ->get(route('professional.reviews.index'))
            ->assertSee('Your name is on it');
    }

    public function test_a_review_really_does_record_who_wrote_it(): void
    {
        // The reason the anonymity claim had to go.
        $this->assertContains('reviewer_id', \Schema::getColumnListing('reviews'));
    }

    public function test_there_is_no_way_to_change_a_review_once_posted(): void
    {
        // "Re-Shape" offered exactly this. If an update route is ever added,
        // this test should fail and the wording can come back.
        $names = collect(\Route::getRoutes())->map(fn ($r) => $r->getName())->filter();

        $this->assertFalse(
            $names->contains(fn ($n) => str_contains($n, 'reviews.update')),
            'a review cannot be edited, so the page must not offer it',
        );
    }

    public function test_the_three_real_ratings_are_still_there(): void
    {
        $this->completedGig();

        $page = $this->actingAs($this->pro)->get(route('professional.reviews.index'));

        $page->assertSee('Punctuality');
        $page->assertSee('Communication');
        $page->assertSee('Safety');
    }

    public function test_posting_a_review_still_works(): void
    {
        $booking = $this->completedGig();

        $this->actingAs($this->pro)->post(route('professional.reviews.store'), [
            'booking_id'    => $booking->id,
            'punctuality'   => 5,
            'communication' => 4,
            'safety'        => 5,
            'note'          => 'Clear plan, smooth load-in.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Review::where('reviewer_id', $this->pro->id)->count());
    }
}
