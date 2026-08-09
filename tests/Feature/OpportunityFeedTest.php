<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\OpportunityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Opportunity Feed — Rule R61, Option B, locked 2026-08-07.
 *
 * The professional's own services first, then RELATED work below, visibly
 * apart. "Related" means same Sub category, a different Sub-Sub service, read
 * off the R45 taxonomy — structural, never a Fit Score threshold.
 *
 * That is the design decision the whole thing turns on, and the memo's
 * arithmetic is why: an off-category gig cannot score above 60 for an
 * established professional or about 40 for a new one, so any threshold picked
 * above 40 would have shown related work to established professionals and NOT
 * to new ones — inverting the feature on the population it exists to help.
 *
 * It replaces the Emergency Gigs card, which was a hardcoded "DJ Needed
 * Tonight" with an invented payout, an invented countdown and an Accept Now
 * button wired to nothing.
 */
class OpportunityFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $dj;
    private Category $music;
    private Category $djing;
    private Category $liveBands;
    private Category $catering;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // The taxonomy's real shape: 27 Sub categories over 241 Sub-Sub services.
        $this->music  = Category::create(['name' => 'DJs, Live Bands & Musicians', 'slug' => 'music-x', 'kind' => 'service_category']);
        $food         = Category::create(['name' => 'Catering & Food Services', 'slug' => 'food-x', 'kind' => 'service_category']);
        $this->djing     = Category::create(['name' => 'DJ Services', 'slug' => 'dj-x', 'kind' => 'service', 'parent_id' => $this->music->id]);
        $this->liveBands = Category::create(['name' => 'Live Bands', 'slug' => 'band-x', 'kind' => 'service', 'parent_id' => $this->music->id]);
        $this->catering  = Category::create(['name' => 'Buffet Catering', 'slug' => 'buffet-x', 'kind' => 'service', 'parent_id' => $food->id]);

        $this->dj = $this->pro();
        $this->dj->serviceCategories()->sync([$this->djing->id]);
        $this->dj = $this->dj->fresh();
    }

    private function pro(): User
    {
        $user = User::factory()->create(['primary_role' => 'professional']);
        $user->assignRole('professional');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function gig(string $title, Category $category, string $state = 'MD'): Event
    {
        $client = User::factory()->create();
        $client->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        $event = Event::create([
            'title'        => $title,
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'is_published' => true,
            'status'       => 'published',
            'location'     => 'Baltimore, MD',
            'starts_at'    => now()->addMonth(),
        ]);
        $event->categories()->sync([$category->id]);

        return $event->fresh();
    }

    private function titles(array $feed, string $block): array
    {
        return collect($feed[$block])->pluck('event.title')->all();
    }

    public function test_a_listed_service_is_in_the_first_block(): void
    {
        $this->gig('Wedding DJ wanted', $this->djing);

        $feed = OpportunityFeed::for($this->dj);

        $this->assertSame(['Wedding DJ wanted'], $this->titles($feed, 'listed'));
    }

    public function test_a_sibling_service_is_related_not_listed(): void
    {
        // A DJ sees a live-band request: both under DJs, Live Bands & Musicians.
        $this->gig('Live band for a gala', $this->liveBands);

        $feed = OpportunityFeed::for($this->dj);

        $this->assertSame([], $this->titles($feed, 'listed'));
        $this->assertSame(['Live band for a gala'], $this->titles($feed, 'related'));
    }

    public function test_a_different_category_appears_in_neither_block(): void
    {
        // A DJ never sees a catering request — Catering & Food Services is a
        // different Sub entirely, and no threshold tuning is needed to keep it
        // out. That is the point of defining relatedness structurally.
        $this->gig('Buffet for 200', $this->catering);

        $feed = OpportunityFeed::for($this->dj);

        $this->assertSame([], $this->titles($feed, 'listed'));
        $this->assertSame([], $this->titles($feed, 'related'));
    }

    public function test_the_two_blocks_never_hold_the_same_gig(): void
    {
        $this->gig('Wedding DJ wanted', $this->djing);
        $this->gig('Live band for a gala', $this->liveBands);

        $feed = OpportunityFeed::for($this->dj);

        $this->assertEmpty(array_intersect($this->titles($feed, 'listed'), $this->titles($feed, 'related')));
    }

    public function test_my_services_only_drops_the_related_block(): void
    {
        $this->gig('Wedding DJ wanted', $this->djing);
        $this->gig('Live band for a gala', $this->liveBands);

        $feed = OpportunityFeed::for($this->dj, myServicesOnly: true);

        $this->assertSame(['Wedding DJ wanted'], $this->titles($feed, 'listed'));
        $this->assertSame([], $this->titles($feed, 'related'));
    }

    public function test_a_gig_in_another_state_is_in_neither_block(): void
    {
        // R38 still applies to the feed — it is not a way around same-state.
        $this->gig('Delaware DJ wanted', $this->djing, state: 'DE');

        $feed = OpportunityFeed::for($this->dj);

        $this->assertSame([], $this->titles($feed, 'listed'));
        $this->assertSame([], $this->titles($feed, 'related'));
    }

    public function test_an_awarded_gig_is_not_an_opportunity(): void
    {
        $taken = $this->gig('Already booked', $this->djing);
        $taken->update(['supplier_id' => $this->pro()->id]);

        $this->assertSame([], $this->titles(OpportunityFeed::for($this->dj), 'listed'));
    }

    /* ── The cold-start case R61's decision names ──────────── */

    public function test_a_professional_with_no_reviews_still_gets_the_related_block(): void
    {
        // The population Option B exists for. Under a score threshold they
        // would have been the first ones locked out, because a new
        // professional loses the rating component exactly when they most need
        // a fuller feed.
        $newcomer = $this->pro();
        $newcomer->serviceCategories()->sync([$this->djing->id]);

        $this->gig('Live band for a gala', $this->liveBands);

        $this->assertSame(0, $newcomer->reviewsReceived()->count());
        $this->assertSame(['Live band for a gala'], $this->titles(OpportunityFeed::for($newcomer->fresh()), 'related'));
    }

    public function test_a_professional_who_listed_nothing_sees_the_open_board_and_is_told_why(): void
    {
        // Nothing to sort by and nothing to relate to. An empty card would be
        // worse than the board, and worse still without the reason.
        $blank = $this->pro();
        $this->gig('Wedding DJ wanted', $this->djing);

        $feed = OpportunityFeed::for($blank);

        $this->assertFalse($feed['hasServices']);
        $this->assertSame(['Wedding DJ wanted'], $this->titles($feed, 'related'));

        $this->actingAs($blank)->get(route('professional.dashboard'))
            ->assertSee('haven’t listed your services yet', false);
    }

    /* ── What the card shows ───────────────────────────────── */

    public function test_the_dashboard_shows_the_feed_and_not_the_old_invented_card(): void
    {
        $this->gig('Wedding DJ wanted', $this->djing);

        $page = $this->actingAs($this->dj)->get(route('professional.dashboard'));

        $page->assertSuccessful();
        $page->assertSee('Opportunity Feed');
        $page->assertSee('Wedding DJ wanted');

        // The hardcoded Emergency Gigs card and everything invented on it.
        $page->assertDontSee('DJ Needed Tonight');
        $page->assertDontSee('Previous DJ Canceled');
        $page->assertDontSee('02h 15m left');
        $page->assertDontSee('Accept Now');
    }

    public function test_the_related_block_is_labelled_and_carries_no_bid_button(): void
    {
        // A professional who taps a percentage and lands on a trade they do
        // not work in stops trusting the number on every other card too.
        $this->gig('Live band for a gala', $this->liveBands);

        $page = $this->actingAs($this->dj)->get(route('professional.dashboard'));

        $page->assertSee('Outside your listed services');
        $page->assertSee('Not your service');
    }

    /* ── R61: bidding stays gated to listed services ───────── */

    public function test_bidding_on_a_related_gig_is_refused(): void
    {
        // The half that makes "non-actionable" true rather than cosmetic.
        $band = $this->gig('Live band for a gala', $this->liveBands);

        $this->actingAs($this->dj)
            ->get(route('professional.bid.step', ['event' => $band->id]))
            ->assertForbidden();
    }

    public function test_bidding_on_a_listed_service_still_works(): void
    {
        $mine = $this->gig('Wedding DJ wanted', $this->djing);

        $this->actingAs($this->dj)
            ->get(route('professional.bid.step', ['event' => $mine->id]))
            ->assertSuccessful();
    }

    public function test_a_professional_who_listed_nothing_is_not_locked_out_of_bidding(): void
    {
        // They have not opted out of anything — they have not filled the
        // field in. The feed asks them to; the bid form does not punish them
        // for it in the meantime.
        $blank = $this->pro();
        $gig = $this->gig('Wedding DJ wanted', $this->djing);

        $this->actingAs($blank)
            ->get(route('professional.bid.step', ['event' => $gig->id]))
            ->assertSuccessful();
    }
}
