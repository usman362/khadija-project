<?php

namespace Tests\Feature;

use App\Models\{Conversation, Event, Message, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The message dock, redesigned from Sir Peter's mockup (2026-09-11): photo,
 * online dot, the professional's service, the event and request type, an
 * Unread tab, Favorites, search, and a way to the full Messages page.
 */
class MessageDockRedesignTest extends TestCase
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

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan', 'last_active_at' => now()]);
        $this->pro->assignRole('professional');
    }

    private function conversation(User $with, string $source = 'direct_offer', bool $unread = false): Conversation
    {
        $event = Event::create([
            'title' => 'Johnson Wedding', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'source' => $source, 'starts_at' => now()->addMonth(),
        ]);

        $c = Conversation::create(['type' => 'event', 'event_id' => $event->id, 'created_by' => $this->client->id]);
        $c->participants()->attach([$this->client->id => ['joined_at' => now()], $with->id => ['joined_at' => now()]]);

        Message::create([
            'conversation_id' => $c->id,
            'sender_id'       => $unread ? $with->id : $this->client->id,
            'body'            => 'Can you confirm the event time?',
        ]);

        return $c;
    }

    private function list(array $query = []): array
    {
        return $this->actingAs($this->client)
            ->getJson(route('conversations.index', $query))
            ->assertOk()
            ->json('data');
    }

    public function test_a_row_knows_who_where_and_what(): void
    {
        $this->conversation($this->pro);

        $row = $this->list()[0];

        $this->assertSame('Priya Raghavan', $row['peer']['name']);
        $this->assertTrue($row['peer']['online']);
        $this->assertNotEmpty($row['peer']['avatar']);
        $this->assertSame('Professional', $row['peer']['subtitle']);   // no service on file yet
        $this->assertSame('Johnson Wedding', $row['event']['title']);
        $this->assertSame('Direct Request', $row['request_type']);
    }

    /** With its timezone, so "a minute ago" is not read as "5h" in Pakistan. */
    public function test_the_last_message_time_carries_its_timezone(): void
    {
        $this->conversation($this->pro);

        $this->assertMatchesRegularExpression('/T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $this->list()[0]['last_message_at']);
    }

    public function test_someone_not_seen_lately_is_not_online(): void
    {
        $this->pro->forceFill(['last_active_at' => now()->subHour()])->save();
        $this->conversation($this->pro);

        $this->assertFalse($this->list()[0]['peer']['online']);
    }

    public function test_request_types_read_as_the_platform_names_them(): void
    {
        $this->conversation($this->pro, 'esr');
        $this->assertSame('Emergency Request', $this->list()[0]['request_type']);
    }

    public function test_the_unread_tab_only_shows_unread(): void
    {
        $other = User::factory()->create(['primary_role' => 'professional']);
        $read = $this->conversation($this->pro);
        $unread = $this->conversation($other, 'user', unread: true);

        $ids = array_column($this->list(['filter' => 'unread']), 'id');

        $this->assertSame([$unread->id], $ids);
        $this->assertNotContains($read->id, $ids);
    }

    public function test_favorites_toggle_and_filter(): void
    {
        $a = $this->conversation($this->pro);
        $b = $this->conversation(User::factory()->create(['primary_role' => 'professional']));

        $this->actingAs($this->client)->postJson(route('conversations.favorite', $a))
            ->assertOk()->assertJson(['favorited' => true]);

        $this->assertSame([$a->id], array_column($this->list(['filter' => 'favorites']), 'id'));

        // One person's star says nothing to the other side.
        $this->assertNull($a->participants()->where('users.id', $this->pro->id)->first()->pivot->favorited_at);

        $this->actingAs($this->client)->postJson(route('conversations.favorite', $a))
            ->assertOk()->assertJson(['favorited' => false]);

        $this->assertSame([], $this->list(['filter' => 'favorites']));
    }

    public function test_only_a_participant_can_star_it(): void
    {
        $c = $this->conversation($this->pro);
        $stranger = User::factory()->create(['primary_role' => 'client']);
        $stranger->assignRole('client');

        $this->actingAs($stranger)->postJson(route('conversations.favorite', $c))->assertForbidden();
    }

    public function test_the_dock_has_the_mockups_parts(): void
    {
        $dock = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        foreach (['Stay connected while you work', 'data-md-search', 'data-md-tab="recent"', 'data-md-tab="unread"',
                  'data-md-tab="favorites"', 'Open as full page', 'View All Messages', 'data-fav'] as $part) {
            $this->assertStringContainsString($part, $dock, "The dock is missing {$part}");
        }
    }
}
