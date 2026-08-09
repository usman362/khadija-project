<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R34 §10 — one line of the audit trail.
 *
 * Write-once: there is no update path and no `updated_at`, because a log you
 * can edit is a log nobody can rely on in the situation it exists for.
 */
class DisputeEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'dispute_case_id', 'actor_id', 'actor_role', 'action',
        'field', 'old_value', 'new_value', 'reason', 'visible_to_parties', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_to_parties' => 'boolean',
            'created_at'         => 'datetime',
        ];
    }

    /** §7 — the parties see their case's history, not staff deliberation. */
    public function scopeVisibleToParties($query)
    {
        return $query->where('visible_to_parties', true);
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
