<?php

namespace Tests\Feature;

use App\Models\{Bid, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The comparison existed. The Proposals page had no way into it.
 *
 * Sir Peter, 21 October: "I saw there is not comparison section for the
 * Proposal's webpage in case the client wants to compare the
 * professional/influencer against each other for a particular service."
 *
 * He was looking at a real gap, but not the one the words suggest. Side-by-side
 * comparison has existed since 17 September at client.proposals.compare, and it
 * already filters to one service, which is exactly what he is asking for. What
 * was missing was the door. The only link on the Proposals page appeared after
 * the client had narrowed the whole page to a single request, and it opened the
 * request's own page rather than the comparison. A client who never touched the
 * request filter saw nothing about comparing at all.
 *
 * So these tests are about reachability, not about the comparison itself.
 */
class ComparingProposalsIsReachableTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Event $event;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = $this->person('client');

        $this->service = Category::create([
            'name' => 'DJ Services', 'slug' => 'dj-services-compare',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->event = Event::create([
            'title'        => 'Backyard Birthday Party',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(30),
        ]);

        $this->event->categories()->sync([$this->service->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function bid(float $amount): Bid
    {
        return Bid::create([
            'event_id'    => $this->event->id,
            'supplier_id' => $this->person('professional')->id,
            'category_id' => $this->service->id,
            'amount'      => $amount,
            'status'      => 'submitted',
        ]);
    }

    /** The page as a client first opens it, with no request chosen. */
    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.proposals.index', $query))
            ->assertSuccessful()
            ->getContent();
    }

    /** The comparison, for this service. */
    private function compareUrl(): string
    {
        return route('client.proposals.compare', [
            'event'   => $this->event->id,
            'service' => $this->service->id,
        ]);
    }

    // ── Reachability ─────────────────────────────────────────

    /**
     * Two proposals on one service, and the page offers to compare them.
     *
     * Without narrowing to a request first, which is the state Peter was
     * looking at.
     */
    public function test_the_page_offers_the_comparison_without_picking_a_request_first(): void
    {
        $this->bid(500);
        $this->bid(700);

        $this->assertStringContainsString($this->compareUrl(), $this->page());
    }

    /**
     * One proposal is not a comparison.
     *
     * An invitation to compare one thing against nothing is a dead end wearing
     * a button.
     */
    public function test_a_lone_proposal_is_not_offered_for_comparison(): void
    {
        $this->bid(500);

        $this->assertStringNotContainsString($this->compareUrl(), $this->page());
    }

    /** Scoped to the request, the rail leads to the comparison too. */
    public function test_the_service_rail_leads_to_the_comparison(): void
    {
        $this->bid(500);
        $this->bid(700);

        $html = $this->page(['event' => $this->event->id]);

        $this->assertStringContainsString($this->compareUrl(), $html);
        $this->assertStringContainsString(
            route('client.proposals.compare', $this->event),
            $html,
            'There is no way to compare everything on the request either.',
        );
    }

    /**
     * The link it replaced went to the request page, not to the comparison.
     *
     * Which is the whole reason this looked missing: the words said compare and
     * the destination was somewhere else.
     */
    public function test_the_rail_no_longer_sends_the_client_to_the_request_page(): void
    {
        $this->bid(500);
        $this->bid(700);

        $html = $this->page(['event' => $this->event->id]);

        $this->assertStringNotContainsString(
            'Compare by service',
            $html,
            'The old link is still there, still pointing at the request page.',
        );
    }

    // ── And the comparison itself works from those links ─────

    public function test_the_comparison_opens_and_shows_the_competing_proposals(): void
    {
        $this->bid(500);
        $this->bid(700);

        $html = $this->actingAs($this->client)->get($this->compareUrl())
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('500', $html);
        $this->assertStringContainsString('700', $html);
        $this->assertStringContainsString('DJ Services', $html);
    }

    /** And it is the client's own request only. */
    public function test_another_clients_request_cannot_be_compared(): void
    {
        $this->bid(500);

        $this->actingAs($this->person('client'))
            ->get($this->compareUrl())
            ->assertForbidden();
    }
}
