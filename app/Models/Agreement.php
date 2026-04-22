<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agreement extends Model
{
    protected $fillable = [
        'booking_id',
        'conversation_id',
        'generated_by',
        'title',
        'content',
        'extracted_terms',
        'status',
        'client_accepted_at',
        'supplier_accepted_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'source',
        'include_chat',
        'version',
        'ai_model_used',
        'ai_prompt_summary',
    ];

    protected function casts(): array
    {
        return [
            'extracted_terms' => 'array',
            'include_chat' => 'boolean',
            'client_accepted_at' => 'datetime',
            'supplier_accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderByDesc('version');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'expired']);
    }

    // ── Status Helpers ─────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingReview(): bool
    {
        return in_array($this->status, ['pending_review', 'client_accepted', 'supplier_accepted']);
    }

    public function isFullyAccepted(): bool
    {
        return $this->status === 'fully_accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function clientAccepted(): bool
    {
        return $this->client_accepted_at !== null;
    }

    public function supplierAccepted(): bool
    {
        return $this->supplier_accepted_at !== null;
    }

    // ── Actions ────────────────────────────────────────────

    public function acceptByClient(): void
    {
        $this->update([
            'client_accepted_at' => now(),
            'status' => $this->supplier_accepted_at
                ? 'fully_accepted'
                : 'client_accepted',
        ]);
    }

    public function acceptBySupplier(): void
    {
        $this->update([
            'supplier_accepted_at' => now(),
            'status' => $this->client_accepted_at
                ? 'fully_accepted'
                : 'supplier_accepted',
        ]);
    }

    public function reject(int $userId, string $reason = ''): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason,
        ]);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'client_accepted' => 'Client Accepted',
            'supplier_accepted' => 'Supplier Accepted',
            'fully_accepted' => 'Fully Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending_review' => 'warning',
            'client_accepted', 'supplier_accepted' => 'info',
            'fully_accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'dark',
            default => 'secondary',
        };
    }
}
