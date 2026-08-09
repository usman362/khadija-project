<?php

namespace App\Models;

use App\Domain\Disputes\DisputeClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R34 §5 — one recorded decision.
 *
 * Append-only. A revision is a new row pointing at the one it replaces, so
 * "the decision was changed after the professional complained" stays visible;
 * an updatable column would have quietly erased it.
 */
class DisputeDecision extends Model
{
    protected $fillable = [
        'dispute_case_id', 'decided_by', 'decided_role',
        'financial_outcome', 'resolution_type', 'reasoning',
        'amount_to_client', 'amount_to_professional',
        'finding_against', 'revises', 'revision_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_to_client'       => 'decimal:2',
            'amount_to_professional' => 'decimal:2',
        ];
    }

    public function financialOutcomeLabel(): ?string
    {
        return $this->financial_outcome
            ? (DisputeClassification::FINANCIAL_OUTCOMES[$this->financial_outcome] ?? $this->financial_outcome)
            : null;
    }

    public function resolutionTypeLabel(): string
    {
        return DisputeClassification::RESOLUTION_TYPES[$this->resolution_type] ?? $this->resolution_type;
    }

    /**
     * §7 — only a confirmed outcome may reach trust or risk systems.
     *
     * Asked of the decision rather than of the case, because a case that was
     * withdrawn or closed administratively has an outcome too, and neither
     * says anything about whether the accusation was true.
     */
    public function mayInfluenceTrust(): bool
    {
        return DisputeClassification::mayInfluenceTrust($this->resolution_type);
    }

    public function isRevision(): bool
    {
        return $this->revises !== null;
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function revised(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revises');
    }

    public function findingAgainst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finding_against');
    }
}
