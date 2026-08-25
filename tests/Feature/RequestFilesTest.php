<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Files on a bidding request.
 *
 * Step 6 of the BR wizard said "Attachments aren't available yet" and drew a
 * dashed box. It was honest — there was nowhere to put a file — but a step in
 * an eight-step wizard that does nothing is still a step that does nothing,
 * and the client counts it as one of the eight.
 *
 * The rules these tests hold:
 *
 *  - Files are private. They are served by a controller, never a public URL,
 *    and a floor plan or guest list is not readable by anyone who guesses an id.
 *  - A professional may read them only once the request is PUBLISHED. Before
 *    that it is a draft.
 *  - The type is read off the file, not off its name.
 *  - Uploads made before the Event row exists are adopted by it on publish —
 *    otherwise the wizard's session-only design would lose them.
 */
class RequestFilesTest extends TestCase
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

    private function pro(): User
    {
        $user = User::factory()->create();
        $user->assignRole('professional');

        return $user->fresh();
    }

    private function upload(User $as, string $key = 'wizard-key', ?UploadedFile $file = null)
    {
        return $this->actingAs($as)->postJson(route('client.request-files.store'), [
            'file'      => $file ?: UploadedFile::fake()->image('floorplan.png', 40, 40),
            'draft_key' => $key,
        ]);
    }

    public function test_a_client_can_attach_a_file_to_a_request_in_progress(): void
    {
        $client = $this->client();

        $response = $this->upload($client);

        $response->assertCreated();
        $response->assertJsonPath('file.name', 'floorplan.png');
        $response->assertJsonPath('file.is_image', true);

        $attachment = RequestAttachment::first();
        $this->assertNotNull($attachment);
        $this->assertSame($client->id, $attachment->user_id);
        $this->assertNull($attachment->event_id);          // no event yet — that is the point
        $this->assertSame('wizard-key', $attachment->draft_key);

        Storage::disk('private')->assertExists($attachment->file_path);
    }

    /** The step renders an actual control now, not a notice. */
    public function test_the_wizard_step_offers_an_upload_rather_than_an_apology(): void
    {
        $client = $this->client();

        // Walk the wizard far enough that step 6 is reachable.
        $this->completeThroughProposals($client);

        $response = $this->actingAs($client)->get(route('client.bsr.step', 'files'));

        $response->assertSuccessful();
        $response->assertSee('Drag files here');
        $response->assertDontSee("Attachments aren't available yet", false);
    }

    public function test_an_attached_file_is_named_on_the_review_step(): void
    {
        $client = $this->client();
        $this->completeThroughProposals($client);

        $key = $this->keyFromWizard($client);
        $this->upload($client, $key, UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf'));

        $this->actingAs($client)->get(route('client.bsr.step', 'files'))
            ->assertSuccessful()
            ->assertSee('brief.pdf');
    }

    /**
     * A type that is not on the list is refused.
     *
     * The controller reads the mime off the FILE, not off its name — a shell
     * script called evil.png is rejected in the browser, verified by hand
     * against the running app. It cannot be asserted here: Laravel's fake
     * uploads derive getMimeType() from the extension, so a fake .png is a png
     * whatever is inside it. What this pins is the list itself.
     */
    public function test_a_file_type_that_is_not_allowed_is_refused(): void
    {
        $client = $this->client();

        $bad = UploadedFile::fake()->create('macro.exe', 4, 'application/x-msdownload');

        $this->upload($client, 'wizard-key', $bad)
            ->assertStatus(422)
            ->assertJsonPath('message', 'That kind of file cannot be attached. Images, PDFs, Word, Excel, CSV and plain text are accepted.');

        $this->assertSame(0, RequestAttachment::count());
    }

    public function test_a_file_over_the_limit_is_refused(): void
    {
        $client = $this->client();

        $big = UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf');

        $this->upload($client, 'wizard-key', $big)->assertStatus(422);
        $this->assertSame(0, RequestAttachment::count());
    }

    public function test_a_client_cannot_read_another_clients_file(): void
    {
        $this->upload($this->client());
        $attachment = RequestAttachment::first();

        $this->actingAs($this->client())
            ->get(route('client.request-files.show', $attachment))
            ->assertForbidden();
    }

    /**
     * The whole reason the client attaches anything: the professional bidding
     * has to be able to open it. But only once the request is public.
     */
    public function test_a_professional_reads_the_file_only_after_the_request_is_published(): void
    {
        $client = $this->client();
        $this->upload($client);
        $attachment = RequestAttachment::first();

        $event = Event::create([
            'client_id'    => $client->id,
            'created_by'   => $client->id,
            'title'        => 'Charity gala',
            'status'       => 'pending',
            'is_published' => false,
        ]);
        $attachment->update(['event_id' => $event->id, 'draft_key' => null]);

        $pro = $this->pro();

        // Draft — not yet anyone else's business.
        $this->actingAs($pro)
            ->get(route('client.request-files.show', $attachment))
            ->assertForbidden();

        $event->update(['is_published' => true]);

        $this->actingAs($pro)
            ->get(route('client.request-files.show', $attachment))
            ->assertSuccessful();
    }

    /** Preview means inline; everything else downloads. */
    public function test_an_image_previews_inline_and_a_document_downloads(): void
    {
        $client = $this->client();
        $this->upload($client);
        $image = RequestAttachment::first();

        $this->actingAs($client)
            ->get(route('client.request-files.show', [$image, 'inline' => 1]))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->upload($client, 'wizard-key', UploadedFile::fake()->create('notes.txt', 2, 'text/plain'));
        $doc = RequestAttachment::latest('id')->first();

        // Not a type a browser should render in our origin, so it downloads
        // even when inline is asked for.
        $response = $this->actingAs($client)
            ->get(route('client.request-files.show', [$doc, 'inline' => 1]));

        $response->assertSuccessful();
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_a_client_can_remove_a_file_before_publishing(): void
    {
        $client = $this->client();
        $this->upload($client);
        $attachment = RequestAttachment::first();
        $path = $attachment->file_path;

        $this->actingAs($client)
            ->deleteJson(route('client.request-files.destroy', $attachment))
            ->assertSuccessful();

        $this->assertSame(0, RequestAttachment::count());
        Storage::disk('private')->assertMissing($path);
    }

    /**
     * A published request is what professionals are bidding against. Pulling a
     * document out from under a bid already sent moves the goalposts.
     */
    public function test_a_file_cannot_be_removed_once_the_request_is_published(): void
    {
        $client = $this->client();
        $this->upload($client);
        $attachment = RequestAttachment::first();

        $event = Event::create([
            'client_id'    => $client->id,
            'created_by'   => $client->id,
            'title'        => 'Charity gala',
            'status'       => 'published',
            'is_published' => true,
        ]);
        $attachment->update(['event_id' => $event->id, 'draft_key' => null]);

        $this->actingAs($client)
            ->deleteJson(route('client.request-files.destroy', $attachment))
            ->assertStatus(422);

        $this->assertSame(1, RequestAttachment::count());
    }

    public function test_at_most_ten_files_can_be_attached(): void
    {
        $client = $this->client();

        for ($i = 0; $i < 10; $i++) {
            $this->upload($client)->assertCreated();
        }

        $this->upload($client)->assertStatus(422);
        $this->assertSame(10, RequestAttachment::count());
    }

    /**
     * The adoption step. The wizard holds its state in the session, so the
     * file is uploaded before the Event exists — publishing is where the two
     * are joined, and without this the file would be orphaned.
     */
    public function test_files_uploaded_in_the_wizard_belong_to_the_request_once_it_is_published(): void
    {
        $client = $this->client();
        $this->completeThroughProposals($client);

        $key = $this->keyFromWizard($client);
        $this->upload($client, $key)->assertCreated();

        $this->assertNull(RequestAttachment::first()->event_id);

        $this->actingAs($client)->post(route('client.bsr.save', 'files'));
        $this->actingAs($client)->post(route('client.bsr.save', 'availability'));
        $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);

        $event = Event::where('client_id', $client->id)->latest('id')->first();
        $attachment = RequestAttachment::first()->fresh();

        $this->assertNotNull($event);
        $this->assertSame($event->id, $attachment->event_id);
        $this->assertNull($attachment->draft_key);
    }

    // ── helpers ──────────────────────────────────────────────

    /** One event type and one bookable service under a category. */
    private function taxonomy(): array
    {
        $v2 = config('taxonomy.version', 'v1') === 'v2';

        $type = Category::create([
            'name'      => 'Charity Event',
            'slug'      => 'charity-event',
            'is_active' => true,
        ] + ($v2 ? ['kind' => Category::EVENT_TYPE] : ['parent_id' => null]));

        $group = Category::create([
            'name'      => 'Catering & Food Services',
            'slug'      => 'catering-food-services',
            'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE_CATEGORY] : ['parent_id' => null]));

        $service = Category::create([
            'name'      => 'Full-Service Catering',
            'slug'      => 'full-service-catering',
            'parent_id' => $group->id,
            'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE] : []));

        return [$type, $service];
    }

    /** The wizard's own files token, read off the rendered step. */
    private function keyFromWizard(User $client): string
    {
        $html = $this->actingAs($client)->get(route('client.bsr.step', 'files'))->getContent();

        preg_match('/data-key="([^"]+)"/', $html, $m);

        return $m[1] ?? '';
    }

    /**
     * Steps 1-5, so step 6 is reachable.
     *
     * Two categories are built here rather than seeding the whole 360-row
     * taxonomy: this file is about files, and a test that depends on the live
     * catalogue fails the day someone renames a service.
     */
    private function completeThroughProposals(User $client): void
    {
        [$type, $service] = $this->taxonomy();

        $this->actingAs($client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$service->id],
            'event_type'        => $type->name,
            'organization_type' => 'individual',
            'characteristic'    => 'standard',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'event'), [
            'title'       => 'Charity gala',
            'starts_at'   => now()->addDays(45)->format('Y-m-d H:i'),
            'guest_count' => 100,
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'requirements'), [
            'description' => 'Catering for one hundred guests with vegetarian options and staff for four hours.',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'budget'), [
            'budget_min' => 3000,
            'budget_max' => 6000,
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'proposals'), [
            'proposal_deadline' => now()->addDays(20)->format('Y-m-d H:i'),
        ]);
    }
}
