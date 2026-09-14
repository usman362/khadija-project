<?php

namespace Tests\Feature;

use App\Models\{Conversation, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Round two from the Developer Handoff v4 of 12 September: Messages and sign-up. */
class HandoffSep12RoundTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role, string $name = null): User
    {
        $u = User::factory()->create(['primary_role' => $role] + ($name ? ['name' => $name] : []));
        $u->assignRole($role);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore', 'service_area_status' => ServiceArea::SUPPORTED]);

        return $u->fresh();
    }

    /** DIR-29: a client can start a group chat with 2 or 3 professionals. */
    public function test_a_group_chat_can_be_started_and_reads_as_everyone(): void
    {
        $client = $this->user('client');
        $a = $this->user('professional', 'Alpha Pro');
        $b = $this->user('professional', 'Beta Pro');

        $page = $this->actingAs($client)->get(route('client.chat.index'))->assertOk();
        $page->assertSee('Group chat')->assertSee('Choose 2 or 3 professionals');

        $res = $this->actingAs($client)->postJson(route('conversations.store'), ['type' => 'direct', 'participant_ids' => [$a->id, $b->id]])->assertSuccessful();
        $conv = Conversation::findOrFail($res->json('id'));
        $this->assertCount(3, $conv->participants);

        Message::create(['conversation_id' => $conv->id, 'sender_id' => $client->id, 'body' => 'Hello both']);

        $this->actingAs($client)->get(route('client.chat.index'))->assertSee('Alpha Pro, Beta Pro');
    }

    /** DIR-30 / OA-119: both directions, and N/A below three replies. */
    public function test_reply_times_go_both_ways_and_need_enough_replies(): void
    {
        $client = $this->user('client');
        $pro = $this->user('professional');
        $conv = Conversation::create(['type' => 'direct', 'created_by' => $client->id]);
        $conv->participants()->attach([$client->id => ['joined_at' => now()], $pro->id => ['joined_at' => now()]]);

        $t = now()->subDays(2);
        $say = function (User $u, int $minutes) use ($conv, &$t) {
            $t = $t->copy()->addMinutes($minutes);
            $m = Message::create(['conversation_id' => $conv->id, 'sender_id' => $u->id, 'body' => 'x']);
            $m->forceFill(['created_at' => $t])->saveQuietly();
        };

        // Only one reply each way: not enough.
        $say($pro, 0); $say($client, 10);
        $html = $this->actingAs($client)->get(route('client.chat.index'))->getContent();
        $this->assertStringContainsString('N/A until there are at least 3 replies', $html);

        // Three replies each way.
        $say($pro, 20); $say($client, 10); $say($pro, 20); $say($client, 10);
        $say($client, 30); $say($pro, 30);

        $html = $this->actingAs($client)->get(route('client.chat.index'))->getContent();
        $this->assertStringContainsString('<div class="v">10m</div>', $html);
        $this->assertStringContainsString('Professionals: <b>', $html);
    }

    /** DIR-34: verification is offered at sign-up. */
    public function test_the_welcome_page_offers_verification(): void
    {
        $client = $this->user('client');

        $this->actingAs($client)->get(route('register.welcome'))
            ->assertOk()->assertSee('Verify my account')->assertSee(route('client.verification.show'), false);
    }
}
