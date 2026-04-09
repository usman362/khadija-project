<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReactivationPayment extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'gateway',
        'gateway_session_id',
        'gateway_payment_id',
        'status',
        'failure_reason',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'metadata'     => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function isCompleted(): bool  { return $this->status === self::STATUS_COMPLETED; }
    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isProcessing(): bool { return $this->status === self::STATUS_PROCESSING; }
    public function isFailed(): bool     { return $this->status === self::STATUS_FAILED; }
}
