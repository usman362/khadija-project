<?php

namespace Tests\Feature;

use App\Domain\Messaging\MessagePriority;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Three things the message dock has done since 21 September that the full
 * Messages page never did.
 *
 * The column, the endpoints and the five levels all existed. The inbox simply
 * never read or offered them — so a client who marked a message Urgent in the
 * popup saw no trace of it on the page the popup's own "Open in Messages"
 * link sends them to. Same for the read tick and for knowing somebody is
 * typing.
 *
 * The dock itself is untouched: it is shared with the professional side.
 */
class TheInboxCaughtUpWithTheDockTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['name' => 'Dana Whitfield', 'primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['name' => 'Grand Oak', 'primary_role' => 'professional']);
        $this->pro->assignRole('professional');

        $this->conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $this->conversation->participants()->attach([$this->client->id, $this->pro->id]);
    }

    private function message(User $from, string $body, ?string $priority = null): Message
    {
        return Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $from->id,
            'recipient_id' => $from->id === $this->client->id ? $this->pro->id : $this->client->id,
            'body' => $body,
            'priority' => $priority,
        ]);
    }

    /** The message list, without the page's own scripts around it. */
    private function thread(string $html): string
    {
        $start = strpos($html, 'id="cm-msgs"');
        $this->assertNotFalse($start, 'No message list on the page.');

        $end = strpos($html, 'id="cm-typing"', $start);

        return substr($html, $start, $end - $start);
    }

    private function inbox(): string
    {
        return $this->actingAs($this->client)->get('/client/messages')->assertSuccessful()->getContent();
    }

    /** One tick for sent. Nothing records delivery, so nothing claims it. */
    public function test_a_message_nobody_has_opened_shows_one_tick(): void
    {
        $this->message($this->client, 'Are you free on the 12th?');

        $html = $this->inbox();

        $this->assertStringContainsString('class="cm-tick "', $html);
        $this->assertStringContainsString('title="Sent"', $html);
        $this->assertStringNotContainsString('title="Read"', $html);
    }

    /** Two when the other side's read is on record. */
    public function test_a_message_the_other_side_has_read_shows_two(): void
    {
        $m = $this->message($this->client, 'Are you free on the 12th?');

        MessageRead::create(['message_id' => $m->id, 'user_id' => $this->pro->id, 'read_at' => now()]);

        $html = $this->inbox();

        $this->assertStringContainsString('title="Read"', $html);
        $this->assertStringContainsString('is-read', $html);
    }

    /** My own reading of my own message is not somebody having read it. */
    public function test_reading_my_own_message_does_not_tick_it(): void
    {
        $m = $this->message($this->client, 'Are you free on the 12th?');

        MessageRead::create(['message_id' => $m->id, 'user_id' => $this->client->id, 'read_at' => now()]);

        $this->assertStringNotContainsString('title="Read"', $this->inbox());
    }

    /** A level set anywhere shows here, in its own colour. */
    public function test_a_level_set_in_the_dock_is_on_the_page(): void
    {
        $this->message($this->pro, 'The hall is free on the 25th.', MessagePriority::URGENT);

        $html = $this->inbox();

        $thread = $this->thread($html);

        $this->assertStringContainsString('class="cm-pri"', $thread);
        $this->assertStringContainsString('Urgent', $thread);
        $this->assertStringContainsString(MessagePriority::COLOURS[MessagePriority::URGENT], $thread);
        $this->assertStringContainsString('data-priority="urgent"', $thread);
    }

    /** Routine is the absence of a chip, not a chip reading "Routine". */
    public function test_a_message_with_no_level_wears_no_chip(): void
    {
        $this->message($this->pro, 'The hall is free on the 25th.');

        // The thread itself, not the script below it that knows how to draw
        // a chip for the messages that do have one.
        $this->assertStringNotContainsString('class="cm-pri"', $this->thread($this->inbox()));
    }

    /** Every message can be marked, by either person, from the page. */
    public function test_the_page_offers_the_five_levels_on_every_message(): void
    {
        $this->message($this->client, 'Mine');
        $this->message($this->pro, 'Theirs');

        $html = $this->inbox();

        $this->assertSame(
            2,
            substr_count($this->thread($html), 'data-pri-open='),
            'Not every message can be marked.'
        );

        foreach (MessagePriority::LEVELS as $key => $label) {
            $this->assertStringContainsString('data-pri-set="' . $key . '"', $html, $label . ' is missing.');
        }

        // And it posts to the endpoint the dock already uses.
        $this->assertStringContainsString('priorityUrl', $html);
        $this->assertStringContainsString('typingUrl', $html);
    }

    /** The endpoint behind the button, from this client's own session. */
    public function test_the_client_can_set_and_clear_a_level(): void
    {
        $m = $this->message($this->pro, 'The hall is free on the 25th.');

        $this->actingAs($this->client)
            ->postJson("/conversations/{$this->conversation->id}/messages/{$m->id}/priority", ['priority' => 'urgent'])
            ->assertSuccessful()
            ->assertJsonPath('priority', 'urgent')
            ->assertJsonPath('label', 'Urgent');

        // Routine is how it comes off, and it stores nothing rather than
        // labelling the row "Routine".
        $this->actingAs($this->client)
            ->postJson("/conversations/{$this->conversation->id}/messages/{$m->id}/priority", ['priority' => 'routine'])
            ->assertSuccessful()
            ->assertJsonPath('priority', null)
            ->assertJsonPath('label', null);

        $this->assertNull($m->fresh()->priority);
    }

    /** The typing line is on the page, and it starts out saying nothing. */
    public function test_the_page_can_say_who_is_typing(): void
    {
        $this->message($this->pro, 'Hello');

        $html = $this->inbox();

        $this->assertStringContainsString('id="cm-typing"', $html);
        $this->assertStringContainsString('id="cm-typing-who"', $html);
        // Hidden until somebody is: no line held open for one who might be.
        $this->assertStringNotContainsString('cm-typing is-on', $html);
    }

    /**
     * The same chip, not a second way of saying the same thing.
     *
     * The dock draws a pill: a dot and the word inside a hairline ring of
     * the level's own colour. The page was drawing a bare dot and a shouted
     * uppercase word, so one message read as two different things depending
     * on which window you were looking at it in.
     */
    public function test_the_chip_is_the_same_chip_as_the_dock_draws(): void
    {
        $this->message($this->pro, 'The hall is free.', MessagePriority::IMPORTANT);

        $css = $this->inbox();

        $this->assertMatchesRegularExpression(
            '/\.cm-pri\s*\{[^}]*border:\s*1px solid currentColor[^}]*\}/s',
            $css,
            'The level has lost its ring, so it is a dot and a word rather than the pill the dock draws.'
        );
        $this->assertMatchesRegularExpression(
            '/\.cm-pri\s*\{[^}]*border-radius:\s*999px/s',
            $css,
            'The level is no longer a pill.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.cm-pri\s*\{[^}]*text-transform:\s*uppercase/s',
            $css,
            'The level is shouted here and spoken in the dock.'
        );

        // And the word itself is the one the dock uses.
        $this->assertStringContainsString('>Important<', $this->thread($css));
    }

    /** The row carries who they are and what the last message was marked. */
    public function test_a_conversation_row_says_what_the_popup_row_says(): void
    {
        $this->message($this->pro, 'The hall is free.', MessagePriority::IMPORTANT);

        $html = $this->inbox();

        $this->assertStringContainsString('class="cm-tag cm-role"', $html, 'The row does not say who they are.');
        $this->assertStringContainsString('Professional', $html);

        // The level of the last message, as a pill, in the list.
        $this->assertMatchesRegularExpression(
            '/cm-conv-tags.*?class="cm-pri"[^>]*>\s*<i><\/i>Important/s',
            $html,
            'The row does not carry the last message\'s level.'
        );
    }

    /** And a star that stars rather than opening the conversation. */
    public function test_the_row_can_be_favourited_without_opening_it(): void
    {
        $this->message($this->pro, 'Hello');

        $html = $this->inbox();

        $this->assertStringContainsString('class="cm-star "', $html);
        $this->assertStringContainsString('☆', $html);
        $this->assertStringContainsString('data-fav="' . route('conversations.favorite', $this->conversation) . '"', $html);

        // The row is a link, so the handler has to stop it following one.
        $this->assertStringContainsString("closest('.cm-star')", $html);
        $this->assertStringContainsString('e.preventDefault();', $html);

        $this->actingAs($this->client)
            ->postJson(route('conversations.favorite', $this->conversation))
            ->assertSuccessful()
            ->assertJsonPath('favorited', true);

        $this->assertStringContainsString('★', $this->inbox());
    }

    /**
     * The shared live partial gained an opt-in hook rather than new
     * behaviour, because the professional page includes the same file.
     */
    public function test_the_shared_engine_only_calls_back_when_asked(): void
    {
        $engine = file_get_contents(resource_path('views/partials/_chat_live.blade.php'));

        $this->assertStringContainsString("typeof cfg.onPoll === 'function'", $engine,
            'The hook is unguarded, so a page that never asked for it would break.');

        $professional = file_get_contents(resource_path('views/professional/chat/index.blade.php'));
        $this->assertStringNotContainsString('onPoll', $professional,
            'The professional page was changed; it is out of scope.');
    }
}
