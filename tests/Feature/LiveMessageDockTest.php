<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's Live Message Dock (developer guide v2, 2026-09-18).
 *
 * A bar along the bottom of every signed-in client page, reading the same
 * conversations as Messages (Inbox). The guide's first rule is architecture:
 * built once at the layout, one source of truth, never a second copy of the
 * history. Most of what it asks for is behaviour in the browser; these hold
 * the parts a server test can see.
 */
class LiveMessageDockTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Sarah Mitchell']);
        $this->pro->assignRole('professional');
    }

    private function conversation(): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $c->participants()->attach([
            $this->client->id => ['joined_at' => now()],
            $this->pro->id    => ['joined_at' => now()],
        ]);

        return $c;
    }

    /** §1: once, at the layout, so every client page has it. */
    public function test_the_bar_is_on_every_client_page(): void
    {
        foreach (['client.dashboard', 'client.events.index', 'client.proposals.index'] as $route) {
            $this->actingAs($this->client)->get(route($route))
                ->assertOk()
                ->assertSee('id="lmdBar"', false)
                ->assertSee('Live Messages')
                ->assertSee('Do Not Disturb');
        }
    }

    /** The Messages page is the full version; the bar is not repeated there. */
    public function test_not_on_the_messages_page_itself(): void
    {
        $this->actingAs($this->client)->get(route('client.chat.index'))
            ->assertOk()
            ->assertDontSee('id="lmdBar"', false);
    }

    /**
     * §1 and §12: the dock reads and writes through the Messages endpoints.
     * No other store, so the two cannot drift apart.
     */
    public function test_it_uses_the_same_endpoints_as_messages(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();

        foreach (['conversations.index', 'conversations.messages.store', 'conversations.mark-read', 'conversations.mute'] as $name) {
            $this->assertStringContainsString(
                str_replace('/', '\/', parse_url(route($name, ['conversation' => '__ID__']), PHP_URL_PATH)),
                $html,
                "The dock does not use {$name}.",
            );
        }
    }

    /** "Responded" needs to know who spoke last, and the header needs the profile. */
    public function test_the_list_says_who_spoke_last_and_links_the_profile(): void
    {
        $c = $this->conversation();
        Message::create(['conversation_id' => $c->id, 'sender_id' => $this->pro->id, 'body' => 'Can I add a second photographer?']);
        Message::create(['conversation_id' => $c->id, 'sender_id' => $this->client->id, 'body' => 'Yes please.']);

        $row = $this->actingAs($this->client)->getJson(route('conversations.index'))->assertOk()->json('data.0');

        $this->assertSame($this->client->id, $row['last_message_sender_id']);
        $this->assertSame(route('public.professional.show', $this->pro->id), $row['peer']['profile']);
        $this->assertArrayHasKey('gr_id', $row['peer']);
    }

    /** §12: a reply from the dock is the same message the Messages page shows. */
    public function test_a_reply_from_the_dock_lands_in_the_conversation(): void
    {
        $c = $this->conversation();

        $this->actingAs($this->client)
            ->postJson(route('conversations.messages.store', $c), ['body' => 'Saturday works.'])
            ->assertSuccessful();

        $this->assertSame(1, $c->messages()->where('body', 'Saturday works.')->count());
    }

    /** §5: closing a card never deletes the conversation. It only hides the tab. */
    public function test_close_is_presentation_only(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_live_message_dock.blade.php'));

        $this->assertMatchesRegularExpression('/function closeCard\(id\) \{(?:(?!\n    \}).)*\}/s', $dock);
        preg_match('/function closeCard\(id\) \{((?:(?!\n    \}).)*)/s', $dock, $m);
        $this->assertStringNotContainsString('fetch', $m[1]);
        $this->assertStringNotContainsString('post(', $m[1]);
    }

    /** §8: one ping, never on the first load, never muted or under Do Not Disturb. */
    public function test_the_ping_rules(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_live_message_dock.blade.php'));

        $this->assertStringContainsString("if (! first && ! pinged && ! dndOn() && ! c.muted_at) { ping(); pinged = true; }", $dock);
    }

    /** §6: "Read" only when the other side's read is on record; no invented "Delivered". */
    public function test_read_is_only_shown_when_it_is_recorded(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_live_message_dock.blade.php'));

        $this->assertStringContainsString('(m.reads || []).some(function (r) { return r.user_id !== me; })', $dock);
        $this->assertStringNotContainsString('Delivered', $dock);
    }

    /** §11: the page keeps room for the bar rather than sliding under it. */
    public function test_the_page_leaves_room_for_the_bar(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_live_message_dock.blade.php'));

        $this->assertStringContainsString('body.has-lmd .cl-main { padding-bottom: 86px; }', $dock);
    }

    /** §10: a phone gets one bubble, not a shrunken bar. */
    public function test_a_phone_gets_a_bubble(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_live_message_dock.blade.php'));

        $this->assertStringContainsString('.lmd-bar, .lmd-chat { display: none !important; }', $dock);
        $this->assertStringContainsString('class="lmd-bubble"', $dock);
    }

    /** Sir Peter's dock: "Sarah is typing…" works without a socket server. */
    public function test_typing_shows_in_the_other_persons_window_for_a_few_seconds(): void
    {
        $c = $this->conversation();

        $this->actingAs($this->pro)->postJson(route('conversations.typing', $c))->assertOk();

        $this->actingAs($this->client)->getJson(route('conversations.show', $c))
            ->assertOk()->assertJsonPath('typing', [$this->pro->name]);

        // Your own typing is never shown back to you.
        $this->actingAs($this->pro)->getJson(route('conversations.show', $c))
            ->assertOk()->assertJsonPath('typing', []);

        $this->travel(10)->seconds();
        $this->actingAs($this->client)->getJson(route('conversations.show', $c))
            ->assertOk()->assertJsonPath('typing', []);
    }

    /** The window matches the drawing: attachment, emoji, typing, and the footer actions. */
    public function test_the_chat_window_has_every_control_from_the_drawing(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->assertOk()->getContent();

        foreach (['data-lmd-attach', 'data-lmd-emoji', 'data-lmd-typing', 'data-lmd-file', 'data-lmd-sound',
                  'data-lmd-mute', 'data-lmd-profile', 'Open in Messages', 'Do Not Disturb', 'Message Settings'] as $needle) {
            $this->assertStringContainsString($needle, $html, "missing: {$needle}");
        }
    }
}
