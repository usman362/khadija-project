<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

/**
 * Khadijah, 27 September: "make sure client or professional or any other user
 * are not able to delete or edit the message."
 *
 * That is already true, and this is what keeps it true. A message once sent
 * is a record: two people rely on what it said, and an agreement, a price or
 * a date may have been settled on the strength of it. There is no control
 * that edits or deletes one, and no address that would accept the attempt.
 *
 * The check is on the routes rather than on a button, because a button is
 * only the polite way in. What matters is that nothing on the other side
 * answers.
 */
class AMessageCannotBeEditedOrDeletedTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $this->pro->assignRole('professional');
    }

    private function message(): Message
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $this->client->id]);
        $c->addParticipant($this->client);
        $c->addParticipant($this->pro);

        return Message::create([
            'conversation_id' => $c->id,
            'sender_id' => $this->client->id,
            'body' => 'The hall is free on Oct 25.',
        ]);
    }

    /** Nothing answers a request to change or remove one. */
    public function test_no_address_will_edit_or_delete_a_message(): void
    {
        $m = $this->message();

        foreach ([
            ['PUT', "/conversations/{$m->conversation_id}/messages/{$m->id}"],
            ['PATCH', "/conversations/{$m->conversation_id}/messages/{$m->id}"],
            ['DELETE', "/conversations/{$m->conversation_id}/messages/{$m->id}"],
            ['DELETE', "/messages/{$m->id}"],
        ] as [$verb, $path]) {
            $status = $this->actingAs($this->client)->json($verb, $path)->getStatusCode();

            $this->assertContains($status, [404, 405],
                "{$verb} {$path} answered with {$status}; nothing should be listening there.");
        }

        $this->assertDatabaseHas('messages', ['id' => $m->id, 'body' => 'The hall is free on Oct 25.']);
    }

    /** And no route is registered that would, whatever it were called. */
    public function test_the_platform_registers_no_such_route(): void
    {
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_contains($uri, 'message')) {
                continue;
            }

            // A redirect answers every verb by definition and changes nothing.
            if (str_contains((string) $route->getActionName(), 'RedirectController')) {
                continue;
            }

            $writes = array_intersect($route->methods(), ['PUT', 'PATCH', 'DELETE']);

            if ($writes) {
                $offenders[] = implode('|', $writes).' '.$uri;
            }
        }

        $this->assertSame([], $offenders,
            "a message can now be changed or removed through:\n".implode("\n", $offenders));
    }

    /** The other side cannot reach it either. */
    public function test_the_other_person_cannot_remove_it(): void
    {
        $m = $this->message();

        $this->actingAs($this->pro)
            ->json('DELETE', "/conversations/{$m->conversation_id}/messages/{$m->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('messages', ['id' => $m->id]);
    }
}
