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
 * The chat's details column, after the Freelancer chat Sir Peter sent on
 * 2026-09-10: photo, name, the job with an Award button, chat options, a
 * collapse to a rail, and a thumbs-up in the composer.
 */
class ChatSidePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Event $event;
    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['name' => 'Dana Client']);
        $this->client->assignRole('client');
        $this->pro = User::factory()->create(['name' => 'Adison Pro']);
        $this->pro->assignRole('professional');

        $this->event = Event::create([
            'title' => 'Web Designer Needed', 'status' => 'published', 'is_published' => true,
            'published_at' => now()->subDay(), 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'starts_at' => now()->addMonth(),
        ]);

        $this->conv = Conversation::create(['type' => 'event', 'event_id' => $this->event->id, 'created_by' => $this->client->id]);
        $this->conv->participants()->attach([
            $this->client->id => ['joined_at' => now()],
            $this->pro->id    => ['joined_at' => now()],
        ]);
    }

    private function page(): string
    {
        return $this->actingAs($this->client->fresh())->get(route('client.chat.show', $this->conv))->assertOk()->getContent();
    }

    private function side(string $html): string
    {
        $a = strpos($html, 'class="cm-card cm-side"');
        $this->assertNotFalse($a, 'The details column is not on the page.');

        return substr($html, $a, strpos($html, '</aside>', $a) - $a);
    }

    private function bid(string $status = 'submitted'): Bid
    {
        return Bid::create(['event_id' => $this->event->id, 'supplier_id' => $this->pro->id, 'amount' => 1200, 'status' => $status, 'submitted_at' => now()]);
    }

    public function test_the_column_shows_the_person_and_the_job(): void
    {
        $side = $this->side($this->page());

        $this->assertStringContainsString('<img src="', $side);
        $this->assertStringContainsString('Adison Pro', $side);
        $this->assertStringContainsString('Web Designer Needed', $side);
        $this->assertStringContainsString('Posted 1 day ago', $side);
    }

    /** Nothing records whether someone is online, so the page does not say they are. */
    public function test_it_does_not_claim_anyone_is_online(): void
    {
        $this->assertStringNotContainsString('Active now', $this->page());
    }

    /** Award goes through finalization, like the Compare page, never the direct accept. */
    public function test_award_starts_finalization_for_their_proposal(): void
    {
        $bid = $this->bid();
        $side = $this->side($this->page());

        $this->assertStringContainsString('action="' . route('client.finalize.start', $bid) . '"', $side);
        $this->assertStringContainsString('>Award<', $side);
        $this->assertStringContainsString('Their proposal: $1,200.00', $side);
        $this->assertStringNotContainsString(route('client.proposals.accept', $bid), $side);
    }

    public function test_no_proposal_means_no_award_button(): void
    {
        $side = $this->side($this->page());

        $this->assertStringNotContainsString('class="cm-award"', $side);
        $this->assertStringContainsString('has not sent a proposal for this event yet', $side);
    }

    public function test_once_booked_it_says_awarded(): void
    {
        $this->bid('won');
        Booking::create(['event_id' => $this->event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 1200]);

        $side = $this->side($this->page());

        $this->assertStringContainsString('Awarded', $side);
        $this->assertStringNotContainsString('class="cm-award"', $side);
    }

    /** Archiving is yours alone; the other side keeps the conversation where it was. */
    public function test_archive_is_per_person_and_toggles(): void
    {
        $this->actingAs($this->client)->post(route('conversations.archive', $this->conv))->assertRedirect();

        $pivot = fn (User $u) => $this->conv->participants()->where('users.id', $u->id)->first()->pivot->archived_at;
        $this->assertNotNull($pivot($this->client));
        $this->assertNull($pivot($this->pro));

        $html = $this->page();
        $this->assertStringContainsString('data-archived="1"', $html);
        $this->assertMatchesRegularExpression('/data-tab="archived">Archived <span class="ct">1<\/span>/', $html);
        $this->assertStringContainsString('Unarchive', $this->side($html));

        $this->actingAs($this->client)->post(route('conversations.archive', $this->conv));
        $this->assertNull($pivot($this->client));
    }

    public function test_someone_outside_the_conversation_cannot_archive_it(): void
    {
        $stranger = User::factory()->create();
        $stranger->assignRole('client');

        $this->actingAs($stranger)->post(route('conversations.archive', $this->conv))->assertForbidden();
    }

    public function test_the_composer_has_a_thumbs_up_and_the_panel_collapses(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('id="cm-thumbs"', $html);
        $this->assertStringContainsString('data-side-toggle', $html);
        $this->assertStringContainsString('class="cm-main has-side"', $html);
    }

    /** Part 2 added Mute and Block beside Archive (ChatMuteBlockTest). */
    public function test_chat_options_offer_mute_block_and_archive(): void
    {
        $side = $this->side($this->page());

        $this->assertStringContainsString('Chat options', $side);
        foreach (['Mute', 'Block', 'Archive'] as $option) {
            $this->assertMatchesRegularExpression('/<\/svg>\s*' . $option . '\s*<\/button>/', $side, "{$option} is missing.");
        }
    }
}
