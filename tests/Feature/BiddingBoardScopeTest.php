<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The board's model: type is how a request reaches professionals (BR / ER /
 * DR) and scope is how many services are in it (SSR / MSR). They used to share
 * one badge, so a card could say MSR while the tab above it said BR.
 */
class BiddingBoardScopeTest extends TestCase
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

    private static int $n = 0;

    /** No Event/Category factories in this project — build the rows directly. */
    private function event(int $services, array $attrs = []): Event
    {
        $client = User::factory()->create();

        // R38 puts the client's own state on the gig, and the board only shows
        // a professional gigs in their state. These tests are about type and
        // scope, so the client is placed alongside the pro rather than having
        // every case disappear behind a rule they are not exercising.
        $client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $event = Event::create(array_merge([
            'title'        => 'Gig ' . (++self::$n),
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'is_published' => true,
            'status'       => 'published',
            'supplier_id'  => null,
            'source'       => 'user',
            'starts_at'    => now()->addMonth(),
        ], $attrs));

        // ER and MSR gigs are held back from non-Elite tiers for 60 minutes
        // after posting. These tests are about scope and type, not that delay,
        // so the gig is backdated past the window — written straight to the
        // row because Eloquent stamps both of these itself on insert.
        //
        // published_at as well as created_at: "posted" now means the publish
        // stamp, and backdating only created_at left the gig looking posted
        // this second.
        \Illuminate\Support\Facades\DB::table('events')
            ->where('id', $event->id)
            ->update(['created_at' => now()->subHours(2), 'published_at' => now()->subHours(2)]);

        $ids = [];
        for ($i = 0; $i < $services; $i++) {
            $slug    = 'svc-' . self::$n . '-' . $i;
            $ids[]   = Category::create(['name' => 'Service ' . $slug, 'slug' => $slug])->id;
        }
        $event->categories()->sync($ids);

        return $event->fresh();
    }

    private function gigs(array $query = []): array
    {
        $response = $this->actingAs($this->pro)
            ->get(route('professional.bidding-board.index', $query));

        $response->assertSuccessful();

        return $response->viewData('gigs');
    }

    public function test_type_and_scope_are_separate_values(): void
    {
        $this->event(3);

        $gig = $this->gigs()[0];

        $this->assertSame('BR', $gig['type'], 'a broadcast gig is type BR whatever its size');
        $this->assertSame('MSR', $gig['scope'], 'three services is multi-service scope');
    }

    public function test_a_single_service_rush_is_esr_by_type_and_ssr_by_scope(): void
    {
        $this->event(1, ['source' => 'esr']);

        $gig = $this->gigs()[0];

        $this->assertSame('ER', $gig['type']);
        $this->assertSame('SSR', $gig['scope']);
    }

    public function test_the_scope_filter_selects_on_service_count(): void
    {
        $this->event(1);
        $this->event(2);
        $this->event(4);

        /*
         * Counted in CARDS, not requests — checklist row 162 split an MSR
         * into one card per service line (R12). One single-service request
         * plus a two-service and a four-service one is 1 + 2 + 4 = 7 jobs a
         * professional can bid on, which is the number the board should show.
         */
        $this->assertCount(7, $this->gigs());
        $this->assertCount(1, $this->gigs(['scope' => 'single']));
        $this->assertCount(6, $this->gigs(['scope' => 'multi']));

        // The filter still selects on the REQUEST's service count.
        $this->assertSame(
            [2, 4],
            collect($this->gigs(['scope' => 'multi']))->countBy('event_id')->values()->sort()->values()->all(),
        );
    }

    public function test_an_awarded_gig_leaves_the_board(): void
    {
        $this->event(2);
        $awarded = $this->event(2, ['supplier_id' => User::factory()->create()->id]);

        $ids = array_column($this->gigs(), 'event_id');

        $this->assertNotContains($awarded->id, $ids, 'a gig already awarded to another pro is not biddable');

        // Two cards, both from the one remaining request: row 162 splits a
        // two-service MSR into its two service lines.
        $this->assertCount(2, $ids);
        $this->assertCount(1, array_unique($ids));
    }

    public function test_the_old_multi_service_page_redirects_to_the_filtered_board(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.multi-service.index'))
            ->assertRedirect(route('professional.bidding-board.index', ['scope' => 'multi']));
    }

    /**
     * Checklist row 162 (R12) — an MSR is one card per service line.
     *
     * "DJ + Lighting + MC" was a single card. It is three jobs: three
     * contracts, three bids, three professionals in the usual case. A
     * lighting company had to open a card titled after somebody else's trade
     * to find out whether their own service was even in it.
     */
    public function test_a_multi_service_request_becomes_one_card_per_service(): void
    {
        $event = $this->event(3);

        $cards = collect($this->gigs())->where('event_id', $event->id);

        $this->assertCount(3, $cards);
        $this->assertCount(3, $cards->pluck('service_id')->unique());

        // Each card is titled by its own service, not by the request alone.
        foreach ($cards as $card) {
            $this->assertStringContainsString($card['service_name'], $card['title']);
            $this->assertSame([$card['service_name']], $card['tags']);
        }
    }

    /** A single-service request is still one card, with no service split. */
    public function test_a_single_service_request_is_not_split(): void
    {
        $event = $this->event(1);

        $cards = collect($this->gigs())->where('event_id', $event->id);

        $this->assertCount(1, $cards);
        $this->assertNull($cards->first()['service_id']);
    }

    /**
     * The other half of the same problem: a shared bid count read "12 bids"
     * on a card where eleven were for a different trade.
     */
    public function test_the_bid_count_is_per_service_not_per_request(): void
    {
        $event    = $this->event(2);
        $services = $event->categories()->pluck('categories.id')->all();

        \App\Models\Bid::create([
            'event_id' => $event->id, 'supplier_id' => User::factory()->create()->id,
            'category_id' => $services[0], 'amount' => 900, 'status' => 'submitted',
        ]);

        $cards = collect($this->gigs())->where('event_id', $event->id)->keyBy('service_id');

        $this->assertSame(1, $cards[$services[0]]['bids']);
        $this->assertSame(0, $cards[$services[1]]['bids']);
    }

    /**
     * The budget is NOT split. A request carries one budget covering every
     * service; there is no per-service budget anywhere in the data, so
     * dividing it would invent a figure per line the client never gave.
     */
    public function test_the_budget_is_not_divided_between_services(): void
    {
        $event = $this->event(2);

        $cards = collect($this->gigs())->where('event_id', $event->id);

        $this->assertCount(1, $cards->pluck('budget')->unique(), 'each line shows the request budget');
        $this->assertTrue($cards->first()['budget_is_whole_request']);
    }
}
