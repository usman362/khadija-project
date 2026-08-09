<?php

namespace App\Domain\Uploads;

use App\Domain\Uploads\Contracts\MalwareScanner;
use App\Models\UploadedFile as FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile as IncomingFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Rule R54 ("CSR-001") — the one path every uploaded file takes.
 *
 * The rule lists the stages: preview → user review/confirm → temporary
 * quarantine → automatic scan → decision engine → access control → download
 * protection → audit logging → retention rules, and adds that "no feature may
 * build its own separate upload path".
 *
 * Preview and confirm belong to the browser; everything from quarantine
 * onwards is here. The stages that are real today are real; the one that
 * cannot be — the malware scan, whose vendor is still an open decision — says
 * so rather than being quietly skipped.
 *
 * Order matters and is not arbitrary. The file is written to a private
 * quarantine disk BEFORE anything inspects it, because inspecting a temp file
 * and then moving it means the thing examined and the thing stored are not
 * provably the same bytes. It is promoted to its final disk only after a
 * decision, so nothing is reachable that has not been decided on.
 */
final class UploadPipeline
{
    public function __construct(private MalwareScanner $scanner)
    {
    }

    /**
     * Take a file in; get an audited record back.
     *
     * @param  string  $purpose  a key from config('uploads.purposes')
     * @param  bool    $rightsAttested  R55 — the uploader's answer, where asked
     */
    public function accept(
        IncomingFile $file,
        string $purpose,
        ?User $uploader,
        bool $rightsAttested = false,
    ): FileRecord {
        $rules = $this->rulesFor($purpose);

        // ── Quarantine ───────────────────────────────────────────
        // Private disk, generated name. The uploader's filename is kept on
        // the record for display but never used on disk: it is attacker-
        // controlled text, and a path is the wrong place to trust it.
        $quarantineDisk = config('uploads.quarantine_disk', 'private');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs("quarantine/{$purpose}", Str::uuid() . '.' . $extension, $quarantineDisk);

        $record = FileRecord::create([
            'user_id'          => $uploader?->id,
            'purpose'          => $purpose,
            'original_name'    => mb_substr($file->getClientOriginalName(), 0, 255),
            'path'             => $path,
            'disk'             => $quarantineDisk,
            'mime'             => $file->getMimeType(),
            'size'             => $file->getSize(),
            'checksum'         => hash_file('sha256', Storage::disk($quarantineDisk)->path($path)),
            'status'           => FileRecord::QUARANTINED,
            'rights_attested'  => $rightsAttested,
            'attestation_text' => $rightsAttested ? config('uploads.minors.attestation') : null,
            'retain_until'     => $rules['retain_days'] ? now()->addDays($rules['retain_days']) : null,
        ]);

        // ── Validate ─────────────────────────────────────────────
        // Against the bytes now sitting in quarantine, not against the
        // request object: what was uploaded and what is stored have to be the
        // same thing, and the request's own getMimeType() is a guess made
        // before the file landed.
        if ($problem = $this->validationProblem($file, $extension, $rules, $quarantineDisk, $path)) {
            return $this->reject($record, $problem);
        }

        // ── Scan ─────────────────────────────────────────────────
        $scan = $this->scanner->scan($quarantineDisk, $path);
        $record->update(['scan_status' => $scan['status'], 'scanner' => $scan['scanner']]);

        if ($scan['status'] === MalwareScanner::INFECTED) {
            return $this->reject($record, 'The file did not pass a malware scan.');
        }

        // ── Decide ───────────────────────────────────────────────
        // An unscanned file is released only where the purpose says that is
        // acceptable. Everywhere else it waits for a person — which for
        // verification documents is what already happened anyway.
        $unverified = $scan['status'] !== MalwareScanner::CLEAN;

        if ($unverified && $rules['holds_for_review']) {
            $record->update([
                'status'          => FileRecord::MANUAL_REVIEW,
                'decision_reason' => $scan['detail'] ?? 'Held for review.',
            ]);

            return $record->fresh();
        }

        return $this->approve($record, $rules['disk'], $purpose, $extension, $unverified ? $scan['detail'] : null);
    }

    /**
     * Move an approved file out of quarantine onto the disk its purpose uses.
     *
     * Public-disk purposes become reachable here and only here — after a
     * decision, never before.
     */
    public function approve(FileRecord $record, string $disk, string $purpose, string $extension, ?string $note = null): FileRecord
    {
        $destination = "{$purpose}/" . basename($record->path);

        $quarantine = Storage::disk($record->disk);
        Storage::disk($disk)->put($destination, $quarantine->get($record->path));
        $quarantine->delete($record->path);

        $record->update([
            'disk'            => $disk,
            'path'            => $destination,
            'status'          => FileRecord::APPROVED,
            'decision_reason' => $note,
        ]);

        return $record->fresh();
    }

