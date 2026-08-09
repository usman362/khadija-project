<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Rule R54 — one row per file uploaded anywhere on GigResource.
 *
 * Before this, a file's only record was a path in some other table's column,
 * which made the rule's audit-logging and retention stages impossible to
 * build: nothing knew when a file arrived, who uploaded it, whether anyone
 * had looked at it, or when it should go.
 */
class UploadedFile extends Model
{
    public const QUARANTINED   = 'quarantined';
    public const APPROVED      = 'approved';
    public const REJECTED      = 'rejected';
    public const MANUAL_REVIEW = 'manual_review';
    public const REMOVED       = 'removed';

    protected $fillable = [
        'user_id', 'purpose', 'original_name', 'path', 'disk', 'mime', 'size', 'checksum',
        'status', 'scan_status', 'scanner', 'decision_reason',
        'rights_attested', 'attestation_text',
        'removed_by', 'removed_at', 'removal_reason', 'retain_until',
    ];

    protected function casts(): array
    {
        return [
            'rights_attested' => 'boolean',
            'removed_at'      => 'datetime',
            'retain_until'    => 'datetime',
            'size'            => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function remover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    /** Is this file allowed to be served to anyone yet? */
    public function isReleasable(): bool
    {
        return $this->status === self::APPROVED;
    }

    /**
     * Rule R55 — GigResource may remove any image that violates its privacy,
     * safety or content policy "regardless of consent claimed".
     *
     * The file goes; the row stays. A removal that erased its own record
     * would leave nothing to answer a later complaint with, and the audit log
     * is the half of R54 that outlives the file.
     */
    public function removeBy(User $admin, string $reason): void
    {
        Storage::disk($this->disk)->delete($this->path);

        $this->update([
            'status'         => self::REMOVED,
            'removed_by'     => $admin->id,
            'removed_at'     => now(),
            'removal_reason' => $reason,
        ]);
    }

    /** Purposes whose files are still waiting on a person. */
    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', [self::MANUAL_REVIEW, self::QUARANTINED]);
    }
}
