<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'user_subscription_id',
        'gateway',
        'status',
        'amount',
        'currency',
        'payment_method',
        'gateway_session_id',
        'gateway_payment_id',
        'metadata',
        'failure_reason',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    // ── Status Helpers ─────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    // ── Actions ────────────────────────────────────────────

    public function markCompleted(?string $gatewayPaymentId = null, ?string $paymentMethod = null): void
    {
        $data = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($gatewayPaymentId) {
            $data['gateway_payment_id'] = $gatewayPaymentId;
        }

        if ($paymentMethod) {
            $data['payment_method'] = $paymentMethod;
        }

        $this->update($data);
    }

    public function markFailed(string $reason = ''): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function markRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }

    // ── Display ────────────────────────────────────────────

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    public function gatewayLabel(): string
    {
        return match ($this->gateway) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            default => ucfirst($this->gateway),
        };
    }
}
