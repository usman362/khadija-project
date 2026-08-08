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
 *   Semi     $2.99 one-time — 5 tools for a client, 6 for a professional
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
                $audience === ToolkitTiers::CLIENT ? 5 : 6,
                ToolkitTiers::countFor('semi', $audience),
                "the Semi subset is wrong for {$audience}",
            );
            $this->assertSame(12, ToolkitTiers::countFor('maximum', $audience), "Maximum is everything for {$audience}");
        }
    }

    public function test_included_tools_are_listed_before_the_rest(): void
    {
        // Peter, 2026-08-07: the six you get should read as a block at the
        // top, not be picked out of twelve interleaved rows.
        foreach ([ToolkitTiers::CLIENT, ToolkitTiers::PROFESSIONAL] as $audience) {
            $flags = ToolkitTiers::table('semi', $audience)->pluck('included')->all();

            $this->assertSame(
                array_values($flags),
                collect($flags)->sortDesc()->values()->all(),
                "the {$audience} Semi table still interleaves included and excluded tools",
            );
        }
    }

    public function test_ordering_does_not_scramble_the_tools_within_a_group(): void
    {
        // A stable sort, not a re-shuffle: the catalog order still holds
        // inside the included block and inside the excluded block.
        $catalog = ToolkitTiers::toolsFor(ToolkitTiers::CLIENT)->pluck('name');
        $rows    = ToolkitTiers::table('semi', ToolkitTiers::CLIENT);

        foreach ([true, false] as $group) {
            $shown = $rows->where('included', $group)->pluck('title')->values();
            $expected = $catalog->filter(fn ($n) => $shown->contains($n))->values();

            $this->assertSame($expected->all(), $shown->all());
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
        $this->assertNotContains('Bid Calculator', $client->all(), 'a professional tool must not appear in the client set');

        // Message Builder is shared and sits at Semi on both sides.
        $this->assertContains('Message Builder', $client->all());
        $this->assertContains('Message Builder', $pro->all());
    }

    public function test_timeline_builder_stays_at_semi(): void
    {
        // Khadijah's draft left it out of both tiers, which read as an
        // omission rather than a decision. Peter then traced it: confirmed
        // live at Semi on 2026-07-24, locked into R31, and explicitly kept
        // untouched by every revision since.
        $row = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)->firstWhere('title', 'Timeline Builder');

        $this->assertTrue($row['included']);
    }

    public function test_the_client_semi_set_is_the_five_peter_settled_on(): void
    {
        $semi = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)
            ->where('included', true)->pluck('title')->sort()->values()->all();

        $this->assertSame([
            'Best Match',
            'Budget Planner',
            'Message Builder',
            'Smart Checklist',
            'Timeline Builder',
        ], $semi);
    }

    public function test_the_marketplace_slot_is_best_match_not_review_builder(): void
    {
        // The pair was the wrong way round until 2026-08-08. Khadijah's draft
        // had put Review Builder in Semi; Peter's spreadsheet correction of
        // 2026-08-05, recorded in R31, reverses it — Best Match is Semi,
        // Review Builder is Maximum-only. Finding a professional is where a
        // client starts; Review Builder has nothing to write about until the
        // event is over.
        $this->assertTrue(ToolkitTiers::unlocks('semi', 'Best Match', ToolkitTiers::CLIENT));
        $this->assertFalse(ToolkitTiers::unlocks('semi', 'Review Builder', ToolkitTiers::CLIENT));
    }

    public function test_the_client_maximum_only_set_is_the_seven_r31_lists(): void
    {
        $locked = ToolkitTiers::table('semi', ToolkitTiers::CLIENT)
            ->where('included', false)->pluck('title')->sort()->values()->all();

        $this->assertSame([
            'Contract Assistant',
            'Guest Capacity Calculator',
            'Guided Event Planner',
            'Language',
            'Review Builder',
            'Style & Inspiration',
            'Venue Compatibility Check',
        ], $locked);
    }

    public function test_the_planning_suite_splits_three_to_four(): void
    {
        // Peter, 2026-08-05, on the Planning suite specifically.
        foreach (['Budget Planner', 'Smart Checklist', 'Timeline Builder'] as $tool) {
            $this->assertTrue(
                ToolkitTiers::unlocks('semi', $tool, ToolkitTiers::CLIENT),
                "{$tool} is one of the three Planning tools at Semi",
            );
        }

        foreach (['Guided Event Planner', 'Guest Capacity Calculator',
                  'Venue Compatibility Check', 'Style & Inspiration'] as $tool) {
            $this->assertFalse(
                ToolkitTiers::unlocks('semi', $tool, ToolkitTiers::CLIENT),
                "{$tool} is Maximum-only",
            );
        }
    }

    public function test_the_other_two_suites_follow_the_r31_correction(): void
    {
        // Marketplace gives Semi one tool, Operations gives it one.
        $this->assertTrue(ToolkitTiers::unlocks('semi', 'Best Match', ToolkitTiers::CLIENT));
        $this->assertTrue(ToolkitTiers::unlocks('semi', 'Message Builder', ToolkitTiers::CLIENT));

        foreach (['Review Builder', 'Contract Assistant', 'Language'] as $tool) {
            $this->assertFalse(
                ToolkitTiers::unlocks('semi', $tool, ToolkitTiers::CLIENT),
                "{$tool} is Maximum-only on the client side",
            );
        }
    }

    public function test_a_shared_tool_can_sit_at_a_different_tier_on_each_side(): void
    {
        // Message Builder is one of the four tools both sides use, and both
        // sides put it at Semi. Review Builder is the counter-example: shared,
        // but Maximum-only on each side — so the tier is resolved per side
        // rather than once per tool.
        foreach ([ToolkitTiers::CLIENT, ToolkitTiers::PROFESSIONAL] as $audience) {
            $this->assertTrue(ToolkitTiers::unlocks('semi', 'Message Builder', $audience));
            $this->assertFalse(ToolkitTiers::unlocks('semi', 'Review Builder', $audience));
            // Maximum is everything, so it carries both on both sides.
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
    public function test_both_sidebars_link_to_the_page(): void
    {
        // It was built and reachable by URL but not linked anywhere, so
        // nobody could find it.
        foreach (['client', 'professional'] as $role) {
            $sidebar = file_get_contents(resource_path("views/layouts/{$role}.blade.php"));

            $this->assertStringContainsString(
                "route('client.toolkit.tiers')",
                $sidebar,
                "the {$role} sidebar has no way into the toolkit tiers page",
            );
        }
    }
}
