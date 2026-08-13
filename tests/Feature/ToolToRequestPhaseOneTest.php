<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Checklist row 226, Phase 1 — "Post as BSR" from a tool result.
 *
 * R40's vision is five outcomes (BSR, ESR, Direct Offer, Save Draft, Attach to
 * Existing Event) across twelve tools. The approved Phase 1 scope is ONE of
 * them from THREE tools, to prove the handoff before committing to sixty
 * combinations — so the other four legs being absent is the specification, not
 * an omission.
 *
 * The judgement worth recording is what crosses over. A request carries what
 * the CLIENT typed — event type, date, guests, budget, location — and not the
 * tool's output. A suggested timeline is a suggestion; a request that quietly
 * asks professionals to bid against a machine's guess is not what the client
 * wrote, and they would be signing for it.
 */
class ToolToRequestPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    private function handOff(array $overrides = [])
    {
        return $this->actingAs($this->client)->post(route('client.bsr.from-tool'), array_merge([
            'tool_key'    => 'budget-allocator',
            'tool_name'   => 'Budget Planner',
            'event_type'  => 'Wedding',
            'event_date'  => now()->addMonths(4)->format('Y-m-d'),
            'guest_count' => 120,
            'budget'      => 18000,
            'location'    => 'Baltimore, MD',
        ], $overrides));
    }

    private function wizard(): array
    {
        return (array) Session::get('bsr_wizard', []);
    }

    /* ── The handoff itself ─────────────────────────────────── */

    public function test_what_the_client_typed_carries_into_the_request(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff()->assertRedirect(route('client.bsr.step', 'service'));

        $w = $this->wizard();

        $this->assertSame('Wedding', $w['event_type']);
        $this->assertSame(120, $w['guest_count']);
        $this->assertSame('Baltimore, MD', $w['location']);
        $this->assertSame(18000.0, $w['budget_min']);
        $this->assertStringStartsWith(now()->addMonths(4)->format('Y-m-d'), $w['starts_at']);
    }

    /** It lands on the first step, because no tool asks what a request needs first. */
    public function test_it_lands_on_the_step_the_client_still_has_to_answer(): void
    {
        $this->handOff()->assertRedirect(route('client.bsr.step', 'service'));

        $this->assertArrayNotHasKey('services', $this->wizard(), 'no tool asked which services');
    }

    /** Nothing is published, and no Event row appears. */
    public function test_nothing_is_posted_to_professionals(): void
    {
        $this->handOff();

        $this->assertDatabaseCount('events', 0);
    }

    /**
     * A free-text event type only becomes the request's event type if it names
     * one that exists. "Sarah and Alex's big day" is not a category.
     */
    public function test_an_unrecognised_event_type_is_not_invented_into_one(): void
    {
        $this->handOff(['event_type' => "Sarah and Alex's big day"]);

        $w = $this->wizard();

        $this->assertArrayNotHasKey('event_type', $w);
        $this->assertArrayNotHasKey('title', $w, 'no title either — a title built on a non-category is a guess');
    }

    public function test_a_recognised_event_type_gives_the_request_a_working_title(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff();

        $this->assertSame('Wedding — ' . now()->addMonths(4)->format('F Y'), $this->wizard()['title']);
    }

    /* ── The scope boundary ─────────────────────────────────── */

    /** Phase 1 is three tools. A fourth is refused rather than quietly accepted. */
    public function test_only_the_three_approved_tools_can_hand_off(): void
    {
        $this->handOff(['tool_key' => 'theme-advisor'])->assertSessionHasErrors('tool_key');

        $this->assertSame([], $this->wizard());
    }

    public function test_the_three_approved_tools_are_the_ones_the_scope_names(): void
    {
        $this->assertSame(
            ['budget-allocator', 'event-planner', 'timeline-builder'],
            \App\Http\Controllers\Client\ClientBsrController::FROM_TOOL,
        );
    }

    /** Every one of the three offers the control. */
    public function test_each_approved_tool_carries_the_control(): void
    {
        $views = [
            'budget-allocator' => 'views/client/ai-tools/budget-allocator.blade.php',
            'event-planner'    => 'views/ai-tools/event-planner.blade.php',
            'timeline-builder' => 'views/ai-tools/timeline-builder.blade.php',
        ];

        foreach ($views as $key => $path) {
            $this->assertStringContainsString(
                "<x-post-as-bsr tool-key=\"{$key}\"",
                file_get_contents(resource_path($path)),
                "{$key} has no way to post its result as a request",
            );
        }
    }

    /* ── What the client is told ────────────────────────────── */

    public function test_the_wizard_says_where_the_prefilled_answers_came_from(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff();

        $page = $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))->assertOk();

        $page->assertSee('Started from Budget Planner', false);
        $page->assertSee('yours to change', false);
    }

    /** And an ordinary wizard, started from nothing, says no such thing. */
    public function test_a_wizard_started_normally_carries_no_handoff_notice(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->assertOk()
            ->assertDontSee('Started from', false);
    }

    /** A professional cannot use it — posting a request is the client's action. */
    public function test_a_professional_cannot_start_a_request_from_a_tool(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $this->actingAs($pro)->post(route('client.bsr.from-tool'), [
            'tool_key' => 'budget-allocator', 'tool_name' => 'Budget Planner',
        ])->assertForbidden();
    }
}