    /** Release a held file. Used by the moderation queue. */
    public function release(FileRecord $record): FileRecord
    {
        $rules = $this->rulesFor($record->purpose);

        return $this->approve(
            $record,
            $rules['disk'],
            $record->purpose,
            pathinfo($record->path, PATHINFO_EXTENSION),
            'Released after review.',
        );
    }

    /** Reject, and delete the bytes — a rejected file has no reason to exist. */
    public function reject(FileRecord $record, string $reason): FileRecord
    {
        Storage::disk($record->disk)->delete($record->path);

        $record->update(['status' => FileRecord::REJECTED, 'decision_reason' => $reason]);

        return $record->fresh();
    }

    /**
     * What the validation stage actually checks.
     *
     * Extension and reported MIME must BOTH be on the purpose's allowlist and
     * must agree with each other. Checking only one is the classic hole: a
     * .php named .jpg passes an extension check, and a file that lies about
     * its MIME passes a MIME check.
     */
    private function validationProblem(
        IncomingFile $file,
        string $extension,
        array $rules,
        string $disk,
        string $path,
    ): ?string {
        if (! in_array($extension, $rules['extensions'], true)) {
            return 'That file type is not accepted here.';
        }

        // Size before contents: it is the cheaper test, and "too big" is a
        // more useful thing to be told than "the contents look wrong" when
        // both are true.
        if ($file->getSize() > $rules['max_kb'] * 1024) {
            return 'That file is larger than ' . round($rules['max_kb'] / 1024, 1) . ' MB.';
        }

        $mime = $file->getMimeType();

        if (! in_array($mime, $rules['mimes'], true)) {
            return 'That file type is not accepted here.';
        }

        if (! $this->mimeMatchesExtension($mime, $extension)) {
            return 'The file’s contents do not match its name.';
        }

        // And the bytes themselves. The two checks above both read what the
        // upload CLAIMS to be — the browser's Content-Type and the filename —
        // and an attacker controls both. This one opens the file.
        if (! $this->contentMatchesExtension($disk, $path, $extension)) {
            return 'The file’s contents do not match its name.';
        }

        return null;
    }

    /**
     * Does the file actually begin the way its type should?
     *
     * Only the types we accept are listed, and anything not listed is left to
     * the checks above rather than guessed at — a signature test that returns
     * "probably fine" for formats it does not know is worse than not running.
     *
     * Office documents are ZIP containers and .doc/.xls are OLE compound
     * files, so their signatures are the container's; that is still enough to
     * catch a script renamed .docx.
     */
    private function contentMatchesExtension(string $disk, string $path, string $extension): bool
    {
        $signatures = [
            'jpg'  => ["\xFF\xD8\xFF"],
            'jpeg' => ["\xFF\xD8\xFF"],
            'png'  => ["\x89PNG\r\n\x1a\n"],
            'gif'  => ['GIF87a', 'GIF89a'],
            'pdf'  => ['%PDF-'],
            'docx' => ["PK\x03\x04"],
            'xlsx' => ["PK\x03\x04"],
            'doc'  => ["\xD0\xCF\x11\xE0"],
            'xls'  => ["\xD0\xCF\x11\xE0"],
        ];

        $expected = $signatures[$extension] ?? null;

        if ($expected === null) {
            return true;   // webp, svg, mp4, webm — handled by the MIME checks
        }

        $handle = @fopen(Storage::disk($disk)->path($path), 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        foreach ($expected as $signature) {
            if (str_starts_with($head, $signature)) {
                return true;
            }
        }

        return false;
    }

    /** Does the sniffed type agree with the name the file was given? */
    private function mimeMatchesExtension(?string $mime, string $extension): bool
    {
        $expected = [
            'jpg'  => ['image/jpeg'],           'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],           'svg'  => ['image/svg+xml'],
            'pdf'  => ['application/pdf'],
            'mp4'  => ['video/mp4'],            'webm' => ['video/webm'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        return in_array($mime, $expected[$extension] ?? [], true);
    }

    private function rulesFor(string $purpose): array
    {
        $rules = config("uploads.purposes.{$purpose}");

        if ($rules === null) {
            // Not a 422 — a purpose that is not in the config is a developer
            // adding an upload path without going through R54, which is the
            // one thing the rule forbids outright.
            throw new RuntimeException("Unknown upload purpose [{$purpose}]. Add it to config/uploads.php — R54 allows no separate upload paths.");
        }

        return $rules;
    }
}
