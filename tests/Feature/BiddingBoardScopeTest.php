<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The board's model: type is how a request reaches professionals (BSR / ESR /
 * DSR) and scope is how many services are in it (SSR / MSR). They used to share
 * one badge, so a card could say MSR while the tab above it said BSR.
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
        $this->pro->assignRole('supplier');
        $this->pro->givePermissionTo('dashboard.view');
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    private static int $n = 0;

    /** No Event/Category factories in this project — build the rows directly. */
    private function event(int $services, array $attrs = []): Event
    {
        $client = User::factory()->create();

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

        // ESR and MSR gigs are held back from non-Elite tiers for 60 minutes
        // after posting. These tests are about scope and type, not that delay,
        // so the gig is backdated past the window — written straight to the
        // row because Eloquent stamps created_at itself on insert.
        \Illuminate\Support\Facades\DB::table('events')
            ->where('id', $event->id)
            ->update(['created_at' => now()->subHours(2)]);

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

        $this->assertSame('BSR', $gig['type'], 'a broadcast gig is type BSR whatever its size');
        $this->assertSame('MSR', $gig['scope'], 'three services is multi-service scope');
    }

    public function test_a_single_service_rush_is_esr_by_type_and_ssr_by_scope(): void
    {
        $this->event(1, ['source' => 'esr']);

        $gig = $this->gigs()[0];

        $this->assertSame('ESR', $gig['type']);
        $this->assertSame('SSR', $gig['scope']);
    }

    public function test_the_scope_filter_selects_on_service_count(): void
    {
        $this->event(1);
        $this->event(2);
        $this->event(4);

        $this->assertCount(3, $this->gigs());
        $this->assertCount(1, $this->gigs(['scope' => 'single']));
        $this->assertCount(2, $this->gigs(['scope' => 'multi']));
    }

    public function test_an_awarded_gig_leaves_the_board(): void
    {
        $this->event(2);
        $awarded = $this->event(2, ['supplier_id' => User::factory()->create()->id]);

        $ids = array_column($this->gigs(), 'event_id');

        $this->assertNotContains($awarded->id, $ids, 'a gig already awarded to another pro is not biddable');
        $this->assertCount(1, $ids);
    }

    public function test_the_old_multi_service_page_redirects_to_the_filtered_board(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.multi-service.index'))
            ->assertRedirect(route('professional.bidding-board.index', ['scope' => 'multi']));
    }
}
