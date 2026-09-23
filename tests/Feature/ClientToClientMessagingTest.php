<?php

namespace Tests\Feature;

use App\Domain\Messaging\MessagingPairs;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Client to Client: Not allowed" (Sir Peter's Client Messaging Rules,
 * 23 September 2026), and the rule the same page sets for how it is done:
 *
 *   "The restriction must be enforced by the backend, not merely hidden in
 *   the interface. A Client-to-Client conversation must not be reachable
 *   through Recent, Unread, Favorites, search, direct URL, API lookup, or
 *   manually entered user ID."
 *
 * So each way in gets its own test. A conversation that is only missing from
 * a list would still open for anyone who kept the link.
 */
class ClientToClientMessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $otherClient;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->makeUser('client');
        $this->otherClient = $this->makeUser('client');
        $this->pro = $this->makeUser('professional');
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $user;
    }

    private function conversationBetween(User $a, User $b): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $a->id]);
        $c->addParticipant($a);
        $c->addParticipant($b);
        Message::create(['conversation_id' => $c->id, 'sender_id' => $b->id, 'body' => 'Hello there']);

        return $c;
    }

    public function test_the_table_says_who_may_talk(): void
    {
        $this->assertFalse(MessagingPairs::allowed('client', 'client'));
        $this->assertTrue(MessagingPairs::allowed('client', 'professional'));
        $this->assertTrue(MessagingPairs::allowed('client', 'influencer'));
        $this->assertTrue(MessagingPairs::allowed('client', 'admin'));
        // An admin reaches everyone, including the pairs barred to each other.
        $this->assertTrue(MessagingPairs::allowed('admin', 'client'));
        $this->assertTrue(MessagingPairs::allowed('admin', 'professional'));
    }

    public function test_a_client_to_client_conversation_cannot_be_opened_by_url(): void
    {
        $conversation = $this->conversationBetween($this->client, $this->otherClient);

        $this->actingAs($this->client)
            ->getJson(route('conversations.show', $conversation))
            ->assertForbidden();
    }

    public function test_a_client_cannot_send_into_one(): void
    {
        $conversation = $this->conversationBetween($this->client, $this->otherClient);

        $this->actingAs($this->client)
            ->postJson(route('conversations.messages.store', $conversation), ['body' => 'Hi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('messages', ['conversation_id' => $conversation->id, 'body' => 'Hi']);
    }

    public function test_a_client_cannot_start_one_with_a_typed_user_id(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('conversations.store'), [
                'type' => 'direct',
                'participant_ids' => [$this->otherClient->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, Conversation::count());
    }

    public function test_it_is_not_in_the_api_list(): void
    {
        $barred = $this->conversationBetween($this->client, $this->otherClient);
        $allowed = $this->conversationBetween($this->client, $this->pro);

        $ids = collect($this->actingAs($this->client)->getJson(route('conversations.index'))->json('data'))
            ->pluck('id')->all();

        $this->assertContains($allowed->id, $ids);
        $this->assertNotContains($barred->id, $ids);
    }

    public function test_it_is_not_on_the_messages_page_and_the_other_client_cannot_be_picked(): void
    {
        $this->conversationBetween($this->client, $this->otherClient);
        $this->conversationBetween($this->client, $this->pro);

        $response = $this->actingAs($this->client)->get(route('client.chat.index'))->assertOk();

        $response->assertDontSee($this->otherClient->name, false);
        $response->assertSee($this->pro->name, false);

        // The New Message picker offers nobody the rules bar.
        $recipients = collect($response->viewData('recipients'))->pluck('id')->all();
        $this->assertNotContains($this->otherClient->id, $recipients);
        $this->assertContains($this->pro->id, $recipients);
    }

    public function test_a_professional_pair_is_left_alone_until_that_side_is_built(): void
    {
        $otherPro = $this->makeUser('professional');
        $conversation = $this->conversationBetween($this->pro, $otherPro);

        // The table already says no; only the client's side is switched on,
        // so nothing on the professional side changes behaviour yet.
        $this->assertFalse(MessagingPairs::allowed('professional', 'professional'));
        $this->assertSame(['client'], MessagingPairs::ENFORCED);
        $this->assertFalse(MessagingPairs::blocks($this->pro, $conversation->load('participants')));
    }
}
