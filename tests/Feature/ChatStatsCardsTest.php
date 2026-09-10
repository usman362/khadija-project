<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The cards above the inbox. Ali, 2026-09-11: remove Priority and Compliance,
 * make the rest work, and add what belongs there.
 */
class ChatStatsCardsTest extends TestCase
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
        $this->client = $this->client->fresh();
    }

    private function pro(): User
    {
        $p = User::factory()->create();
        $p->assignRole('professional');

        return $p;
    }

    private function chat(User $pro, array $pivot = []): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        // Two attaches: one insert of rows with different columns is refused.
        $c->participants()->attach($this->client->id, ['joined_at' => now()] + $pivot);
        $c->participants()->attach($pro->id, ['joined_at' => now()]);

        return $c;
    }

    private function say(Conversation $c, User $from, string $body = 'Hi', $at = null): void
    {
        $m = Message::create(['conversation_id' => $c->id, 'sender_id' => $from->id, 'body' => $body, 'source' => 'user']);
        if ($at) {
            $m->forceFill(['created_at' => $at])->saveQuietly();
        }
    }

    private function event(?User $owner = null): Event
    {
        $owner ??= $this->client;

        return Event::create(['title' => 'Party', 'status' => 'published', 'is_published' => true,
            'client_id' => $owner->id, 'created_by' => $owner->id, 'starts_at' => now()->addMonth()]);
    }

    private function cards(): string
    {
        $html = $this->actingAs($this->client)->get(route('client.chat.index'))->assertOk()->getContent();
        $a = strpos($html, '<div class="cm-stats">');

        return substr($html, $a, strpos($html, '<div class="cm-actions">', $a) - $a);
    }

    private function card(string $label, string $cards): string
    {
        $a = strpos($cards, '</span>' . $label . '</div>');
        $this->assertNotFalse($a, "No {$label} card.");

        return substr($cards, $a, 400);
    }

    public function test_priority_compliance_and_the_invented_comparison_are_gone(): void
    {
        $cards = $this->cards();

        foreach (['Priority', 'Compliance', 'vs last 30 days', 'Payment Secured'] as $gone) {
            $this->assertStringNotContainsString($gone, $cards);
        }
    }

    /** Conversations, not messages; muted and archived left out. */
    public function test_unread_counts_conversations_in_the_inbox(): void
    {
        $a = $this->pro();
        $c1 = $this->chat($a);
        foreach (range(1, 3) as $i) { $this->say($c1, $a, "msg {$i}"); }
        $b = $this->pro(); $this->say($this->chat($b, ['muted_at' => now()]), $b);
        $d = $this->pro(); $this->say($this->chat($d, ['archived_at' => now()]), $d);

        $card = $this->card('Unread', $this->cards());

        $this->assertStringContainsString('<div class="v">1</div>', $card);
        $this->assertStringContainsString('conversation of 2 in your inbox', $card);
    }

    public function test_awaiting_counts_chats_where_their_message_is_last(): void
    {
        $a = $this->pro(); $c1 = $this->chat($a); $this->say($c1, $a, 'Question?');
        // Both in the same second on purpose: "last" must still be the reply,
        // not whichever row the database returns first.
        $b = $this->pro(); $c2 = $this->chat($b); $this->say($c2, $b, 'Hi'); $this->say($c2, $this->client, 'Answered');

        $this->assertStringContainsString('<div class="v">1</div>', $this->card('Awaiting your reply', $this->cards()));

        $html = $this->actingAs($this->client)->get(route('client.chat.index'))->getContent();
        $this->assertStringContainsString('data-awaiting="1"', $html);
        $this->assertStringContainsString('data-awaiting="0"', $html);
    }

    /** Open proposals on this client's events, from people they are talking to, and nobody else. */
    public function test_open_proposals_come_from_chat_partners_on_your_events(): void
    {
        $partner = $this->pro(); $this->chat($partner);
        $stranger = $this->pro();
        $mine = $this->event();

        Bid::create(['event_id' => $mine->id, 'supplier_id' => $partner->id, 'amount' => 500, 'status' => 'submitted']);
        Bid::create(['event_id' => $mine->id, 'supplier_id' => $stranger->id, 'amount' => 450, 'status' => 'submitted']);
        Bid::create(['event_id' => $this->event(User::factory()->create())->id, 'supplier_id' => $partner->id, 'amount' => 700, 'status' => 'submitted']);

        $this->assertStringContainsString('<div class="v">1</div>', $this->card('Open proposals', $this->cards()));
    }

    /** 926.3h read like a typo; past two days it is days. */
    public function test_a_long_reply_time_is_said_in_days(): void
    {
        $a = $this->pro(); $c = $this->chat($a);
        $this->say($c, $a, 'Hello', now()->subDays(5));
        $this->say($c, $this->client, 'Sorry for the wait', now()->subDays(2));

        $this->assertStringContainsString('<div class="v">3 days</div>', $this->card('Your reply time', $this->cards()));
    }

    /** Not "secured": nothing is held. Agreed, not yet paid, counted in bookings. */
    public function test_agreed_not_yet_paid_is_honest_about_what_it_is(): void
    {
        $p = $this->pro();
        Booking::create(['event_id' => $this->event()->id, 'client_id' => $this->client->id, 'supplier_id' => $p->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 400]);

        $card = $this->card('Due', $this->cards());

        $this->assertStringContainsString('$400', $card);
        $this->assertStringContainsString('Across 1 booking<', $card);
    }

    public function test_the_unread_and_awaiting_cards_narrow_the_list(): void
    {
        $cards = $this->cards();

        $this->assertStringContainsString('data-card-filter="unread"', $cards);
        $this->assertStringContainsString('data-card-filter="awaiting"', $cards);
        $this->assertStringContainsString(route('client.proposals.index'), $cards);
        $this->assertStringContainsString(route('client.bookings.index'), $cards);
    }
}
