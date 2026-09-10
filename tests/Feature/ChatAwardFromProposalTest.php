<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Award shows in the chat even when the chat is not linked to an event.
 *
 * Ali, 2026-09-11: "chat me kahin show nhi horaha hai award karne ka". Sending
 * a proposal does not open a conversation, so client and professional talk in
 * a plain direct chat with no event on it, and the job card only read the
 * event from the conversation. It now falls back to their proposals on this
 * client's own events.
 */
class ChatAwardFromProposalTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['name' => 'Dana Client']);
        $this->client->assignRole('client');
        $this->client = $this->client->fresh();
        $this->pro = User::factory()->create(['name' => 'Adison Pro']);
        $this->pro->assignRole('professional');

        // The ordinary case: a direct chat, started from "Message", no event.
        $this->conv = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $this->conv->participants()->attach([
            $this->client->id => ['joined_at' => now()],
            $this->pro->id    => ['joined_at' => now()],
        ]);
    }

    private function event(string $title, ?User $owner = null): Event
    {
        $owner ??= $this->client;

        return Event::create(['title' => $title, 'status' => 'published', 'is_published' => true, 'published_at' => now()->subDays(2),
            'client_id' => $owner->id, 'created_by' => $owner->id, 'starts_at' => now()->addMonth()]);
    }

    private function bid(Event $e, string $status = 'submitted', int $amount = 900): Bid
    {
        return Bid::create(['event_id' => $e->id, 'supplier_id' => $this->pro->id, 'amount' => $amount, 'status' => $status]);
    }

    private function side(): string
    {
        $html = $this->actingAs($this->client)->get(route('client.chat.show', $this->conv))->assertOk()->getContent();
        $a = strpos($html, 'class="cm-card cm-side"');
        $this->assertNotFalse($a);

        return substr($html, $a, strpos($html, '</aside>', $a) - $a);
    }

    public function test_a_direct_chat_shows_their_proposal_and_award(): void
    {
        $bid = $this->bid($this->event('Garden Wedding'));

        $side = $this->side();

        $this->assertStringContainsString('Their proposal on your event', $side);
        $this->assertStringContainsString('Garden Wedding', $side);
        $this->assertStringContainsString('action="' . route('client.finalize.start', $bid) . '"', $side);
        $this->assertStringContainsString('>Award<', $side);
    }

    public function test_the_newest_open_proposal_is_shown_and_the_rest_are_counted(): void
    {
        $this->bid($this->event('Older Gala'));
        $newest = $this->bid($this->event('Newest Brunch'));

        $side = $this->side();

        $this->assertStringContainsString('Newest Brunch', $side);
        $this->assertStringContainsString(route('client.finalize.start', $newest), $side);
        $this->assertStringContainsString('1 more proposal from them', $side);
    }

    /** An open proposal wins over a newer declined one. */
    public function test_an_open_proposal_comes_before_a_closed_one(): void
    {
        $open = $this->bid($this->event('Still Open'));
        $this->bid($this->event('Declined Later'), 'declined');

        $side = $this->side();

        $this->assertStringContainsString('Still Open', $side);
        $this->assertStringContainsString(route('client.finalize.start', $open), $side);
    }

    public function test_an_award_already_made_still_reads_as_awarded(): void
    {
        $e = $this->event('Booked Party');
        $this->bid($e, 'won');
        Booking::create(['event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 900]);

        $side = $this->side();

        $this->assertStringContainsString('Booked Party', $side);
        $this->assertStringContainsString('Awarded', $side);
        $this->assertStringNotContainsString('class="cm-award"', $side);
    }

    /** Their proposal on somebody else's event is none of this client's business. */
    public function test_another_clients_event_is_never_shown(): void
    {
        $other = User::factory()->create();
        $this->bid($this->event('Not Yours', $other));

        $side = $this->side();

        $this->assertStringNotContainsString('Not Yours', $side);
        $this->assertStringNotContainsString('class="cm-award"', $side);
    }

    public function test_with_no_proposals_there_is_no_job_card(): void
    {
        $side = $this->side();

        $this->assertStringNotContainsString('class="cm-side-job"', $side);
        $this->assertStringNotContainsString('class="cm-award"', $side);
    }

    /** A chat that names its event keeps that event, and no "from their proposal" caption. */
    public function test_a_chat_linked_to_an_event_keeps_it(): void
    {
        $linked = $this->event('Linked Event');
        $this->conv->update(['type' => 'event', 'event_id' => $linked->id]);
        $this->bid($this->event('Some Other Event'));
        $this->bid($linked);

        $side = $this->side();

        $this->assertStringContainsString('Linked Event', $side);
        $this->assertStringNotContainsString('Their proposal on your event', $side);
    }
}
