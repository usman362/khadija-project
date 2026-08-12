<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAiArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist row 194 — "Add Tool Data" on an open request.
 *
 * The toolkit bridge only ever ran one way: a tool pushed its result onto an
 * event. A client looking at an open request had no way to reach for
 * something they had already worked out, and had to go back and run the tool
 * again. This is the pull.
 */
class AddToolDataToRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account();
    }

    private function account(): User
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function event(?User $owner = null, string $title = 'An event'): Event
    {
        $owner ??= $this->client;

        return Event::create([
            'title' => $title, 'client_id' => $owner->id, 'created_by' => $owner->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(30),
        ]);
    }

    private function artifact(Event $event, ?User $owner = null): EventAiArtifact
    {
        return EventAiArtifact::create([
            'event_id'  => $event->id,
            'user_id'   => ($owner ?? $this->client)->id,
            'tool_key'  => 'budget-allocator',
            'tool_name' => 'Budget Allocator',
            'title'     => 'Wedding budget split',
            'payload'   => ['total' => 18000],
            'mode'      => 'manual',
        ]);
    }

    public function test_the_request_page_offers_your_results_from_other_events(): void
    {
        $other = $this->event(title: 'Last year');
        $this->artifact($other);

        $current = $this->event(title: 'This year');

        $page = $this->actingAs($this->client)->get(route('client.events.show', $current));

        $page->assertOk();
        $page->assertSee('Add tool data from your other events', false);
        $page->assertSee('Wedding budget split', false);
    }

    /** A copy, not a move — the same budget can inform two requests. */
    public function test_adding_a_result_copies_it_and_leaves_the_original(): void
    {
        $other    = $this->event(title: 'Last year');
        $artifact = $this->artifact($other);
        $current  = $this->event(title: 'This year');

        $this->actingAs($this->client)
            ->post(route('client.ai-artifacts.copy', [$current, $artifact]))
            ->assertRedirect();

        $this->assertDatabaseCount('event_ai_artifacts', 2);
        $this->assertDatabaseHas('event_ai_artifacts', ['event_id' => $other->id, 'id' => $artifact->id]);
        $this->assertDatabaseHas('event_ai_artifacts', ['event_id' => $current->id, 'title' => 'Wedding budget split']);
    }

    /**
     * Pulled in by hand, so it is marked manual — not auto-attached. The row
     * is explicit that data added this way is a normal editable field, never
     * locked or authoritative.
     */
    public function test_a_pulled_result_is_a_normal_editable_field(): void
    {
        $other    = $this->event(title: 'Last year');
        $artifact = $this->artifact($other);
        $current  = $this->event(title: 'This year');

        $this->actingAs($this->client)->post(route('client.ai-artifacts.copy', [$current, $artifact]));

        $copy = EventAiArtifact::where('event_id', $current->id)->firstOrFail();

        $this->assertSame('manual', $copy->mode);

        // And removable like anything else on the request.
        $this->actingAs($this->client)
            ->delete(route('client.ai-artifacts.destroy', $copy))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_ai_artifacts', ['id' => $copy->id]);
    }

    public function test_the_same_result_is_not_added_twice(): void
    {
        $other    = $this->event(title: 'Last year');
        $artifact = $this->artifact($other);
        $current  = $this->event(title: 'This year');

        $this->actingAs($this->client)->post(route('client.ai-artifacts.copy', [$current, $artifact]));
        $this->actingAs($this->client)->post(route('client.ai-artifacts.copy', [$current, $artifact]));

        $this->assertSame(1, EventAiArtifact::where('event_id', $current->id)->count());
    }

    /** Somebody else's budget is not a library to browse. */
    public function test_you_cannot_pull_in_someone_elses_result(): void
    {
        $stranger = $this->account();
        $theirs   = $this->artifact($this->event($stranger, 'Their event'), $stranger);

        $mine = $this->event(title: 'My event');

        $this->actingAs($this->client)
            ->post(route('client.ai-artifacts.copy', [$mine, $theirs]))
            ->assertForbidden();
    }

    public function test_you_cannot_pull_into_someone_elses_request(): void
    {
        $other    = $this->event(title: 'Mine');
        $artifact = $this->artifact($other);

        $stranger  = $this->account();
        $theirs    = $this->event($stranger, 'Theirs');

        $this->actingAs($this->client)
            ->post(route('client.ai-artifacts.copy', [$theirs, $artifact]))
            ->assertForbidden();
    }

    /** Nothing to offer means no control, rather than an empty panel. */
    public function test_a_client_with_no_saved_results_sees_no_control(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.events.show', $this->event()))
            ->assertDontSee('Add tool data from your other events', false);
    }
}
