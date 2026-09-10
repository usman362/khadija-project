<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Events, as seen on the live site on 2026-09-10, after the calendar and
 * live-update release. Each of these was visible in Ali's screenshots.
 */
class MyEventsUiPolishTest extends TestCase
{
    use RefreshDatabase;

    private function page(): string
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $client = User::factory()->create();
        $client->assignRole('client');

        return $this->actingAs($client->fresh())->get(route('client.events.index'))->assertOk()->getContent();
    }

    private function source(string $view): string
    {
        return file_get_contents(resource_path("views/{$view}.blade.php"));
    }

    public function test_quick_actions_is_gone_and_recent_activity_is_full_width(): void
    {
        $html = $this->page();

        $this->assertStringNotContainsString('Quick Actions', $html);
        $this->assertStringNotContainsString('mg-qa-grid', $html);
        $this->assertStringContainsString('Recent Professional Activity', $html);
        $this->assertStringContainsString('.mg-row2 { display: grid; grid-template-columns: minmax(0, 1fr);', $html);
    }

    /** The sub-tab strip had no active underline and oversized labels. */
    public function test_the_sub_tab_buttons_keep_their_underline_and_size(): void
    {
        $src = $this->source('client/events/index');
        preg_match('/button\.mg-subtab \{([^}]*)\}/', $src, $m);

        $this->assertNotEmpty($m, 'The sub-tab button reset is missing.');
        $this->assertStringNotContainsString('border: 0', $m[1]);
        $this->assertStringNotContainsString('font: inherit', $m[1]);
        $this->assertStringContainsString('border-width: 0 0 2px', $m[1]);
    }

    /** One day with an event pushed the other six columns into slivers. */
    public function test_the_calendar_has_seven_equal_columns(): void
    {
        $this->assertStringContainsString('.ec-grid { table-layout: fixed; }', $this->source('client/events/index'));
    }

    /** The live-region rule, loaded last, made the sticky rail stop following the scroll. */
    public function test_the_live_region_rule_does_not_override_the_rails_position(): void
    {
        $src = $this->source('partials/_live_regions');

        $this->assertStringContainsString(':where([data-live-region]) { position: relative; }', $src);
        $this->assertDoesNotMatchRegularExpression('/^\s*\[data-live-region\]\s*\{[^}]*position/m', $src);
    }

    /** The address said calview=month over the events list. */
    public function test_switching_tabs_updates_the_address(): void
    {
        $src = $this->source('client/events/index');

        $this->assertStringContainsString("u.searchParams.set('tab', this.dataset.tab);", $src);
    }
}
