<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 92, 100 and 147 — three panels whose numbers described
 * something other than what sat beside them.
 *
 *   Bookings: five buckets over four statuses, with `confirmed` split by event
 *   date into Upcoming and In Progress. A confirmed booking whose event had
 *   already finished belonged to neither, so it was counted in no tile at all
 *   — "4 All Bookings" above tiles summing to 2.
 *
 *   Reviews: "Secure Payment 0 (67%)". The count came from the review total,
 *   the percentage was a literal in the template, and nought of anything is
 *   nought per cent. Below it, four lines of derived-looking insight on an
 *   account with no reviews to derive anything from.
 *
 *   Browse: a city list capped at six rows, summing to 7 beside a header
 *   reading "Found: 13 Pros".
 */
class PanelsThatAddUpTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
    }

    private function account(string $role, array $profile = []): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(array_merge(
            ['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore'],
            $profile,
        ));

        return $user->fresh();
    }

    /* ── Row 92: every booking lands in exactly one tile ────── */

    private function booking(string $status, ?\Carbon\Carbon $starts, ?\Carbon\Carbon $ends = null): Booking
    {
        $event = Event::create([
            'title' => 'A booking', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => $starts, 'ends_at' => $ends,
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->account('professional')->id,
            'created_by' => $this->client->id, 'status' => $status, 'price' => 500,
        ]);
    }

    private function bookingCounts(): array
    {
        return $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertOk()
            ->viewData('counts');
    }

    /**
     * The exact reported shape: four bookings, two of them confirmed on
     * events that have already been and gone.
     */
    public function test_a_confirmed_booking_whose_event_has_passed_is_still_counted(): void
    {
        $this->booking('confirmed', now()->subDays(10), now()->subDays(10)->addHours(6));
        $this->booking('confirmed', now()->subDays(3),  now()->subDays(3)->addHours(4));
        $this->booking('requested', now()->addDays(20));
        $this->booking('requested', now()->addDays(25));

        $counts = $this->bookingCounts();

        $this->assertSame(4, $counts['all']);
        $this->assertSame(2, $counts['awaiting_completion'], 'the two finished-but-confirmed bookings have a home');
        $this->assertSame(2, $counts['pending']);
    }

    public function test_the_buckets_partition_every_booking(): void
    {
        $this->booking('confirmed', now()->addDays(5));                                  // upcoming
        $this->booking('confirmed', now()->subHour(), now()->addHour());                 // in progress
        $this->booking('confirmed', now()->subMonth(), now()->subMonth()->addHours(3));  // awaiting completion
        $this->booking('confirmed', null);                                               // no dates at all
        $this->booking('requested', now()->addDays(9));
        $this->booking('completed', now()->subDays(40));
        $this->booking('cancelled', now()->addDays(3));

        $counts = $this->bookingCounts();

        $buckets = $counts['upcoming'] + $counts['in_progress'] + $counts['awaiting_completion']
                 + $counts['pending'] + $counts['completed'] + $counts['cancelled'];

        $this->assertSame(7, $counts['all']);
        $this->assertSame($counts['all'], $buckets, 'every booking falls in exactly one bucket');
    }

    /** A booking with no event dates cannot be upcoming or running, so it has to land somewhere. */
    public function test_a_confirmed_booking_with_no_dates_is_not_lost(): void
    {
        $this->booking('confirmed', null);

        $this->assertSame(1, $this->bookingCounts()['awaiting_completion']);
    }

    /** Opening a tile shows what its number promised. */
    public function test_the_tile_opens_the_bookings_it_counted(): void
    {
        $this->booking('confirmed', now()->subDays(10), now()->subDays(10)->addHours(6));
        $this->booking('confirmed', now()->addDays(10));

        $listed = $this->actingAs($this->client)
            ->get(route('client.bookings.index', ['tab' => 'awaiting_completion']))
            ->viewData('bookings');

        $this->assertSame(1, $listed->total());
    }

    /* ── Row 100: the reviews right rail ────────────────────── */

    public function test_an_account_with_no_reviews_shows_no_percentages(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.reviews.index'))->assertOk();

        $this->assertSame(0, $page->viewData('trust')['denominator']);
        $this->assertSame([], $page->viewData('trust')['checks']);

        // The four hardcoded lines, gone.
        $page->assertDontSee('(67%)', false);
        $page->assertDontSee('(33%)', false);
        $page->assertDontSee('(83%)', false);
        $page->assertSee('Once you review a professional', false);
    }

    public function test_no_reviews_means_no_review_highlights(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.reviews.index'));

        $this->assertSame([], $page->viewData('highlights')['themes']);
        $page->assertDontSee('Timeliness &amp; Reliability', false);
        $page->assertDontSee('Budget Management', false);
        $page->assertSee('Themes appear here once your reviews mention them', false);
    }

    private function review(User $of, int $rating, string $comment): Review
    {
        $event = Event::create([
            'title' => 'Reviewed job', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'starts_at' => now()->subMonth(),
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $of->id,
            'created_by' => $this->client->id, 'status' => 'completed', 'price' => 400,
        ]);

        return Review::create([
            'booking_id'  => $booking->id,
            'reviewer_id' => $this->client->id,
            'reviewee_id' => $of->id,
            'rating'      => $rating,
            'comment'     => $comment,
        ]);
    }

    /** Each percentage is computed from the count printed beside it. */
    public function test_each_verification_percentage_matches_its_own_count(): void
    {
        $verified = $this->account('professional');
        $verified->profile->update(['address_verified_at' => now()]);

        $unverified = $this->account('professional');
        $unverified->profile->update(['address_verified_at' => null]);

        $this->review($verified, 5, 'Great.');
        $this->review($unverified, 4, 'Good.');

        $trust = $this->actingAs($this->client)->get(route('client.reviews.index'))->viewData('trust');

        $this->assertSame(2, $trust['denominator']);

        foreach ($trust['checks'] as $check) {
            $this->assertSame(
                (int) round(($check['count'] / $trust['denominator']) * 100),
                $check['pct'],
                "{$check['label']} prints a percentage that does not match its count",
            );
        }

        $address = collect($trust['checks'])->firstWhere('label', 'Address verified');
        $this->assertSame(1, $address['count']);
        $this->assertSame(50, $address['pct']);
    }

    /** Two reviews of the same professional are one verified professional. */
    public function test_the_denominator_counts_professionals_not_reviews(): void
    {
        $pro = $this->account('professional');
        $this->review($pro, 5, 'First time was great.');
        $this->review($pro, 4, 'Second time was good.');

        $this->assertSame(
            1,
            $this->actingAs($this->client)->get(route('client.reviews.index'))->viewData('trust')['denominator'],
        );
    }

    /** Highlights come out of the words the client actually wrote. */
    public function test_highlights_are_counted_from_the_review_comments(): void
    {
        $this->review($this->account('professional'), 5, 'Excellent communication throughout, and they arrived on time.');
        $this->review($this->account('professional'), 5, 'Communication was clear from the first message.');
        $this->review($this->account('professional'), 2, 'The price kept creeping up beyond the budget we agreed.');

        $highlights = $this->actingAs($this->client)->get(route('client.reviews.index'))->viewData('highlights');

        $themes = collect($highlights['themes'])->pluck('mentions', 'theme');

        $this->assertSame(2, $themes['Communication'], 'two comments mention it');
        $this->assertSame('Communication', $highlights['strength']);
        $this->assertSame('Value', $highlights['watch'], 'the theme raised in the low rating');
    }

    /* ── Row 147: the browse city sidebar ───────────────────── */

    public function test_the_city_sidebar_adds_up_to_the_found_count(): void
    {
        // Eight cities, so the six-row list cannot show them all.
        $cities = ['Baltimore', 'Baltimore', 'Annapolis', 'Rockville', 'Bethesda',
                   'Frederick', 'Columbia', 'Towson', 'Bowie'];

        foreach ($cities as $city) {
            $this->account('professional', ['city' => $city]);
        }

        // And one with no city on file at all — the other way a pro used to
        // disappear from this panel.
        $this->account('professional', ['city' => null]);

        $page = $this->actingAs($this->client)->get(route('public.browse'))->assertOk();

        $counts = $page->viewData('locationCounts');
        $other  = $page->viewData('locationOther');
        $found  = $page->viewData('pros')->total();

        $this->assertLessThanOrEqual(6, $counts->count(), 'still a short list');
        $this->assertGreaterThan(0, $other, 'the remainder is not zero on this data');
        $this->assertSame($found, (int) $counts->sum() + $other, 'the column must add up to the found total');
    }

    /** With everything on show there is no remainder row to print. */
    public function test_no_remainder_row_when_the_cities_already_account_for_everyone(): void
    {
        $this->account('professional', ['city' => 'Baltimore']);
        $this->account('professional', ['city' => 'Annapolis']);

        $page = $this->actingAs($this->client)->get(route('public.browse'));

        $this->assertSame(0, $page->viewData('locationOther'));
        $page->assertDontSee('Other cities &amp; not stated', false);
    }
}
