<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rule R33 — one reactivation of a listing: the free grace reopen, or a paid
 * extension.
 *
 * Both live in one table because they are the same event in the listing's
 * life and because §2's cap is defined against one of them and not the other.
 * Keeping them apart would mean two places to look when answering "how many
 * extensions has this client had", and the cap is the answer to that question.
 */
class EventExtension extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'event_id', 'user_id', 'days', 'is_grace',
        'amount', 'currency', 'gateway', 'gateway_session_id', 'gateway_payment_id',
        'status', 'failure_reason', 'previous_deadline', 'new_deadline', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_grace'          => 'boolean',
            'amount'            => 'decimal:2',
            'days'              => 'integer',
            'previous_deadline' => 'datetime',
            'new_deadline'      => 'datetime',
            'completed_at'      => 'datetime',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
