<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue 1 of the Professional IA Consolidation Plan, approved by Sir Peter on
 * 2026-08-05 (R42, scope amended): Contracts, My Gigs and the Gig Operations
 * Hub all showed the same jobs in different layouts, so they became one
 * workspace with three tabs.
 *
 * His condition was that this is "a UI and workflow consolidation only — it
 * does not change the ownership of data or calculations", which is what most
 * of these tests are really checking.
 */
class GigHubMergeTest extends TestCase
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
        $this->pro->givePermissionTo(['dashboard.view', 'events.view_any']);
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    private function gig(string $status = 'confirmed', float $price = 1000): Booking
    {
        $client = User::factory()->create();

        $event = Event::create([
            'title'      => 'Gig',
            'created_by' => $client->id,
            'client_id'  => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addMonth(),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $client->id,
            'status'      => $status,
            'price'       => $price,
            'currency'    => 'USD',
        ]);
    }

    public function test_the_hub_opens_on_overview_and_offers_all_three_tabs(): void
    {
        $response = $this->actingAs($this->pro)->get(route('professional.gig-hub.index'));

        $response->assertSuccessful();
        $response->assertViewHas('tab', 'overview');
        $response->assertSee('My Gigs');
        $response->assertSee('Contracts');
    }

    public function test_each_tab_renders(): void
    {
        $this->gig();

        foreach (['overview', 'gigs', 'contracts'] as $tab) {
            $this->actingAs($this->pro)
                ->get(route('professional.gig-hub.index', ['tab' => $tab]))
                ->assertSuccessful()
                ->assertViewHas('tab', $tab);
        }
    }

    public function test_an_unknown_tab_falls_back_rather_than_erroring(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index', ['tab' => 'nonsense']))
            ->assertSuccessful()
            ->assertViewHas('tab', 'overview');
    }

    public function test_the_old_contracts_page_lands_on_the_contracts_tab(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.contracts.index'))
            ->assertRedirect(route('professional.gig-hub.index', ['tab' => 'contracts']));
    }

    public function test_the_old_my_gigs_page_lands_on_the_gigs_tab(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.gigs.index'))
            ->assertRedirect(route('professional.gig-hub.index', ['tab' => 'gigs']));
    }

    public function test_the_sidebar_no_longer_offers_the_two_merged_pages(): void
    {
        $sidebar = file_get_contents(resource_path('views/layouts/professional.blade.php'));

        $this->assertStringNotContainsString("route('professional.contracts.index')", $sidebar);
        $this->assertStringNotContainsString("route('professional.gigs.index')", $sidebar);
        $this->assertStringContainsString("route('professional.gig-hub.index')", $sidebar);
    }

    public function test_money_on_the_hub_comes_from_the_payments_source(): void
    {
        // Sir Peter's condition: the merge does not change who owns a
        // calculation. The hub shows earnings but must not add up booking
        // prices itself — that is what had Earnings and Transactions
        // disagreeing about the same account.
        $this->gig('completed', 1000);

        $money = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))
            ->viewData('money');

        $this->assertSame(
            \App\Support\Earnings::forProfessional($this->pro->fresh()),
            $money,
        );

        // 5% commission on $1,000 — net, not gross.
        $this->assertSame(950.0, $money['earned']);
    }

    public function test_the_gigs_tab_counts_the_same_jobs_as_the_hub(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->gig();
        }

        $response = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index', ['tab' => 'gigs']));

        $this->assertCount(3, $response->viewData('gigs'), 'the hub sees three jobs');
        $this->assertSame(3, $response->viewData('myGigs')->total(), 'and so does the gigs tab');
    }
}
