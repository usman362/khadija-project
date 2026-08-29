<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Event;
use App\Models\User;
use App\Support\UserLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The limits where they actually bite — Khadijah's sheet, 29 Aug.
 *
 * UserLimitsTest covers the mechanism. This covers the wiring: that each rule
 * is attached to the real action, and — the part that is easy to get wrong —
 * that a request which FAILS does not spend an allowance.
 */
class UserLimitsInPlaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role = 'client'): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function conversation(User ...$people): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $people[0]->id]);

        foreach ($people as $p) {
            $c->participants()->attach($p->id, ['joined_at' => now()]);
        }

        return $c;
    }

    /** Ten an hour on messages, as configured. */
    public function test_messages_stop_at_the_hourly_limit(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($client)
                ->postJson(route('conversations.messages.store', $c), ['body' => "Message {$i}"])
                ->assertCreated();
        }

        $this->actingAs($client)
            ->postJson(route('conversations.messages.store', $c), ['body' => 'One too many'])
            ->assertStatus(422);

        $this->assertSame(10, $c->messages()->count());
    }

    /**
     * The important one: a message rejected for being empty must not cost the
     * sender one of their ten.
     */
    public function test_a_rejected_message_costs_nothing(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($client)
                ->postJson(route('conversations.messages.store', $c), ['body' => ''])
                ->assertStatus(422);
        }

        $this->assertSame(10, UserLimit::remaining('messages-hour', $client));
    }

    /** Thirty guests to one event, and the next is refused. */
    public function test_invitations_are_capped_per_event(): void
    {
        $client = $this->user();

        $event = Event::create([
            'client_id' => $client->id, 'created_by' => $client->id,
            'title' => 'Reception', 'status' => 'pending', 'starts_at' => now()->addMonth(),
        ]);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($client)
                ->post(route('client.attendees.store', $event), ['name' => "Guest {$i}"])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($client)
            ->post(route('client.attendees.store', $event), ['name' => 'Guest 31'])
            ->assertSessionHasErrors();

        $this->assertSame(30, $event->attendees()->count());
    }

    /**
     * A paste of fifty is refused whole rather than silently becoming thirty.
     * Twenty guests quietly dropped is worse than being told it will not fit.
     */
    public function test_an_import_that_would_overflow_is_refused_whole(): void
    {
        $client = $this->user();

        $event = Event::create([
            'client_id' => $client->id, 'created_by' => $client->id,
            'title' => 'Gala', 'status' => 'pending', 'starts_at' => now()->addMonth(),
        ]);

        $list = collect(range(1, 50))->map(fn ($i) => "Guest {$i}, guest{$i}@example.com")->implode("\n");

        $this->actingAs($client)
            ->post(route('client.attendees.import', $event), ['list' => $list])
            ->assertSessionHasErrors('list');

        $this->assertSame(0, $event->attendees()->count(), 'Nothing should have been saved.');
    }

    /** Each event gets its own thirty. */
    public function test_a_second_event_has_its_own_allowance(): void
    {
        $client = $this->user();

        $make = fn (string $t) => Event::create([
            'client_id' => $client->id, 'created_by' => $client->id,
            'title' => $t, 'status' => 'pending', 'starts_at' => now()->addMonth(),
        ]);

        $first  = $make('First');
        $second = $make('Second');

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($client)->post(route('client.attendees.store', $first), ['name' => "G{$i}"]);
        }

        $this->actingAs($client)
            ->post(route('client.attendees.store', $second), ['name' => 'Someone'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $second->attendees()->count());
    }

    /** Three password-reset requests a day, keyed on the address. */
    public function test_password_resets_are_capped_per_email(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasErrors('email');

        // A different address is untouched by the first one's spending.
        $this->post(route('password.email'), ['email' => $this->user()->email])
            ->assertSessionHasNoErrors();
    }
}
