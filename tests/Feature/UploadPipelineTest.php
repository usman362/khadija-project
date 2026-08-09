<?php

namespace Tests\Feature;

use App\Domain\Uploads\Contracts\MalwareScanner;
use App\Domain\Uploads\Scanners\UnavailableScanner;
use App\Domain\Uploads\UploadPipeline;
use App\Models\UploadedFile as FileRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Rule R54 ("CSR-001") — every file uploaded anywhere passes one pipeline:
 * quarantine → validate → scan → decide → store → audit → retention, and
 * "no feature may build its own separate upload path".
 *
 * And Rule R55 — event photographs contain children, which is NOT a reason to
 * refuse them. The uploader carries responsibility for rights and permission
 * including a guardian's; GigResource keeps the power to remove regardless of
 * any consent claimed.
 *
 * The finding that made this urgent: verification documents — trade licences,
 * insurance certificates, workers' comp — and clients' event photographs were
 * written to the PUBLIC disk. Their path was the only thing between them and
 * anyone who guessed it.
 *
 * No malware vendor is chosen (Open Decisions row 39), so the scan stage is
 * behind a contract and the bound scanner reports NOT SCANNED. Several tests
 * below exist only to make sure that never quietly becomes "clean".
 */
class UploadPipelineTest extends TestCase
{
    use RefreshDatabase;

    private UploadPipeline $pipeline;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');

