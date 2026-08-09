<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R34 §2 Step 4 — an outside escalation, or a payment provider acting
 * under its own rules (§8).
 *
 * Both land here because both are the same thing structurally: an outcome
 * GigResource records rather than reaches. Keeping them out of the decisions
 * table is what stops a chargeback from reading, later, like a platform
 * ruling — and §8 is explicit that a platform decision does not silently
 * override what the processor already did.
 */
class DisputeEscalation extends Model
{
    protected $fillable = [
        'dispute_case_id', 'requested_by', 'requested_at', 'provider',
        'external_reference', 'outcome_summary', 'concluded_at',
        'payment_provider_initiated',
    ];

    protected function casts(): array
    {
        return [
            'requested_at'               => 'datetime',
            'concluded_at'               => 'datetime',
            'payment_provider_initiated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $escalation) {
            $escalation->requested_at ??= now();
        });
    }

    public function isConcluded(): bool
    {
        return $this->concluded_at !== null;
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
