<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two panels from the 2026-08-04 screenshot review stated things as fact that
 * were not true. They are the same mistake in two places: a screen presenting
 * a number or a state it does not have.
 *
 *   Calendar — "Phone Synchronization" showed Apple and Google Calendar with a
 *   tick and the words "Synced Successfully". No calendar integration exists
 *   at all, so a professional could believe their outside commitments were
 *   reaching GigResource and double-book on the strength of it.
 *
 *   Bid Intelligence — "Competitor Benchmarks" showed Low / Market Avg / High
 *   around the professional's own bid. The three were that same bid times
 *   0.69, 0.94 and 1.19. It compared a professional to themselves and called
 *   it the market, and pricing against it is a decision they would act on.
 */
class NoInventedFiguresTest extends TestCase
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

    public function test_the_calendar_does_not_claim_a_sync_that_does_not_exist(): void
    {
        $page = $this->actingAs($this->pro)->get(route('professional.calendar.index'));

        $page->assertSuccessful();
        $page->assertDontSee('Synced Successfully');
        $page->assertDontSee('Phone Synchronization');
    }

    public function test_no_calendar_integration_is_actually_installed(): void
    {
        // The reason the panel had to go rather than be reworded. If this ever
        // stops being true, the panel can come back showing a real state.
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $packages = array_keys($composer['require'] ?? []);

        foreach (['laravel/socialite', 'google/apiclient', 'spatie/icalendar-generator'] as $package) {
            $this->assertNotContains($package, $packages);
        }
    }

    public function test_bid_intelligence_does_not_present_a_market_it_cannot_see(): void
    {
        $page = $this->actingAs($this->pro)->get(route('professional.bid-intelligence.index'));

        $page->assertSuccessful();
        $page->assertDontSee('Competitor Benchmarks');
        $page->assertDontSee('Market Avg');
        $page->assertDontSee('Your Pricing vs Market');
    }

    public function test_the_professionals_own_average_is_still_shown(): void
    {
        // The real figure was never the problem — only the invented band
        // around it — so removing that must not have taken this with it.
        $page = $this->actingAs($this->pro)->get(route('professional.bid-intelligence.index'));

        $page->assertSee('Your Average Bid');
        $this->assertArrayHasKey('avg', $page->viewData('pricing'));
        $this->assertCount(1, $page->viewData('pricing'), 'nothing but the real number');
    }
}
