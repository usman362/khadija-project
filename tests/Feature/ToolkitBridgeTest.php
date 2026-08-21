<?php

namespace Tests\Feature;

use App\Domain\Toolkit\ToolkitBridge;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\Event;
use App\Models\EventAiArtifact;
use App\Models\ToolkitAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R30 — the Client Toolkit → Request / Agreement bridge.
 *
 * These guard the promises the screen makes, not the arrangement of its boxes:
 * that removing placed data leaves the saved result alone, that a signed
 * agreement cannot be edited through this door, that "keep linked" never
 * rewrites a figure without asking, and that one professional's agreement is
 * not a shortcut into everybody's.
 */
class ToolkitBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->givePermissionTo('dashboard.view');
        $this->client->givePermissionTo('agreements.view_any');
        $this->client->givePermissionTo('events.view');
        $this->client = $this->client->fresh();
    }

    /** A professional who can reach the agreement page at all. */
    private function professional(): User
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->givePermissionTo('agreements.view_any');

        return $pro->fresh();
    }

    private function event(string $title = 'Wedding'): Event
    {
        return Event::create([
            'title'      => $title,
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
            'status'     => 'pending',
            'starts_at'  => now()->addMonth(),
        ]);
    }

    private function artifact(string $tool = 'budget-allocator', array $payload = ['total' => 4200]): EventAiArtifact
    {
        return EventAiArtifact::create([
            'event_id'  => $this->event('Source event')->id,
            'user_id'   => $this->client->id,
            'tool_key'  => $tool,
            'tool_name' => 'Budget Planner',
            'title'     => 'Wedding budget',
            'payload'   => $payload,
            'mode'      => 'manual',
        ]);
    }

    private function agreement(string $status = 'draft', ?User $pro = null): Agreement
    {
        $pro ??= $this->professional();

        $booking = Booking::create([
            'event_id'    => $this->event('Booked event')->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $pro->id,
            'created_by'  => $this->client->id,
            'status'      => 'pending',
            'price'       => 1000,
            'currency'    => 'USD',
        ]);

        return Agreement::create([
            'booking_id'   => $booking->id,
            'generated_by' => $this->client->id,
            'title'        => 'Agreement with ' . $pro->name,
            'content'      => '<p>Terms</p>',
            'status'       => $status,
            'version'      => 1,
            'source'       => 'manual',
        ]);
    }

    // ── What the toolkit will and will not hand over ──────────────

    /**
     * Best Match starts an agreement; it is not a figure to put inside one.
     * Review Builder runs after the event. Neither is a data source, and the
     * page says why rather than quietly showing ten tools out of twelve.
     */
    public function test_tools_that_hold_no_data_are_shown_with_the_reason(): void
    {
        $tools = ToolkitBridge::toolsFor($this->client);

        $best = $tools->firstWhere('key', 'vendor-matchmaking');
        $this->assertFalse($best['usable']);
        $this->assertStringContainsString('starts an agreement', $best['reason']);

        $review = $tools->firstWhere('key', 'review-writer');
        $this->assertFalse($review['usable']);
        $this->assertStringContainsString('after the event', $review['reason']);

        $language = $tools->firstWhere('key', 'translator');
        $this->assertFalse($language['usable']);

        // And the ones that do carry data are offered.
        $this->assertTrue($tools->firstWhere('key', 'budget-allocator')['usable']);
        $this->assertTrue($tools->firstWhere('key', 'timeline-builder')['usable']);
    }

    public function test_a_non_source_tool_cannot_be_selected_through_the_url(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.toolkit.plan', ['tool' => 'vendor-matchmaking']))
            ->assertOk()
            ->assertViewHas('selectedTool', null);
    }

    // ── A signed agreement is a contract, not a form ──────────────

    public function test_an_accepted_agreement_is_listed_but_blocked_with_the_reason(): void
    {
        $this->agreement('fully_accepted');

        $rows = ToolkitBridge::agreementsFor($this->client);

        $this->assertCount(1, $rows, 'The agreement should still be visible.');
        $this->assertFalse($rows->first()['eligible']);
        $this->assertStringContainsString('change-and-approval', $rows->first()['reason']);
    }

    public function test_posting_to_an_accepted_agreement_is_refused(): void
    {
        $agreement = $this->agreement('fully_accepted');
        $artifact  = $this->artifact();

        $this->actingAs($this->client)
            ->post(route('client.toolkit.plan.store'), [
                'result'      => $artifact->id,
                'destination' => 'agreement:' . $agreement->id,
                'link_mode'   => 'copy',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, ToolkitAttachment::count());
    }

    /**
     * The rule that made this a separate table: taking data off a request must
     * not destroy the client's saved work.
     */
    public function test_removing_placed_data_leaves_the_saved_result_alone(): void
    {
        $artifact = $this->artifact();
        $event    = $this->event('Live request');

        $attachment = ToolkitBridge::attach($this->client, $artifact, $event);

        $this->actingAs($this->client)
            ->delete(route('client.toolkit.placed.destroy', $attachment))
            ->assertRedirect();

        $this->assertSame(0, ToolkitAttachment::count());
        $this->assertDatabaseHas('event_ai_artifacts', ['id' => $artifact->id]);
    }

    // ── Copy versus linked ───────────────────────────────────────

    public function test_a_copy_does_not_move_when_the_source_does(): void
    {
        $artifact   = $this->artifact();
        $attachment = ToolkitBridge::attach($this->client, $artifact, $this->event('R'), ToolkitAttachment::COPY);

        $artifact->update(['payload' => ['total' => 9999]]);
        ToolkitBridge::markMovedSources($this->client);

        $attachment->refresh();
        $this->assertSame(4200, $attachment->payload['total']);
        $this->assertFalse($attachment->needs_review);
    }

    /**
     * Linked data is flagged, never rewritten. It can be sitting inside an
     * agreement somebody is reading.
     */
    public function test_linked_data_is_flagged_for_review_and_not_rewritten(): void
    {
        $artifact   = $this->artifact();
        $attachment = ToolkitBridge::attach($this->client, $artifact, $this->event('R'), ToolkitAttachment::LINKED);

        $artifact->update(['payload' => ['total' => 9999]]);
        $flagged = ToolkitBridge::markMovedSources($this->client);

        $attachment->refresh();
        $this->assertSame(1, $flagged);
        $this->assertTrue($attachment->needs_review);
        $this->assertSame(4200, $attachment->payload['total'], 'The placed figure must not change on its own.');
    }

    public function test_the_client_can_take_the_new_version_or_keep_the_old(): void
    {
        $artifact = $this->artifact();
        $take     = ToolkitBridge::attach($this->client, $artifact, $this->event('A'), ToolkitAttachment::LINKED);
        $keep     = ToolkitBridge::attach($this->client, $artifact, $this->event('B'), ToolkitAttachment::LINKED);

        $artifact->update(['payload' => ['total' => 9999]]);
        ToolkitBridge::markMovedSources($this->client);

        $this->actingAs($this->client)->post(route('client.toolkit.placed.apply', $take));
        $this->actingAs($this->client)->post(route('client.toolkit.placed.keep', $keep));

        $this->assertSame(9999, $take->refresh()->payload['total']);
        $this->assertFalse($take->needs_review);

        $this->assertSame(4200, $keep->refresh()->payload['total']);
        $this->assertFalse($keep->needs_review);
        $this->assertSame(ToolkitAttachment::COPY, $keep->link_mode, 'Keeping it should stop it following the source.');
    }

    /** Re-saving the same figures in a different order is not a change. */
    public function test_reordering_the_same_result_is_not_treated_as_a_change(): void
    {
        $artifact   = $this->artifact('budget-allocator', ['venue' => 2000, 'food' => 1200]);
        $attachment = ToolkitBridge::attach($this->client, $artifact, $this->event('R'), ToolkitAttachment::LINKED);

        $artifact->update(['payload' => ['food' => 1200, 'venue' => 2000]]);

        $this->assertSame(0, ToolkitBridge::markMovedSources($this->client));
        $this->assertFalse($attachment->refresh()->needs_review);
    }

    /**
     * The back door: data linked into a DRAFT agreement, both sides then
     * accept it, and the source moves afterwards. Taking "the new version"
     * would rewrite a figure inside a signed contract -- which is precisely
     * what refusing accepted agreements as destinations stops at the front.
     */
    public function test_a_linked_update_cannot_rewrite_an_agreement_signed_since(): void
    {
        $agreement  = $this->agreement('draft');
        $artifact   = $this->artifact();
        $attachment = ToolkitBridge::attach($this->client, $artifact, $agreement, ToolkitAttachment::LINKED);

        // Both sides accept it, and only then does the source move.
        $agreement->update(['status' => 'fully_accepted']);
        $artifact->update(['payload' => ['total' => 9999]]);
        ToolkitBridge::markMovedSources($this->client);

        $this->actingAs($this->client)
            ->post(route('client.toolkit.placed.apply', $attachment))
            ->assertSessionHas('error');

        $this->assertSame(4200, $attachment->refresh()->payload['total'],
            'A signed agreement must not be edited through the review prompt.');
    }

    /** The same door, on a request that has since closed. */
    public function test_a_linked_update_cannot_rewrite_a_closed_request(): void
    {
        $event      = $this->event('Closing soon');
        $artifact   = $this->artifact();
        $attachment = ToolkitBridge::attach($this->client, $artifact, $event, ToolkitAttachment::LINKED);

        $event->update(['closed_at' => now()]);
        $artifact->update(['payload' => ['total' => 9999]]);
        ToolkitBridge::markMovedSources($this->client);

        $this->actingAs($this->client)
            ->post(route('client.toolkit.placed.apply', $attachment))
            ->assertSessionHas('error');

        $this->assertSame(4200, $attachment->refresh()->payload['total']);
    }

    // ── Whose data, whose request ────────────────────────────────

    public function test_somebody_elses_saved_result_cannot_be_placed(): void
    {
        $other = User::factory()->create();
        $theirs = EventAiArtifact::create([
            'event_id'  => $this->event('X')->id,
            'user_id'   => $other->id,
            'tool_key'  => 'budget-allocator',
            'tool_name' => 'Budget Planner',
            'title'     => 'Their budget',
            'payload'   => ['total' => 1],
            'mode'      => 'manual',
        ]);

        $this->actingAs($this->client)
            ->post(route('client.toolkit.plan.store'), [
                'result'      => $theirs->id,
                'destination' => 'request:' . $this->event('Mine')->id,
                'link_mode'   => 'copy',
            ])
            ->assertForbidden();
    }

    public function test_a_request_that_is_not_yours_is_not_a_destination(): void
    {
        $stranger = User::factory()->create();
        $theirs = Event::create([
            'title' => 'Not yours', 'created_by' => $stranger->id, 'client_id' => $stranger->id,
            'status' => 'pending', 'starts_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->client)
            ->post(route('client.toolkit.plan.store'), [
                'result'      => $this->artifact()->id,
                'destination' => 'request:' . $theirs->id,
                'link_mode'   => 'copy',
            ])
            ->assertForbidden();
    }

    /**
     * One agreement per professional. The guest count that is true for the
     * caterer is not something the DJ should find in their contract.
     */
    public function test_placing_on_one_agreement_does_not_touch_another(): void
    {
        $caterer = $this->agreement('draft');
        $dj      = $this->agreement('draft');

        ToolkitBridge::attach($this->client, $this->artifact(), $caterer);

        $this->assertCount(1, ToolkitBridge::attachmentsOn($caterer));
        $this->assertCount(0, ToolkitBridge::attachmentsOn($dj));
    }

    public function test_the_same_result_is_not_placed_twice_on_one_destination(): void
    {
        $artifact = $this->artifact();
        $event    = $this->event('R');

        $this->assertNotNull(ToolkitBridge::attach($this->client, $artifact, $event));
        $this->assertNull(ToolkitBridge::attach($this->client, $artifact, $event));
        $this->assertSame(1, ToolkitAttachment::count());
    }

    // ── Placed data has to be visible where it was placed ────────

    /**
     * The point of putting a budget into a professional's agreement is that
     * the professional reads it. Data that lands somewhere nothing renders has
     * not gone anywhere.
     */
    public function test_the_professional_sees_what_the_client_attached_to_their_agreement(): void
    {
        $pro       = $this->professional();
        $agreement = $this->agreement('draft', $pro);

        ToolkitBridge::attach($this->client, $this->artifact(), $agreement);

        $this->actingAs($pro)
            ->get(route('app.agreements.show', $agreement))
            ->assertOk()
            ->assertSee('Wedding budget')
            ->assertSee('From Budget Planner', false);
    }

    /** And it is not dressed up as something they signed. */
    public function test_attached_data_is_marked_as_not_part_of_the_agreement(): void
    {
        $agreement = $this->agreement('draft');
        ToolkitBridge::attach($this->client, $this->artifact(), $agreement);

        $this->actingAs($this->client)
            ->get(route('app.agreements.show', $agreement))
            ->assertOk()
            ->assertSee('not part of the agreement text', false);
    }

    /** The professional may read it. They may not take it off. */
    public function test_the_professional_cannot_remove_what_the_client_attached(): void
    {
        $pro        = $this->professional();
        $agreement  = $this->agreement('draft', $pro);
        $attachment = ToolkitBridge::attach($this->client, $this->artifact(), $agreement);

        $this->actingAs($pro)
            ->delete(route('client.toolkit.placed.destroy', $attachment))
            ->assertForbidden();

        $this->assertSame(1, ToolkitAttachment::count());

        // And they are not offered the button either. Asserted on the form's
        // action, not the CSS class -- the stylesheet names the class whether
        // or not anything is wearing it.
        $this->actingAs($pro)
            ->get(route('app.agreements.show', $agreement))
            ->assertOk()
            ->assertDontSee(route('client.toolkit.placed.destroy', $attachment), false);

        // The client, on the same page, is.
        $this->actingAs($this->client)
            ->get(route('app.agreements.show', $agreement))
            ->assertOk()
            ->assertSee(route('client.toolkit.placed.destroy', $attachment), false);
    }

    public function test_a_request_shows_what_was_attached_to_it(): void
    {
        $event = $this->event('Live request');
        ToolkitBridge::attach($this->client, $this->artifact(), $event);

        $this->actingAs($this->client)
            ->get(route('client.events.show', $event))
            ->assertOk()
            ->assertSee('Attached from your toolkit', false)
            ->assertSee('Wedding budget');
    }

    // ── The screen ───────────────────────────────────────────────

    public function test_the_screen_states_that_the_toolkit_is_not_yet_on_sale(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.toolkit.plan'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('not on sale yet', $html);

        // The mockup's tier picker would let a client choose a tier they never
        // bought. It must not be here.
        $this->assertStringNotContainsString('Select the Client', $html);
    }

    public function test_placed_data_is_labelled_with_its_tool_and_time(): void
    {
        $event = $this->event('Live request');
        ToolkitBridge::attach($this->client, $this->artifact(), $event);

        $this->actingAs($this->client)
            ->get(route('client.toolkit.plan', ['to' => 'request:' . $event->id]))
            ->assertOk()
            ->assertSee('From Budget Planner', false);
    }
}
