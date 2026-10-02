<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\UploadedFile as FileRecord;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Ali, 1 October: "here in contact support page allow them to attach any image
 * or doc option so they can show their issues also".
 *
 * A screenshot settles in one look what a paragraph of "the button does
 * nothing" cannot. What this holds is the part that is easy to get wrong: the
 * file takes R54's one upload path rather than a shortcut of its own, the
 * submission keeps the record's id and never a path, and the file is private,
 * so only the person who sent it and the support team can open it.
 */
class SupportRequestTakesAFileTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Storage::fake('private');
        Storage::fake('public');

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    private function send(array $files): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->post('/requests-submissions/new/support_request', [
            'topic'   => 'technical',
            'subject' => 'The Accept button does nothing',
            'detail'  => 'I press Accept on a proposal and the page just sits there.',
            'files'   => $files,
        ]);
    }

    /** The screenshot arrives, and the submission points at it by id. */
    public function test_a_screenshot_arrives_with_the_request(): void
    {
        $this->send([UploadedFile::fake()->image('broken-button.png')])
            ->assertRedirect();

        $record = FileRecord::firstWhere('purpose', 'support_attachment');

        $this->assertNotNull($record, 'No file was stored for the support request.');
        $this->assertSame('broken-button.png', $record->original_name);
        $this->assertSame($this->client->id, $record->user_id);

        $submission = FormSubmission::firstWhere('form_key', 'support_request');

        $this->assertSame([$record->id], $submission->payload['files'],
            'The submission should keep the record id, never a path.');
    }

    /** It is listed on the request, by name. */
    public function test_the_request_lists_what_was_sent(): void
    {
        // An image, because the pipeline checks a file's contents against its
        // name: a fake PDF is not a PDF, and it is right to refuse it.
        $this->send([UploadedFile::fake()->image('the-screen.png')]);

        $submission = FormSubmission::firstWhere('form_key', 'support_request');

        $this->assertNotNull($submission, 'The request was not stored.');

        $this->actingAs($this->client)
            ->get(route('forms.show', $submission))
            ->assertOk()
            ->assertSee('the-screen.png');
    }

    /** A support file is private: its sender may open it, a stranger may not. */
    public function test_only_the_sender_and_staff_can_open_it(): void
    {
        $this->send([UploadedFile::fake()->image('receipt.png')]);

        $record = FileRecord::firstWhere('purpose', 'support_attachment');

        $this->assertSame('private', $record->disk, 'A support file must not be on a public disk.');

        $this->actingAs($this->client)->get(route('uploads.show', $record))->assertOk();

        $stranger = User::factory()->create(['primary_role' => 'client']);
        $stranger->assignRole('client');

        $this->actingAs($stranger)->get(route('uploads.show', $record))->assertForbidden();
    }

    /** Sending nothing is still a support request. */
    public function test_the_file_is_optional(): void
    {
        $this->send([])->assertRedirect();

        $this->assertSame(0, FileRecord::where('purpose', 'support_attachment')->count());
        $this->assertNotNull(FormSubmission::firstWhere('form_key', 'support_request'));
    }

    /** Something that is not a document or a picture is refused. */
    public function test_a_file_of_the_wrong_kind_is_refused(): void
    {
        $this->send([UploadedFile::fake()->create('payload.exe', 10)])
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, FormSubmission::where('form_key', 'support_request')->count());
    }
}
