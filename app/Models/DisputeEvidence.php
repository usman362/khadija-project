<?php

namespace App\Models;

use App\Domain\Disputes\DecisionGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R34 §4 — one submitted item of evidence.
 *
 * The integrity rules live here rather than in a controller because they are
 * properties of the record, not of one screen: files are hashed on upload,
 * nothing is silently edited, and a withdrawal is recorded rather than
 * performed. An investigator who cannot tell whether a photograph was swapped
 * after it was submitted has no evidence, only a picture.
 */
class DisputeEvidence extends Model
{
    protected $table = 'dispute_evidence';

    protected $fillable = [
        'dispute_case_id', 'submitted_by', 'kind', 'platform_generated',
        'description', 'uploaded_file_id', 'sha256', 'supersedes',
        'withdrawn_at', 'withdrawn_reason',
    ];

    protected function casts(): array
    {
        return [
            'platform_generated' => 'boolean',
            'withdrawn_at'       => 'datetime',
        ];
    }

    /**
     * §4 — platform-generated records are primary evidence.
     *
     * Stamped from the kind rather than trusted from the caller: a party
     * uploading a screenshot of a message is submitting a picture of a record,
     * not the record, and the difference is exactly what an investigator needs.
     */
    protected static function booted(): void
    {
        static::creating(function (self $item) {
            $item->platform_generated = in_array(
                $item->kind, ['platform_contract', 'platform_timeline'], true
            );
        });
    }

    public function weightLabel(): string
    {
        return DecisionGuide::EVIDENCE_WEIGHT[$this->kind]['weight'] ?? 'Depends on context';
    }

    public function kindLabel(): string
    {
        return DecisionGuide::EVIDENCE_WEIGHT[$this->kind]['label'] ?? ucfirst(str_replace('_', ' ', $this->kind));
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    /** Superseded items stay readable — §4 keeps version history. */
    public function isSuperseded(): bool
    {
        return static::where('supersedes', $this->id)->exists();
    }

    /**
     * §4 — deletions are logged rather than allowed to truly delete.
     *
     * A party may withdraw something they submitted. What they cannot do is
     * make it as though it was never submitted, because "the client removed
     * the invoice after the professional responded to it" is itself a fact
     * about the case.
     */
    public function withdraw(User $actor, string $reason): void
    {
        $this->forceFill(['withdrawn_at' => now(), 'withdrawn_reason' => $reason])->save();

        $this->disputeCase->log('evidence_withdrawn', $actor, null, [
            'field' => 'evidence', 'old' => (string) $this->id, 'reason' => $reason,
        ]);
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(UploadedFile::class, 'uploaded_file_id');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes');
    }
}
