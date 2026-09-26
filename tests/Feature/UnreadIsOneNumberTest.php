<?php

namespace Tests\Feature;

use App\Domain\Messaging\Unread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The unread badge beside "Messages (Inbox)" and the list it opens are the
 * same fact, so they have to be the same number.
 *
 * They were counted differently. The sidebar counted rows addressed by
 * recipient_id, which is null in a group conversation; the dock and the
 * Messages page counted messages in the user's conversations that somebody
 * else sent and they have not read. And the sidebar knew nothing about the
 * conversations a client is not allowed to open, so it could hold a number
 * that nothing the client did would ever clear.
 */
class UnreadIsOneNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private User $otherClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->make('client', 'Dana Whitfield');
        $this->pro = $this->make('professional', 'Priya Raghavan');
        $this->otherClient = $this->make('client', 'Marcus Lee');
    }

    private function make(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function conversation(User $with, string $body): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $with->id]);
        $c->addParticipant($this->client);
        $c->addParticipant($with);

        Message::create([
            'conversation_id' => $c->id, 'sender_id' => $with->id, 'body' => $body,
        ]);

        return $c;
    }

    public function test_it_counts_what_is_waiting_in_a_conversation(): void
    {
        $this->conversation($this->pro, 'Are you free on the 12th?');

        $this->assertSame(1, Unread::forUser($this->client));
    }

    /** A message in a conversation has no recipient_id, and still counts. */
    public function test_the_badge_and_the_page_agree(): void
    {
        $this->conversation($this->pro, 'Are you free on the 12th?');

        $page = $this->actingAs($this->client)->get(route('client.chat.index'))->assertOk();

        $this->assertSame(1, Unread::forUser($this->client));
        $this->assertSame(1, $page->viewData('tabCounts')['unread']);

        // And the badge in the sidebar carries that same one.
        $this->assertMatchesRegularExpression(
            '/cl-nav-badge cl-nav-badge-count">\s*1\s*</',
            $page->getContent(),
        );
    }

    /** Nothing this client cannot open is counted at them. */
    public function test_a_conversation_the_client_may_not_open_is_not_counted(): void
    {
        $this->conversation($this->otherClient, 'Hello from another client');

        $this->assertSame(0, Unread::forUser($this->client),
            'The badge is holding a number the client can never clear.');
    }

    /** Muted is still read later, just not counted now. */
    public function test_a_muted_conversation_is_not_counted(): void
    {
        $c = $this->conversation($this->pro, 'Are you free on the 12th?');

        \Illuminate\Support\Facades\DB::table('conversation_participants')
            ->where('conversation_id', $c->id)->where('user_id', $this->client->id)
            ->update(['muted_at' => now()]);

        $this->assertSame(0, Unread::forUser($this->client));
    }

    public function test_the_layout_asks_that_one_question(): void
    {
        $layout = file_get_contents(base_path('resources/views/layouts/client.blade.php'));

        $this->assertStringContainsString('Unread::forUser(auth()->user())', $layout);
        $this->assertStringNotContainsString("Message::where('recipient_id', auth()->id())", $layout);
    }
}
