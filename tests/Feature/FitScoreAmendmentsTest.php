<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R61's two amendments to the locked Fit Score, 2026-08-07.
 *
 * Both come from one observation: under R38 every gig a professional can see
 * is already in their state, so the old "in-area 20" scored a flat 20 on every
 * visible row. Twenty of a hundred points that cannot separate any row from
 * any other is not a weak signal — it is a constant, and the percentage on the
 * card carried a permanent +20 floor.
 *
 *   Category 40 → GRADED: 40 exact service · 20 same category, different
 *   service · 0 otherwise. This also settles the binary-or-partial question
 *   the original rule left open, and is what makes the feed's "related" block
 *   definable: relatedness is structural, from the R45 taxonomy, not a
 *   threshold on this score.
 *
 *   In-area 20 → PROXIMITY to the event.
 *
 * Availability 20 and rating 20 are untouched, and tier is still not an input.
 */
class FitScoreAmendmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private Category $parent;
    private Category $mine;
    private Category $sibling;
    private Category $unrelated;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Two services under one category, plus one under another — the exact
        // shape the taxonomy uses: 27 categories over 241 services.
        $this->parent  = Category::create(['name' => 'Music & Entertainment', 'slug' => 'music-ent', 'kind' => 'service_category']);
        $other         = Category::create(['name' => 'Catering & Food', 'slug' => 'catering-food', 'kind' => 'service_category']);
        $this->mine    = Category::create(['name' => 'DJ Services', 'slug' => 'dj-services', 'kind' => 'service', 'parent_id' => $this->parent->id]);
        $this->sibling = Category::create(['name' => 'Live Bands', 'slug' => 'live-bands', 'kind' => 'service', 'parent_id' => $this->parent->id]);
        $this->unrelated = Category::create(['name' => 'Buffet Catering', 'slug' => 'buffet-catering', 'kind' => 'service', 'parent_id' => $other->id]);

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro->givePermissionTo('dashboard.view');
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro->serviceCategories()->sync([$this->mine->id]);
        $this->pro = $this->pro->fresh();
    }

    /** A published gig in the pro's state, so R38 does not hide it. */
    private function gig(array $categoryIds, ?string $location = 'Baltimore, MD', ?string $when = '+1 month'): Event
    {
        $client = User::factory()->create();
        $client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $event = Event::create([
            'title'        => 'Gig ' . uniqid(),
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'is_published' => true,
            'status'       => 'published',
            'location'     => $location,
            'starts_at'    => $when ? now()->modify($when) : null,
        ]);
        $event->categories()->sync($categoryIds);

        \Illuminate\Support\Facades\DB::table('events')
            ->where('id', $event->id)->update(['created_at' => now()->subHours(2)]);

        return $event->fresh();
    }

    private function scoreFor(Event $event): int
    {
        $rows = collect($this->actingAs($this->pro)
            ->get(route('professional.bidding-board.index'))
            ->viewData('gigs'));

        return (int) $rows->firstWhere('event_id', $event->id)['match'];
    }

    public function test_a_service_the_professional_lists_scores_the_full_forty(): void
    {
        // 40 category + 20 same city + 20 free + 10 unrated = 90.
        $this->assertSame(90, $this->scoreFor($this->gig([$this->mine->id])));
    }

    public function test_a_sibling_service_scores_twenty_of_the_forty(): void
    {
        // A DJ seeing a live-band request: same category, different service.
        // 20 + 20 + 20 + 10 = 70.
        $this->assertSame(70, $this->scoreFor($this->gig([$this->sibling->id])));
    }

    public function test_a_service_under_another_category_scores_nothing_for_category(): void
    {
        // A DJ and a caterer are not related at any level. 0 + 20 + 20 + 10.
        $this->assertSame(50, $this->scoreFor($this->gig([$this->unrelated->id])));
    }

    public function test_the_three_grades_are_strictly_ordered(): void
    {
        // The property that matters more than any single number: an exact
        // match must always beat a sibling, which must always beat neither.
        $exact   = $this->scoreFor($this->gig([$this->mine->id]));
        $related = $this->scoreFor($this->gig([$this->sibling->id]));
        $neither = $this->scoreFor($this->gig([$this->unrelated->id]));

        $this->assertGreaterThan($related, $exact);
        $this->assertGreaterThan($neither, $related);
    }

    public function test_proximity_separates_two_gigs_in_the_same_state(): void
    {
        // The whole point of the amendment. Both of these are in Maryland, so
        // the old in-area component gave both a flat 20 and did no work.
        $near = $this->scoreFor($this->gig([$this->mine->id], 'Baltimore, MD'));
        $far  = $this->scoreFor($this->gig([$this->mine->id], 'Cumberland, MD'));

        $this->assertGreaterThan($far, $near);
    }

    public function test_an_unlocatable_request_sits_mid_rather_than_at_zero(): void
    {
        // Absent information is not a bad answer — the same treatment the
        // rating component already gives a professional with no reviews.
        $unknown = $this->scoreFor($this->gig([$this->mine->id], null));
        $far     = $this->scoreFor($this->gig([$this->mine->id], 'Cumberland, MD'));
        $near    = $this->scoreFor($this->gig([$this->mine->id], 'Baltimore, MD'));

        $this->assertGreaterThan($far, $unknown);
        $this->assertLessThan($near, $unknown);
    }

    public function test_a_clash_on_the_date_costs_the_availability_points(): void
    {
        $taken = $this->gig([$this->mine->id]);
        $other = $this->gig([$this->mine->id], 'Baltimore, MD');

        Booking::create([
            'event_id'    => $taken->id,
            'client_id'   => $taken->client_id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $taken->client_id,
            'status'      => 'confirmed',
            'price'       => 500,
            'currency'    => 'USD',
        ]);

        // $other is on the same date as $taken, so the pro is now double-booked.
        $this->assertSame(70, $this->scoreFor($other));
    }

    public function test_the_professionals_own_services_come_from_what_they_listed(): void
    {
        // Not from their published packages, which is where this used to read
        // them. A professional with no packages had no categories at all and
        // scored zero on 40 points regardless of what they do.
        $this->assertSame(0, \App\Models\Package::where('user_id', $this->pro->id)->count());
        $this->assertSame(90, $this->scoreFor($this->gig([$this->mine->id])));
    }

    public function test_membership_tier_is_still_not_an_input(): void
    {
        // Q7, unchanged by R61: tier affects early-access timing only.
        $before = $this->scoreFor($this->gig([$this->mine->id]));

        $plan = \App\Models\MembershipPlan::firstOrCreate(
            ['slug' => 'enterprise'],
            ['name' => 'Elite', 'price' => 59.99, 'billing_cycle' => 'monthly'],
        );
        \App\Models\UserSubscription::create([
            'user_id'            => $this->pro->id,
            'membership_plan_id' => $plan->id,
            'status'             => 'active',
            'starts_at'          => now()->subDay(),
            'expires_at'         => now()->addMonth(),
        ]);

        $this->assertSame($before, $this->scoreFor($this->gig([$this->mine->id])));
    }
}
