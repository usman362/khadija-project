<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-114 / OA-115 / OA-140 — one event, one stage.
 *
 * An event carries a `status` and an `is_published` flag, and they answer
 * overlapping questions. The badge read the status; the row actions read the
 * flag. So a row whose badge said "Published" offered "Continue Draft" and
 * "Publish", and a Confirmed event offered the wizard — you cannot go back and
 * finish writing a request professionals have already answered.
 *
 * Status wins, and the assertions below are the combinations that were on the
 * screen: published-with-the-flag-unset, confirmed-with-the-flag-unset.
 */
class EventStageIsOneAnswerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    private function event(string $status, bool $flag): Event
    {
        return Event::create([
            'title' => "A {$status} request",
            'client_id' => $this->client->id,
            'created_by' => $this->client->id,
            'status' => $status,
            'is_published' => $flag,
        ]);
    }

    /* ── The stage itself ───────────────────────────────────── */

    public function test_a_pending_request_never_sent_is_a_draft(): void
    {
        $this->assertSame('draft', $this->event('pending', false)->stage());
    }

    /** The contradiction that was live: published, flag unset. */
    public function test_a_published_request_is_not_a_draft_whatever_the_flag_says(): void
    {
        $e = $this->event('published', false);

        $this->assertSame('open', $e->stage());
        $this->assertFalse($e->isDraft());
    }

    /** And the one from the ticket: Confirmed offering Continue Draft. */
    public function test_a_confirmed_request_is_not_a_draft(): void
    {
        $this->assertSame('confirmed', $this->event('confirmed', false)->stage());
        $this->assertFalse($this->event('confirmed', false)->isDraft());
    }

    public function test_completed_and_cancelled_are_their_own_stages(): void
    {
        $this->assertSame('completed', $this->event('completed', false)->stage());
        $this->assertSame('cancelled', $this->event('cancelled', true)->stage());
    }

    /* ── What the client is offered ─────────────────────────── */

    public function test_a_confirmed_event_is_not_offered_continue_draft(): void
    {
        $this->event('confirmed', false);

        $html = $this->actingAs($this->client)->get('/client/events')->assertOk()->getContent();

        $this->assertStringNotContainsString('Continue Draft', $html);
    }

    /** A real draft keeps the one action that moves it forward. */
    public function test_a_real_draft_is_still_offered_continue_draft(): void
    {
        $this->event('pending', false);

        $this->actingAs($this->client)->get('/client/events')
            ->assertOk()
            ->assertSee('Continue Draft');
    }

    public function test_a_confirmed_event_detail_page_offers_no_publish_button(): void
    {
        $event = $this->event('confirmed', false);

        $html = $this->actingAs($this->client)
            ->get("/client/events/{$event->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('Publish Request', $html);
    }

    /* ── The data ───────────────────────────────────────────── */

    public function test_no_event_says_published_and_not_published_at_once(): void
    {
        $this->event('published', true);
        $this->event('pending', false);

        $this->assertSame(0, Event::statusContradictsFlag()->count());
    }
}
