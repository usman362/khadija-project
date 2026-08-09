<?php

namespace App\Models;

use App\Domain\Disputes\DisputePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R34 §7 — a staff member holding a case in a particular role.
 *
 * A row per assignment rather than a column on the case, because a case
 * passes through several people (intake, then an investigator, then a senior
 * reviewer) and "who had this case in March" is a question the audit trail
 * has to be able to answer after they have handed it on.
 */
class DisputeAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dispute_case_id', 'staff_id', 'role',
        'conflict_disclosed', 'conflict_detail', 'assigned_at', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'conflict_disclosed' => 'boolean',
            'assigned_at'        => 'datetime',
            'released_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $assignment) {
            $assignment->assigned_at ??= now();
        });
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }

    public function roleLabel(): string
    {
        return DisputePermissions::STAFF_ROLES[$this->role] ?? $this->role;
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
