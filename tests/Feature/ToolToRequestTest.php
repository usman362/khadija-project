<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Checklist row 226 — turning a tool result into a request.
 *
 * R40's vision is five outcomes (BR, ER, Direct Request, Save Draft, Attach to
 * Existing Event) across twelve tools. Phase 1 proved ONE outcome from THREE
 * tools; Phase 2 is three outcomes across seven.
 *
 * Neither number is a shortfall. Four of the twelve describe something that is
 * not an event to be requested — a review, a signed contract, a message, a
 * phrase — and the two remaining legs already have homes: "attach to an
 * existing event" lives on the event page, and a Direct Request needs a
 * professional, which only Best Match names.
 *
 * The judgement worth recording is what crosses over. A request carries what
 * the CLIENT typed — event type, date, guests, budget, location — and not the
 * tool's output. A suggested timeline is a suggestion; a request that quietly
 * asks professionals to bid against a machine's guess is not what the client
 * wrote, and they would be signing for it.
 */
class ToolToRequestTest extends TestCase
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
            'outcome'     => 'bidding',
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

    /**
     * A tool outside the list is refused rather than quietly accepted.
     * review-writer is the clearest case: a review is written after the job.
     */
    public function test_only_the_approved_tools_can_hand_off(): void
    {
        $this->handOff(['tool_key' => 'review-writer'])->assertSessionHasErrors('tool_key');

        $this->assertSame([], $this->wizard());
    }

    public function test_the_approved_tools_are_the_ones_the_scope_names(): void
    {
        $this->assertSame(
            [
                'budget-allocator', 'event-planner', 'timeline-builder',
                'checklist-generator', 'theme-advisor', 'venue-analyzer', 'guest-capacity',
                'contract-assistant',
            ],
            \App\Http\Controllers\Client\ClientBsrController::FROM_TOOL,
        );
    }

    /** Every approved tool offers the control, and the list cannot drift. */
    public function test_each_approved_tool_carries_the_control(): void
    {
        $views = [
            'budget-allocator'    => 'views/client/ai-tools/budget-allocator.blade.php',
            'event-planner'       => 'views/ai-tools/event-planner.blade.php',
            'timeline-builder'    => 'views/ai-tools/timeline-builder.blade.php',
            'checklist-generator' => 'views/ai-tools/checklist-generator.blade.php',
            'theme-advisor'       => 'views/ai-tools/theme-advisor.blade.php',
            'venue-analyzer'      => 'views/ai-tools/venue-analyzer.blade.php',
            'guest-capacity'      => 'views/ai-tools/guest-capacity.blade.php',
            'contract-assistant'  => 'views/ai-tools/contract-assistant.blade.php',
        ];

        $this->assertSame(
            \App\Http\Controllers\Client\ClientBsrController::FROM_TOOL,
            array_keys($views),
            'a tool was approved without a control, or given one without approval',
        );

        foreach ($views as $key => $path) {
            $this->assertStringContainsString(
                "<x-post-as-request tool-key=\"{$key}\"",
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

    /* ── Phase 2: the other two outcomes ────────────────────── */

    /**
     * The ER form reads old(), so the carried facts arrive as flashed input
     * and the view needed no change — one mapping, not two.
     */
    public function test_an_urgent_handoff_lands_on_the_emergency_form_prefilled(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff(['outcome' => 'emergency'])
            ->assertRedirect(route('client.esr.create'))
            ->assertSessionHasInput('location', 'Baltimore, MD')
            ->assertSessionHasInput('guest_count', 120);
    }

    /** The urgent route opens too, with the carried facts on the page. */
    public function test_the_emergency_form_opens_with_the_carried_facts_on_it(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->actingAs($this->client)
            ->followingRedirects()
            ->post(route('client.bsr.from-tool'), [
                'outcome'     => 'emergency',
                'tool_key'    => 'budget-allocator',
                'tool_name'   => 'Budget Planner',
                'event_type'  => 'Wedding',
                'event_date'  => now()->addMonths(4)->format('Y-m-d'),
                'guest_count' => 120,
                'location'    => 'Baltimore, MD',
            ])
            ->assertOk()
            ->assertSee('Baltimore, MD', false);
    }

    /**
     * Why it is urgent is not carried, because no tool asks. Choosing a reason
     * on the client's behalf would put words in their mouth on a form they
     * sign — and the reason is the only thing that makes it an emergency.
     */
    public function test_the_reason_for_urgency_is_left_for_the_client_to_give(): void
    {
        $this->handOff(['outcome' => 'emergency']);

        $this->assertNull(session('_old_input')['reason'] ?? null);
    }

    /** Saved, not sent: a draft exists, nothing is published. */
    public function test_a_draft_is_saved_without_posting_anything(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff(['outcome' => 'draft']);

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('events', [
            'client_id'    => $this->client->id,
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /** The draft carries what the client typed, so resuming is not retyping. */
    public function test_the_draft_holds_what_the_tool_carried(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->handOff(['outcome' => 'draft']);

        $event = \App\Models\Event::firstOrFail();

        $this->assertSame('Wedding', $event->event_type);
        $this->assertSame(120, $event->guest_count);
        $this->assertSame('Baltimore, MD', $event->location);
    }

    /**
     * And the draft's destination is a page that actually opens.
     *
     * The redirect target is a permission-gated, ownership-checked route, so
     * asserting the redirect alone would prove only that a Location header was
     * set. Following it is the difference between "we sent them somewhere" and
     * "they arrived".
     */
    public function test_the_client_can_open_the_draft_they_were_sent_to(): void
    {
        Category::firstOrCreate(['slug' => 'wedding'], ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $this->actingAs($this->client)
            ->followingRedirects()
            ->post(route('client.bsr.from-tool'), [
                'outcome'     => 'draft',
                'tool_key'    => 'budget-allocator',
                'tool_name'   => 'Budget Planner',
                'event_type'  => 'Wedding',
                'event_date'  => now()->addMonths(4)->format('Y-m-d'),
                'guest_count' => 120,
                'budget'      => 18000,
                'location'    => 'Baltimore, MD',
            ])
            ->assertOk();
    }

    /** An outcome nobody offered is refused rather than defaulted. */
    public function test_an_unknown_outcome_is_refused(): void
    {
        $this->handOff(['outcome' => 'direct_offer'])->assertSessionHasErrors('outcome');

        $this->assertDatabaseCount('events', 0);
        $this->assertSame([], $this->wizard());
    }

    public function test_an_outcome_must_be_chosen(): void
    {
        $this->handOff(['outcome' => ''])->assertSessionHasErrors('outcome');
    }

    /** All three are offered on the result screen, not just the main one. */
    public function test_the_control_offers_every_outcome(): void
    {
        $markup = file_get_contents(resource_path('views/components/post-as-request.blade.php'));

        foreach (\App\Http\Controllers\Client\ClientBsrController::OUTCOMES as $outcome) {
            $this->assertStringContainsString("value=\"{$outcome}\"", $markup, "no way to choose {$outcome}");
        }
    }
}
