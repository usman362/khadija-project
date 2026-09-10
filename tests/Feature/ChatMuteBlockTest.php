<?php

namespace Tests\Feature;

use App\Domain\Messaging\Blocking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chat, part 2: mute, block, and when someone was last active.
 * Sir Peter's Freelancer example, 2026-09-10; Ali started this part the same day.
 */
class ChatMuteBlockTest extends TestCase
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
        $this->pro = $this->pro->fresh();

        $this->conv = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $this->conv->participants()->attach([
            $this->client->id => ['joined_at' => now()],
            $this->pro->id    => ['joined_at' => now()],
        ]);
    }

    private function send(User $as, string $body = 'Hello there')
    {
        return $this->actingAs($as)->postJson(route('conversations.messages.store', $this->conv), ['body' => $body]);
    }

    private function unreadFor(User $from, User $to): void
    {
        Message::create(['conversation_id' => $this->conv->id, 'sender_id' => $from->id, 'recipient_id' => $to->id, 'body' => 'Are you free?', 'source' => 'user']);
    }

    // ── Mute ─────────────────────────────────────────────────────────

    public function test_mute_is_per_person_and_toggles(): void
    {
        $this->actingAs($this->client)->post(route('conversations.mute', $this->conv))->assertRedirect();

        $pivot = fn (User $u) => $this->conv->participants()->where('users.id', $u->id)->first()->pivot->muted_at;
        $this->assertNotNull($pivot($this->client));
        $this->assertNull($pivot($this->pro));

        $this->actingAs($this->client)->post(route('conversations.mute', $this->conv));
        $this->assertNull($pivot($this->client));
    }

    /** The messages button's number leaves muted conversations out. */
    public function test_the_dock_is_told_which_conversations_are_muted(): void
    {
        $this->unreadFor($this->pro, $this->client);
        $this->actingAs($this->client)->post(route('conversations.mute', $this->conv));

        $row = collect($this->actingAs($this->client)->getJson(route('conversations.index'))->json('data'))->firstWhere('id', $this->conv->id);

        $this->assertNotNull($row['muted_at']);
        $this->assertSame(1, (int) $row['unread_count'], 'The conversation still shows its own unread message.');
        $this->assertStringContainsString('c.muted_at ? 0 :', file_get_contents(resource_path('views/partials/_message_dock.blade.php')));
    }

    public function test_a_muted_conversation_leaves_the_menu_badge(): void
    {
        $this->unreadFor($this->pro, $this->client);

        $before = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();
        // The element, not the bare class name: the layout's stylesheet has
        // a .cl-nav-badge-count rule on every page.
        $this->assertStringContainsString('class="cl-nav-badge cl-nav-badge-count"', $before);

        $this->actingAs($this->client)->post(route('conversations.mute', $this->conv));
        $after = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();
        $this->assertStringNotContainsString('class="cl-nav-badge cl-nav-badge-count"', $after);
    }

    // ── Block ────────────────────────────────────────────────────────

    public function test_after_a_block_neither_side_can_send(): void
    {
        $this->send($this->pro)->assertCreated();

        $this->actingAs($this->client)->post(route('conversations.block', $this->conv))->assertRedirect();

        $this->send($this->pro)->assertForbidden()->assertJson(['message' => Blocking::MESSAGE]);
        $this->send($this->client)->assertForbidden();
    }

    public function test_unblocking_lets_them_talk_again(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));

        $this->send($this->pro)->assertCreated();
    }

    /** Only the blocker can lift it; the blocked person pressing Block adds their own. */
    public function test_the_blocked_person_cannot_lift_the_block(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));
        $this->actingAs($this->pro)->post(route('conversations.block', $this->conv));

        $this->assertTrue(Blocking::blocked($this->client->id, $this->pro->id));
        $this->send($this->pro)->assertForbidden();
    }

    public function test_a_block_stops_a_new_conversation_too(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));

        $this->actingAs($this->pro)->postJson(route('conversations.store'), ['type' => 'direct', 'participant_ids' => [$this->client->id]])
            ->assertForbidden();
    }

    /** No route round it: the message row itself is refused. */
    public function test_a_message_written_any_other_way_is_refused_too(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));

        $this->expectException(AuthorizationException::class);
        Message::create(['conversation_id' => $this->conv->id, 'sender_id' => $this->pro->id, 'body' => 'Sneaking through', 'source' => 'user']);
    }

    /** A blocked attempt is not counted against their hourly limit. */
    public function test_a_refused_message_costs_nothing(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));
        foreach (range(1, 12) as $i) {
            $this->send($this->pro);
        }

        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));
        $this->send($this->pro)->assertCreated();
    }

    public function test_the_blocker_is_told_and_can_undo_it(): void
    {
        $this->actingAs($this->client)->post(route('conversations.block', $this->conv));

        $html = $this->actingAs($this->client)->get(route('client.chat.show', $this->conv))->getContent();

        $this->assertStringContainsString('You blocked Adison Pro. Unblock them to send messages.', $html);
        $this->assertStringContainsString('cm-compose cm-compose-off', $html);
    }

    /** The blocked side is told they cannot send, not that they were blocked. */
    public function test_the_blocked_person_is_not_told_they_were_blocked(): void
    {
        $this->actingAs($this->pro)->post(route('conversations.block', $this->conv));

        $html = $this->actingAs($this->client)->get(route('client.chat.show', $this->conv))->getContent();

        $this->assertStringContainsString(e(Blocking::MESSAGE), $html);   // Blade escapes the apostrophe
        $this->assertStringNotContainsString('blocked you', $html);
        $this->assertStringNotContainsString('You blocked', $html);
    }

    // ── Last active ──────────────────────────────────────────────────

    public function test_a_visit_records_last_active_at_most_every_five_minutes(): void
    {
        $this->actingAs($this->pro)->get('/');
        $first = DB::table('users')->where('id', $this->pro->id)->value('last_active_at');
        $this->assertNotNull($first);

        $this->travel(2)->minutes();
        $this->actingAs($this->pro->fresh())->get('/');
        $this->assertSame($first, DB::table('users')->where('id', $this->pro->id)->value('last_active_at'));

        $this->travel(6)->minutes();
        $this->actingAs($this->pro->fresh())->get('/');
        $this->assertNotSame($first, DB::table('users')->where('id', $this->pro->id)->value('last_active_at'));
    }

    public function test_the_panel_says_when_they_were_last_active_and_never_online(): void
    {
        DB::table('users')->where('id', $this->pro->id)->update(['last_active_at' => now()->subMinutes(20)]);

        $html = $this->actingAs($this->client)->get(route('client.chat.show', $this->conv))->getContent();

        $this->assertStringContainsString('Last active 20 minutes ago', $html);
        $this->assertStringNotContainsString('Active now', $html);
        $this->assertStringNotContainsString('Online', $html);
    }

    /** The photo column was never loaded, so the panel only ever drew initials. */
    public function test_the_panel_shows_their_real_photo(): void
    {
        DB::table('users')->where('id', $this->pro->id)->update(['avatar' => 'avatars/adison.jpg']);

        $html = $this->actingAs($this->client)->get(route('client.chat.show', $this->conv))->getContent();

        $this->assertMatchesRegularExpression('/cm-side-photo">\s*<img src="[^"]*avatars\/adison\.jpg/', $html);
    }
}
