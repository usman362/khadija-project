<?php

namespace App\Models;

use App\Domain\Cancellations\CancellationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Checklist row 155 — a cancellation, from either side.
 *
 * One model for both directions. A client cancelling and a professional
 * reporting a no-show on the same booking are two accounts of the same
 * morning, and the only useful way to read either is next to the other.
 */
class CancellationRequest extends Model
{
    /* What is being reported. One vocabulary for both directions. */
    public const CLIENT_CANCELS       = 'client_cancels';
    public const CLIENT_NO_SHOW       = 'client_no_show';
    public const CLIENT_CANCELLED_ON_DAY = 'client_cancelled_on_day';
    public const CLIENT_REFUSED_ACCESS   = 'client_refused_access';
    public const SCOPE_CHANGED_ON_ARRIVAL = 'scope_changed_on_arrival';

    public const KINDS = [
        self::CLIENT_CANCELS           => 'I need to cancel this booking',
        self::CLIENT_NO_SHOW           => 'The client did not turn up',
        self::CLIENT_CANCELLED_ON_DAY  => 'The client cancelled on the day',
        self::CLIENT_REFUSED_ACCESS    => 'I could not get access to the venue',
        self::SCOPE_CHANGED_ON_ARRIVAL => 'The job was not what was agreed when I arrived',
    ];

    /** Only the client raises this one; the rest are the professional's. */
    public const CLIENT_KINDS = [self::CLIENT_CANCELS];

    protected $fillable = [
        'booking_id', 'event_id', 'raised_by', 'raised_role', 'kind', 'reason', 'detail',
        'occurred_at', 'waited_minutes',
        'quoted_agreed', 'quoted_deposit', 'quoted_balance', 'quoted_refund', 'quoted_tier', 'days_before',
        'status', 'resolution_note', 'actioned_by', 'actioned_at', 'dispute_case_id',
        'certified', 'certification_text',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at'    => 'datetime',
            'actioned_at'    => 'datetime',
            'certified'      => 'boolean',
            'quoted_agreed'  => 'decimal:2',
            'quoted_deposit' => 'decimal:2',
            'quoted_balance' => 'decimal:2',
            'quoted_refund'  => 'decimal:2',
            'days_before'    => 'integer',
            'waited_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->reference ??= self::nextReference();
            $request->event_id  ??= $request->booking?->event_id;
        });
    }

    /** CR-YYYY-NNNNNN, one global sequence — the same shape as a dispute. */
    public static function nextReference(): string
    {
        $last = static::query()->orderByDesc('id')->lockForUpdate()->value('reference');

        return sprintf('CR-%s-%06d', now()->year, $last ? ((int) substr($last, -6)) + 1 : 1);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    /**
     * Does this one carry money terms?
     *
     * Only a client cancellation does. A professional's no-show report is a
     * report: the policy puts professional-side money out of scope with no
     * spec written, and quoting a figure against no rule would be inventing
     * one.
     */
    public function hasQuote(): bool
    {
        return $this->kind === self::CLIENT_CANCELS && $this->quoted_agreed !== null;
    }

    /** The deposit is never part of a refund. Stated here so views cannot forget. */
    public function depositRefunded(): bool
    {
        return false;
    }

    public function policyTierLabel(): string
    {
        return $this->quoted_tier ?: CancellationPolicy::tierFor($this->days_before)['label'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function disputeCase(): BelongsTo
    {
        return $this->belongsTo(DisputeCase::class);
    }
}
