<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ToolkitTiers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R31: the toolkit add-ons unlock a subset of the 12 Client Toolkit
 * tools, shown as a two-tab table rather than a pair of toggle buttons.
 *
 * R31 also says the breakdown must come from Peter and warns "do not invent
 * it". Only Timeline Builder is confirmed; the rest must read as unsettled
 * rather than presenting a proposal to a paying client as fact.
 */
class ToolkitTierTableTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('client');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_the_table_covers_all_twelve_client_tools(): void
    {
        $this->assertCount(12, ToolkitTiers::clientTools());
        $this->assertCount(12, ToolkitTiers::table('semi'));
    }

    public function test_the_page_opens_on_semi_and_offers_both_tabs(): void
    {
        $response = $this->actingAs($this->client())->get(route('client.toolkit.tiers'));

        $response->assertSuccessful();
        $response->assertViewHas('tab', 'semi');
        $response->assertSee('Semi Tools');
        $response->assertSee('Maximum Tools');
    }

    public function test_an_unknown_tab_falls_back_rather_than_erroring(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.toolkit.tiers', ['tier' => 'nonsense']))
            ->assertSuccessful()
            ->assertViewHas('tab', 'semi');
    }

    public function test_timeline_builder_is_included_at_semi(): void
    {
        $row = ToolkitTiers::table('semi')->firstWhere('title', 'Timeline Builder');

        $this->assertTrue($row['included']);
        $this->assertTrue($row['confirmed'], 'this is the one assignment seen live');
    }

    public function test_maximum_is_a_superset_of_semi(): void
    {
        $semi = ToolkitTiers::table('semi')->where('included', true)->pluck('title');
        $max  = ToolkitTiers::table('maximum')->where('included', true)->pluck('title');

        $this->assertEmpty($semi->diff($max)->all(), 'everything Semi unlocks, Maximum unlocks too');
    }

    public function test_a_tool_with_no_tier_yet_is_included_in_neither(): void
    {
        foreach (['semi', 'maximum'] as $tier) {
            $row = ToolkitTiers::table($tier)->firstWhere('title', 'Smart Checklist');
            $this->assertFalse($row['included'], "no assignment must not read as included at {$tier}");
        }
    }

    public function test_unconfirmed_rows_are_shown_as_unsettled_not_as_fact(): void
    {
        $this->assertContains('Budget Planner', ToolkitTiers::unconfirmed()->all());
        $this->assertNotContains('Timeline Builder', ToolkitTiers::unconfirmed()->all());

        $this->actingAs($this->client())
            ->get(route('client.toolkit.tiers'))
            ->assertSee('Being finalised')
            ->assertSee('still being finalised');
    }

    public function test_confirming_every_tool_removes_the_warning(): void
    {
        $tools = collect(config('toolkit-tiers.tools'))
            ->map(fn ($row) => ['tier' => $row['tier'] ?? 'semi', 'confirmed' => true, 'note' => null])
            ->all();

        config(['toolkit-tiers.tools' => $tools]);

        $this->assertTrue(ToolkitTiers::allConfirmed());

        $this->actingAs($this->client())
            ->get(route('client.toolkit.tiers'))
            ->assertDontSee('Being finalised');
    }
}
