<?php

namespace Tests\Feature;

use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Attachments could be sent and could not be looked at.
 *
 * Every file — a photo included — was listed as a generic icon and a filename,
 * and clicking it started a download rather than opening it. So you could not
 * tell a photo from a spreadsheet without saving it first, and a thumbnail had
 * nothing it could point at.
 */
class MessageAttachmentViewingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('private');
    }

    private function client(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        return $user->fresh();
    }

    private function upload(User $owner, UploadedFile $file): MessageAttachment
    {
        $path = $file->store('attachments', 'private');

        return MessageAttachment::create([
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
            'uploaded_by' => $owner->id,
        ]);
    }

    public function test_an_image_opens_instead_of_downloading(): void
    {
        $client = $this->client();
        $att = $this->upload($client, UploadedFile::fake()->image('venue.jpg'));

        $response = $this->actingAs($client)->get(route('attachments.download', $att));

        $response->assertSuccessful();
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    /** Asking for the file itself still saves it. */
    public function test_an_image_can_still_be_downloaded_on_purpose(): void
    {
        $client = $this->client();
        $att = $this->upload($client, UploadedFile::fake()->image('venue.jpg'));

        $response = $this->actingAs($client)->get(route('attachments.download', $att) . '?download=1');

        $response->assertSuccessful();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    /** Anything that is not for looking at keeps saving. */
    public function test_a_document_still_downloads(): void
    {
        $client = $this->client();
        $att = $this->upload($client, UploadedFile::fake()->create('quote.docx', 12, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));

        $response = $this->actingAs($client)->get(route('attachments.download', $att));

        $response->assertSuccessful();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    /** The flag the thread needs to know whether to draw a thumbnail. */
    public function test_an_attachment_says_whether_it_is_an_image(): void
    {
        $client = $this->client();

        $image = $this->upload($client, UploadedFile::fake()->image('venue.jpg'))->fresh();
        $doc   = $this->upload($client, UploadedFile::fake()->create('quote.pdf', 8, 'application/pdf'))->fresh();

        // Appended, so it survives into the JSON the send endpoint returns —
        // otherwise a photo is a thumbnail after a reload and a grey icon the
        // moment it is sent.
        $this->assertTrue($image->toArray()['is_image']);
        $this->assertFalse($doc->toArray()['is_image']);
    }

    /** Somebody else's unsent attachment stays theirs. */
    public function test_a_stranger_cannot_open_an_unsent_attachment(): void
    {
        $owner = $this->client();
        $att = $this->upload($owner, UploadedFile::fake()->image('private.jpg'));

        $this->actingAs($this->client())
            ->get(route('attachments.download', $att))
            ->assertForbidden();
    }

    /** A voice note was the one thing that could not be sent at all. */
    public function test_audio_can_be_uploaded(): void
    {
        $client = $this->client();
        $conversation = \App\Models\Conversation::create(['subject' => 'About the venue', 'created_by' => $client->id]);
        $conversation->participants()->sync([$client->id]);

        $this->actingAs($client)->post(route('attachments.store'), [
            'file' => UploadedFile::fake()->create('voice-note.mp3', 40, 'audio/mpeg'),
            'conversation_id' => $conversation->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('message_attachments', ['file_name' => 'voice-note.mp3']);
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function playableTypes(): array
    {
        return [
            'image' => ['photo.jpg', 'image/jpeg', 'image'],
            'video' => ['walkthrough.mp4', 'video/mp4', 'video'],
            'audio' => ['voice-note.mp3', 'audio/mpeg', 'audio'],
            'pdf'   => ['quote.pdf', 'application/pdf', 'pdf'],
        ];
    }

    /**
     * Everything you look at or listen to must arrive as something the browser
     * will play, with byte ranges — a streamed response ignores Range, so a
     * video cannot be scrubbed and Safari refuses to start it at all.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('playableTypes')]
    public function test_media_is_served_to_be_played(string $name, string $mime, string $kind): void
    {
        $client = $this->client();
        $att = $this->upload($client, UploadedFile::fake()->create($name, 20, $mime));

        $response = $this->actingAs($client)->get(route('attachments.download', $att));

        $response->assertSuccessful();
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertSame('bytes', $response->headers->get('accept-ranges'));
        $this->assertSame($kind, $att->fresh()->kind);
    }

    /** The one word the thread and the live JS both draw from. */
    public function test_an_attachment_reports_its_kind(): void
    {
        $client = $this->client();

        $doc = $this->upload($client, UploadedFile::fake()->create('notes.docx', 5,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))->fresh();

        $this->assertSame('file', $doc->kind);
        $this->assertArrayHasKey('kind', $doc->toArray());
    }
}