        $this->pipeline = app(UploadPipeline::class);
        $this->user = User::factory()->create();
    }

    private function image(string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 40, 40);
    }

    /* ── Quarantine ────────────────────────────────────────── */

    public function test_a_file_lands_in_private_quarantine_before_anything_looks_at_it(): void
    {
        // Order matters: inspecting a temp file and then moving it means the
        // thing examined and the thing stored are not provably the same bytes.
        $record = $this->pipeline->accept($this->image(), 'verification', $this->user);

        $this->assertSame('private', $record->disk);
        // Still in quarantine: a held file is promoted only when released.
        $this->assertStringStartsWith('quarantine/', $record->path);
        Storage::disk('private')->assertExists($record->path);
        Storage::disk('public')->assertMissing($record->path);
    }

    public function test_the_uploaders_filename_is_never_used_on_disk(): void
    {
        // It is attacker-controlled text. Kept on the record for display,
        // never in a path.
        $record = $this->pipeline->accept($this->image('../../etc/passwd.jpg'), 'avatar', $this->user);

        $this->assertStringNotContainsString('passwd', $record->path);
        $this->assertStringContainsString('passwd', $record->original_name);
    }

    /* ── Validation ────────────────────────────────────────── */

    public function test_a_file_whose_contents_do_not_match_its_name_is_rejected(): void
    {
        // The classic hole: a script named .jpg passes an extension check, and
        // a file lying about its MIME passes a MIME check. Both are required
        // AND they have to agree.
        $record = $this->pipeline->accept(
            UploadedFile::fake()->createWithContent('script.jpg', '<?php echo "hi";'),
            'avatar',
            $this->user,
        );

        $this->assertSame(FileRecord::REJECTED, $record->status);
    }

    public function test_a_type_outside_the_purposes_allowlist_is_rejected(): void
    {
        // A PDF is fine as a verification document and wrong as an avatar.
        $pdf = UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf');

        $this->assertSame(FileRecord::REJECTED, $this->pipeline->accept($pdf, 'avatar', $this->user)->status);
    }

    public function test_a_rejected_file_leaves_no_bytes_behind(): void
    {
        $record = $this->pipeline->accept(
            UploadedFile::fake()->createWithContent('script.jpg', '<?php'),
            'avatar',
            $this->user,
        );

        Storage::disk('private')->assertMissing($record->path);
        Storage::disk('public')->assertMissing($record->path);
        // The audit row stays: it is the half of R54 that outlives the file.
        $this->assertDatabaseHas('uploaded_files', ['id' => $record->id, 'status' => 'rejected']);
    }

    public function test_a_file_over_the_purposes_ceiling_is_rejected(): void
    {
        $big = UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf');

        $record = $this->pipeline->accept($big, 'verification', $this->user);

        $this->assertSame(FileRecord::REJECTED, $record->status);
        $this->assertStringContainsString('larger than', $record->decision_reason);
    }

    /* ── The scan we cannot do yet ─────────────────────────── */

    public function test_an_unscanned_file_is_recorded_as_unscanned_not_as_clean(): void
    {
        // The single most important assertion here. If a malicious file is
        // ever traced back to this row, it has to say we never looked — not
        // imply we looked and were satisfied.
        $record = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $this->assertSame(MalwareScanner::NOT_SCANNED, $record->scan_status);
        $this->assertNotSame(MalwareScanner::CLEAN, $record->scan_status);
        $this->assertSame('none', $record->scanner);
    }

    public function test_the_default_scanner_never_reports_clean(): void
    {
        $this->assertSame(
            MalwareScanner::NOT_SCANNED,
            (new UnavailableScanner)->scan('private', 'anything')['status'],
        );
    }

    public function test_an_infected_file_is_rejected_when_a_scanner_says_so(): void
    {
        $this->swap(MalwareScanner::class, new class implements MalwareScanner {
            public function scan(string $disk, string $path): array
            {
                return ['status' => self::INFECTED, 'scanner' => 'test', 'detail' => 'EICAR'];
            }
        });

        $record = app(UploadPipeline::class)->accept($this->image(), 'avatar', $this->user);

        $this->assertSame(FileRecord::REJECTED, $record->status);
    }

    /* ── The decision ──────────────────────────────────────── */

    public function test_a_sensitive_purpose_holds_an_unscanned_file_for_a_person(): void
    {
        // Verification documents already went to an admin queue for approval,
        // so holding them costs nothing and means the "Manual Review" branch
        // of the decision engine is real rather than decorative.
        $record = $this->pipeline->accept($this->image(), 'verification', $this->user);

        $this->assertSame(FileRecord::MANUAL_REVIEW, $record->status);
        $this->assertTrue(config('uploads.purposes.verification.holds_for_review'));
    }

    public function test_a_low_risk_purpose_releases_it_and_still_records_the_truth(): void
    {
        // Holding everything would stop a professional changing their picture
        // until an admin woke up. The audit row still says it was not scanned.
        $record = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $this->assertSame(FileRecord::APPROVED, $record->status);
        $this->assertSame(MalwareScanner::NOT_SCANNED, $record->scan_status);
    }

    public function test_an_approved_file_moves_to_the_disk_its_purpose_uses(): void
    {
        $record = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $this->assertSame('public', $record->disk);
        Storage::disk('public')->assertExists($record->path);
    }

    public function test_releasing_a_held_file_keeps_it_private(): void
    {
        $record = $this->pipeline->accept($this->image(), 'verification', $this->user);

        $released = $this->pipeline->release($record);

        $this->assertSame(FileRecord::APPROVED, $released->status);
        $this->assertSame('private', $released->disk);
        Storage::disk('public')->assertMissing($released->path);
    }

    /* ── Access control and download protection ────────────── */

    public function test_a_verification_document_is_not_readable_by_a_stranger(): void
    {
        // This is the leak the rule closes: these were on the public disk.
        $record = $this->pipeline->release(
            $this->pipeline->accept($this->image(), 'verification', $this->user)
        );

        $this->actingAs(User::factory()->create())
            ->get(route('uploads.show', $record))
            ->assertForbidden();
    }

    public function test_a_signed_out_visitor_gets_nothing(): void
    {
        // Sent to sign in rather than shown the file. The point is that the
        // path alone is not a key, which is exactly what it was while these
        // documents sat on the public disk.
        $record = $this->pipeline->release(
            $this->pipeline->accept($this->image(), 'verification', $this->user)
        );

        $this->get(route('uploads.show', $record))->assertRedirect(route('login'));
    }

    public function test_the_uploader_can_read_their_own_file(): void
    {
        $record = $this->pipeline->release(
            $this->pipeline->accept($this->image(), 'verification', $this->user)
        );

        $this->actingAs($this->user)->get(route('uploads.show', $record))->assertSuccessful();
    }

    public function test_a_file_still_awaiting_a_decision_is_served_to_nobody(): void
    {
        // Not even its owner. It has not passed the pipeline.
        $record = $this->pipeline->accept($this->image(), 'verification', $this->user);

        $this->actingAs($this->user)->get(route('uploads.show', $record))->assertNotFound();
    }

    /* ── Audit and retention ───────────────────────────────── */

    public function test_every_upload_leaves_a_row_with_who_what_and_when(): void
    {
        $record = $this->pipeline->accept($this->image('mine.jpg'), 'avatar', $this->user);

        $this->assertSame($this->user->id, $record->user_id);
        $this->assertSame('mine.jpg', $record->original_name);
        $this->assertSame('avatar', $record->purpose);
        $this->assertNotNull($record->checksum);
        $this->assertNotNull($record->created_at);
    }

    public function test_a_purpose_with_a_retention_period_gets_an_expiry(): void
    {
        $held = $this->pipeline->accept($this->image(), 'verification', $this->user);
        $avatar = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $this->assertNotNull($held->retain_until);
        // An avatar lives as long as the account, so it has no expiry.
        $this->assertNull($avatar->retain_until);
    }

    public function test_an_unknown_purpose_is_a_developer_error_not_a_user_one(): void
    {
        // R54 forbids separate upload paths outright, so a purpose missing
        // from the config means someone added an upload without going through
        // the rule — that should stop the build, not return a 422.
        $this->expectException(\RuntimeException::class);

        $this->pipeline->accept($this->image(), 'some_new_feature', $this->user);
    }

    /* ── Rule R55 ──────────────────────────────────────────── */

    public function test_an_event_photograph_is_accepted_not_refused(): void
    {
        // R55's actual content. Event photos contain children and that is not
        // grounds for rejection — a pipeline that blocked them would be the
        // blanket prohibition the rule exists to prevent.
        $record = $this->pipeline->accept($this->image(), 'event_media', $this->user, rightsAttested: true);

        $this->assertNotSame(FileRecord::REJECTED, $record->status);
    }

    public function test_the_attestation_is_stored_with_the_wording_shown(): void
    {
        // "They ticked a box" is worth little; "they ticked THIS box" is
        // evidence. The wording can change, so it is kept per file.
        $record = $this->pipeline->accept($this->image(), 'event_media', $this->user, rightsAttested: true);

        $this->assertTrue($record->rights_attested);
        $this->assertSame(config('uploads.minors.attestation'), $record->attestation_text);
        $this->assertStringContainsString('parent or guardian', $record->attestation_text);
    }

    public function test_no_attestation_recorded_when_none_was_given(): void
    {
        $record = $this->pipeline->accept($this->image(), 'event_media', $this->user);

        $this->assertFalse($record->rights_attested);
        $this->assertNull($record->attestation_text);
    }

    public function test_an_admin_can_remove_a_file_whatever_was_attested(): void
    {
        // R55: removable "regardless of consent claimed".
        $admin = User::factory()->create();
        $record = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $this->assertTrue($record->rights_attested === false);
        $record->removeBy($admin, 'Breaches the privacy policy.');

        $this->assertSame(FileRecord::REMOVED, $record->fresh()->status);
        Storage::disk('public')->assertMissing($record->path);
    }

    public function test_removal_deletes_the_file_and_keeps_the_record(): void
    {
        // An audit log that erases itself answers nothing later.
        $admin = User::factory()->create();
        $record = $this->pipeline->accept($this->image(), 'avatar', $this->user);

        $record->removeBy($admin, 'Reported by a client.');

        $this->assertDatabaseHas('uploaded_files', [
            'id' => $record->id,
            'removed_by' => $admin->id,
            'removal_reason' => 'Reported by a client.',
        ]);
    }

    public function test_a_removed_file_is_no_longer_served(): void
    {
        $admin = User::factory()->create();
        $record = $this->pipeline->release(
            $this->pipeline->accept($this->image(), 'verification', $this->user)
        );

        $record->removeBy($admin, 'Wrong document.');

        $this->actingAs($this->user)->get(route('uploads.show', $record))->assertNotFound();
    }
}
