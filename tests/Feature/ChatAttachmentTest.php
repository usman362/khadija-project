<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Attachments in chat.
 *
 * Ali reported the paperclip as doing nothing. It opened a file dialog and the
 * upload 500'd, every time, for everyone.
 *
 * A file is uploaded the moment it is picked and held until the message is
 * sent — upload returns an id, send takes `attachment_ids`. Between the two
 * there is an attachment with no message. But `message_id` was NOT NULL with a
 * foreign key, and the controller inserted `0` into it, with a comment calling
 * it temporary, then updated it to null on the next line. MySQL never reached
 * the next line: 0 is not a message, the constraint rejected the INSERT.
 *
 * A second fault sat behind it. `download()` 404'd whenever an attachment had
 * no message — which is every attachment for as long as it sits in the
 * composer, so even a successful upload could not be previewed.
 */
class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Storage::fake('private');
    }

    private function user(string $role = 'client'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    private function conversation(User ...$people): Conversation
    {
        $c = Conversation::create(['type' => 'direct', 'created_by' => $people[0]->id]);

        foreach ($people as $p) {
            $c->participants()->attach($p->id, ['joined_at' => now()]);
        }

        return $c;
    }

    /** The bug itself: an upload with no message must be storable. */
    public function test_a_file_can_be_uploaded_before_the_message_is_sent(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $response = $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->image('menu.png'),
            'conversation_id' => $c->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('file_name', 'menu.png');

        $attachment = MessageAttachment::first();

        $this->assertNotNull($attachment);
        $this->assertNull($attachment->message_id);          // the whole point
        $this->assertSame($client->id, $attachment->uploaded_by);
        Storage::disk('private')->assertExists($attachment->file_path);
    }

    /** Sending joins the two. */
    public function test_sending_a_message_attaches_the_uploaded_file_to_it(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $id = $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->image('menu.png'),
            'conversation_id' => $c->id,
        ])->json('id');

        $response = $this->actingAs($client)->postJson(route('conversations.messages.store', $c), [
            'body'           => 'Here is the menu.',
            'attachment_ids' => [$id],
        ]);

        $response->assertCreated();

        $message = Message::latest('id')->first();

        $this->assertSame($message->id, MessageAttachment::find($id)->message_id);
        $this->assertCount(1, $message->attachments);
    }

    /**
     * An attachment still in the composer belongs to whoever picked it — and
     * to nobody else. This used to 404 for everyone including the uploader.
     */
    public function test_the_uploader_can_read_a_file_that_is_not_yet_sent(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $id = $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->image('menu.png'),
            'conversation_id' => $c->id,
        ])->json('id');

        $attachment = MessageAttachment::find($id);

        $this->actingAs($client)
            ->get(route('attachments.download', $attachment))
            ->assertSuccessful();

        // Not even the other participant — it has not been sent to them.
        $this->actingAs($pro)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }

    /** Once sent, the other side can open it. That is what sending means. */
    public function test_the_other_participant_can_read_it_once_it_is_sent(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $id = $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->image('menu.png'),
            'conversation_id' => $c->id,
        ])->json('id');

        $this->actingAs($client)->postJson(route('conversations.messages.store', $c), [
            'body'           => 'Here is the menu.',
            'attachment_ids' => [$id],
        ]);

        $this->actingAs($pro)
            ->get(route('attachments.download', MessageAttachment::find($id)))
            ->assertSuccessful();
    }

    public function test_somebody_outside_the_conversation_cannot_read_it(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $id = $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->image('menu.png'),
            'conversation_id' => $c->id,
        ])->json('id');

        $this->actingAs($client)->postJson(route('conversations.messages.store', $c), [
            'body'           => 'Here is the menu.',
            'attachment_ids' => [$id],
        ]);

        $this->actingAs($this->user())
            ->get(route('attachments.download', MessageAttachment::find($id)))
            ->assertForbidden();
    }

    public function test_a_disallowed_file_type_is_refused(): void
    {
        $client = $this->user();
        $pro    = $this->user('professional');
        $c      = $this->conversation($client, $pro);

        $this->actingAs($client)->postJson(route('attachments.store'), [
            'file'            => UploadedFile::fake()->create('macro.exe', 4, 'application/x-msdownload'),
            'conversation_id' => $c->id,
        ])->assertStatus(422);

        $this->assertSame(0, MessageAttachment::count());
    }
}
