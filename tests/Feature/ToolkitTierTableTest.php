<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ToolkitTiers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R31, answered in full by Peter on 2026-08-05: the toolkit tiers unlock
 * a subset of the 12 tools, shown as a tab table rather than toggle buttons.
 *
 *   Manual   nothing, always, both sides — a preset, not a list
 *   Semi     six chosen tools, $2.99 one-time
 *   Maximum  all twelve, $5.99 one-time
 *
 * Professionals are gated by membership on top of that: Starter gets Manual
 * only, and Elite is offered Maximum only — the top membership has nothing
 * lower to choose from.
 */
class ToolkitTierTableTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'client'): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_each_side_has_twelve_tools(): void
    {
        $this->assertCount(12, ToolkitTiers::toolsFor(ToolkitTiers::CLIENT));
        $this->assertCount(12, ToolkitTiers::toolsFor(ToolkitTiers::PROFESSIONAL));
    }

    public function test_the_tiers_unlock_none_then_six_then_everything(): void
    {
        foreach ([ToolkitTiers::CLIENT, ToolkitTiers::PROFESSIONAL] as $audience) {
            $this->assertSame(0, ToolkitTiers::countFor('manual', $audience), "Manual must unlock nothing for {$audience}");
            $this->assertSame(
                $audience === ToolkitTiers::CLIENT ? 8 : 6,
                ToolkitTiers::countFor('semi', $audience),
                "the Semi subset is wrong for {$audience}",
            );
            $this->assertSame(12, ToolkitTiers::countFor('maximum', $audience), "Maximum is everything for {$audience}");
        }
    }

    public function test_the_prices_are_free_two_ninety_nine_and_five_ninety_nine(): void
    {
        $this->assertSame(0.0, ToolkitTiers::price('manual'));
        $this->assertSame(2.99, ToolkitTiers::price('semi'));
        $this->assertSame(5.99, ToolkitTiers::price('maximum'));
    }

    public function test_the_two_sides_have_different_semi_sets(): void
    {
        $client = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)->where('included', true)->pluck('title');
        $pro    = ToolkitTiers::table('semi', ToolkitTiers::PROFESSIONAL)->where('included', true)->pluck('title');

        $this->assertContains('Budget Planner', $client->all());
        $this->assertContains('Bid Calculator', $pro->all());
        // Best Match is Semi for professionals but Maximum-only for clients.
        $this->assertNotContains('Best Match', $client->all());
        $this->assertNotContains('Bid Calculator', $client->all(), 'a professional tool must not appear in the client set');

        // Message Builder is shared and sits at Semi on both sides.
        $this->assertContains('Message Builder', $client->all());
        $this->assertContains('Message Builder', $pro->all());
    }

    public function test_timeline_builder_stays_at_semi(): void
    {
        // Khadijah's revised client list of 2026-08-05 names 11 of the 12
        // tools and leaves Timeline Builder out of BOTH tiers — an omission,
        // not a decision. It is the one assignment confirmed live on the
        // screen Peter reviewed (R31, 2026-07-24), so it stays at Semi until
        // he says otherwise.
        $row = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)->firstWhere('title', 'Timeline Builder');

        $this->assertTrue($row['included']);
    }

    public function test_the_client_semi_set_matches_khadijahs_revised_list(): void
    {
        $semi = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)
            ->where('included', true)->pluck('title')->sort()->values()->all();

        $this->assertSame([
            'Budget Planner',
            'Guest Capacity Calculator',
            'Message Builder',
            'Review Builder',
            'Smart Checklist',
            'Style & Inspiration',
            'Timeline Builder',
            'Venue Compatibility Check',
        ], $semi);

        // Moved to Maximum-only by her revision.
        foreach (['Best Match', 'Guided Event Planner', 'Contract Assistant', 'Language'] as $tool) {
            $this->assertFalse(
                ToolkitTiers::unlocks('semi', $tool, ToolkitTiers::CLIENT),
                "{$tool} is Maximum-only on the client side",
            );
        }
    }

    public function test_a_shared_tool_can_sit_at_a_different_tier_on_each_side(): void
    {
        // Review Builder is one of the four tools both sides use, and the two
        // sides landed on different answers: Khadijah put it in the client's
        // Semi set on 2026-08-05, while Peter's professional sheet keeps it
        // Maximum-only. Both are deliberate, so the tier is per side.
        $this->assertTrue(ToolkitTiers::unlocks('semi', 'Review Builder', ToolkitTiers::CLIENT));
        $this->assertFalse(ToolkitTiers::unlocks('semi', 'Review Builder', ToolkitTiers::PROFESSIONAL));

        // Maximum is everything, so it carries the tool on both sides.
        foreach ([ToolkitTiers::CLIENT, ToolkitTiers::PROFESSIONAL] as $audience) {
            $this->assertTrue(ToolkitTiers::unlocks('maximum', 'Review Builder', $audience));
        }
    }

    public function test_a_starter_membership_can_only_have_manual(): void
    {
        $pro = $this->user('professional');
        $this->assertSame(['manual'], ToolkitTiers::purchasableBy($pro));
    }

    public function test_a_client_may_buy_either_tier(): void
    {
        $this->assertSame(['manual', 'semi', 'maximum'], ToolkitTiers::purchasableBy($this->user()));
    }

    public function test_the_page_opens_on_semi_and_lists_all_three_tiers(): void
    {
        $response = $this->actingAs($this->user())->get(route('client.toolkit.tiers'));

        $response->assertSuccessful();
        $response->assertViewHas('tab', 'semi');
        $response->assertSee('Manual');
        $response->assertSee('Semi');
        $response->assertSee('Maximum');
        $response->assertSee('$2.99');
    }

    public function test_the_manual_tab_says_it_has_nothing_rather_than_listing_twelve_refusals(): void
    {
        $this->actingAs($this->user())
            ->get(route('client.toolkit.tiers', ['tier' => 'manual']))
            ->assertSuccessful()
            ->assertSee('Manual includes no tools')
            ->assertDontSee('Not included');
    }

    public function test_an_unknown_tab_falls_back_rather_than_erroring(): void
    {
        $this->actingAs($this->user())
            ->get(route('client.toolkit.tiers', ['tier' => 'nonsense']))
            ->assertSuccessful()
            ->assertViewHas('tab', 'semi');
    }
}
