<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TimelineSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 197, 224 and 225 — the Timeline Builder's two views.
 *
 * Row 197 asks for an operational schedule beside the workflow Gantt,
 * "reading the SAME event data". Rows 224 and 225 are the other half: the
 * second tab must not carry its own Export or Notifications button, because
 * both already exist on the page and in the top nav.
 */
class TimelineBuilderTabsTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function page()
    {
        return $this->actingAs($this->client())->get(route('ai-tools.timeline-builder'));
    }

    public function test_both_tabs_are_offered(): void
    {
        $page = $this->page();

        $page->assertOk();
        $page->assertSee('Timeline View (Workflow)', false);
        $page->assertSee('Interactive Event Timeline (Operational Schedule)', false);
    }

    public function test_the_schedule_has_the_columns_the_row_asks_for(): void
    {
        $page = $this->page();

        foreach (['Time', 'Activity', 'Assigned To', 'Status', 'Notes &amp; Attachments', 'Actions'] as $column) {
            $page->assertSee($column, false);
        }
    }

    /**
     * Rows 224 and 225. An Export button on the second tab would duplicate
     * Export Timeline below it; a Notifications button would duplicate the
     * bell in the top nav. The page keeps exactly one of each.
     */
    public function test_the_second_tab_adds_no_duplicate_controls(): void
    {
        $body = $this->page()->getContent();

        $panel = substr($body, (int) strpos($body, 'data-tb-panel="schedule"'));
        $panel = substr($panel, 0, strpos($panel, 'Export Timeline') ?: 4000);

        $this->assertStringNotContainsString('Export', $panel, 'the schedule tab has its own Export button');
        $this->assertStringNotContainsString('Notification', $panel, 'the schedule tab has its own Notifications button');

        // And there is still exactly one Export Timeline on the page.
        $this->assertSame(1, substr_count($body, 'Export Timeline'));
    }

    /* ── The same data, read two ways ───────────────────────── */

    /**
     * The whole point of row 197: the schedule is DERIVED from the tracks the
     * Gantt draws. A second dataset is how the two tabs end up disagreeing.
     */
    public function test_the_schedule_is_derived_from_the_gantt_tracks(): void
    {
        $tracks = [
            ['Setup',     '#64748b', [['Venue Access', 0, 12]]],
            ['Reception', '#f97316', [['Dinner Service', 50, 20]]],
        ];

        $rows = TimelineSchedule::fromTracks($tracks, ['5 PM', '6 PM', '7 PM', '8 PM', '9 PM']);

        $this->assertCount(2, $rows);
        $this->assertSame('Venue Access', $rows[0]['activity']);
        $this->assertSame('Setup', $rows[0]['track']);
        $this->assertSame('5:00 PM', $rows[0]['time']);
    }

    public function test_the_schedule_reads_in_time_order_not_track_order(): void
    {
        $tracks = [
            ['Vendors',  '#16a34a', [['Load out', 80, 10]]],
            ['Ceremony', '#7c3aed', [['Guest arrival', 10, 10]]],
        ];

        $rows = TimelineSchedule::fromTracks($tracks, ['5 PM', '6 PM', '7 PM', '8 PM', '9 PM']);

        $this->assertSame('Guest arrival', $rows[0]['activity']);
        $this->assertSame('Load out', $rows[1]['activity']);
    }

    /**
     * The last hour label is the final COLUMN's start, so the window runs an
     * hour past it. Without that the evening drifts progressively earlier.
     */
    public function test_the_window_includes_the_final_hour(): void
    {
        $rows = TimelineSchedule::fromTracks(
            [['Reception', '#f97316', [['Last dance', 100, 0]]]],
            ['5 PM', '6 PM', '7 PM'],
        );

        $this->assertSame('8:00 PM', $rows[0]['time']);
    }

    public function test_an_empty_run_of_show_produces_no_rows(): void
    {
        $this->assertSame([], TimelineSchedule::fromTracks([], []));
        $this->assertSame([], TimelineSchedule::fromTracks([['Setup', '#000', []]], ['5 PM']));
    }
}
