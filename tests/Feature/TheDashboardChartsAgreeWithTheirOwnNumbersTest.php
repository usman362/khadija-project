<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\OpportunityFeed;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two pictures on the dashboard that were drawn rather than measured.
 *
 * The bid ring was six fixed slices of a pie written into the stylesheet, so
 * it showed a six-way split beside a legend reading one hundred percent open.
 * The Opportunity Feed had no notion of a closed request, so six requests
 * whose bidding shut months ago filled a card capped at six rows, and every
 * line of a feed about available work read "Closed to bids".
 */
class TheDashboardChartsAgreeWithTheirOwnNumbersTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Orla Beckwith');
        $this->client = $this->person('client', 'Cassius Lowell');

        $this->service = Category::create([
            'name' => 'Wedding DJs', 'slug' => 'wedding-djs-'.uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->syncWithoutDetaching([$this->service->id]);
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function request(array $over = []): Event
    {
        $event = Event::create(array_merge([
            'title'             => 'Lowell reception',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'status'            => 'published',
            'is_published'      => true,
            'starts_at'         => now()->addDays(30),
            'proposal_deadline' => now()->addDays(10),
            'location'          => 'Baltimore, MD',
            'state'             => 'MD',
        ], $over));

        $event->categories()->syncWithoutDetaching([$this->service->id]);

        return $event;
    }

    /** The ring is built from the counts printed beside it. */
    public function test_the_bid_ring_is_drawn_from_the_bids(): void
    {
        foreach (['won', 'won', 'won', 'submitted'] as $status) {
            Bid::create([
                'event_id'    => $this->request()->id,
                'supplier_id' => $this->pro->id,
                'category_id' => $this->service->id,
                'amount'      => 1000,
                'status'      => $status,
            ]);
        }

        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        // Three won and one open: a quarter blue, three quarters green.
        $this->assertStringContainsString('conic-gradient(', $html);
        $this->assertStringContainsString('#2563eb 0% 25%', $html);
        $this->assertStringContainsString('#10b981 25% 100%', $html);

        // And the six invented slices are gone for good.
        $this->assertStringNotContainsString('#8b5cf6 32% 56%', $html);
    }

    /** No bids means no ring, rather than a ring of somebody else's figures. */
    public function test_no_bids_draws_no_ring(): void
    {
        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('You have not bid on anything yet.', $html);
        $this->assertStringNotContainsString('conic-gradient(', $html);
    }

    /** A request past its deadline is not an opportunity. */
    public function test_a_closed_request_is_not_in_the_feed(): void
    {
        $open   = $this->request(['title' => 'Still taking proposals']);
        $closed = $this->request([
            'title'             => 'Shut in August',
            'proposal_deadline' => now()->subMonths(3),
        ]);

        $feed = OpportunityFeed::for($this->pro);
        $titles = $feed['listed']->map(fn ($row) => $row['event']->title)->all();

        $this->assertContains($open->title, $titles);
        $this->assertNotContains($closed->title, $titles);
    }

    /** Nor is one whose own date has already gone by. */
    public function test_a_request_whose_date_has_passed_is_not_in_the_feed(): void
    {
        $this->request([
            'title'             => 'Happened last week',
            'starts_at'         => now()->subWeek(),
            'proposal_deadline' => now()->addDays(5),
        ]);

        $this->assertCount(0, OpportunityFeed::for($this->pro)['listed']);
    }

    /**
     * And a closed one cannot crowd out a live one.
     *
     * The card holds six rows. Seven dead requests used to take every seat,
     * so the live one below them was never seen.
     */
    public function test_dead_requests_do_not_fill_the_card(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->request([
                'title'             => "Shut request {$i}",
                'proposal_deadline' => now()->subMonths(3),
            ]);
        }

        $live = $this->request(['title' => 'The one that is open']);

        $titles = OpportunityFeed::for($this->pro)['listed']
            ->map(fn ($row) => $row['event']->title)->all();

        $this->assertSame([$live->title], $titles);
    }
}
