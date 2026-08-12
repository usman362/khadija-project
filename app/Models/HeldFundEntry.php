<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One movement of money against a booking's held balance (row 181).
 *
 * Append-only. There is no update path here on purpose: a ledger you can edit
 * is a ledger nobody can rely on in the argument it exists for. A mistake is
 * corrected by a reversing entry, which stays visible beside the original.
 */
class HeldFundEntry extends Model
{
    /* Direction relative to the HELD balance, not to anyone's bank account. */
    public const IN  = 'in';    // money arrives into the hold
    public const OUT = 'out';   // money leaves it — to either party

    public const DEPOSIT    = 'deposit';
    public const BALANCE    = 'balance';
    public const RELEASE    = 'release';
    public const REFUND     = 'refund';
    public const COMMISSION = 'commission';
    public const ADJUSTMENT = 'adjustment';

    public const PENDING = 'pending';
    public const SETTLED = 'settled';

    /*
     * The column default exists too, but a freshly created model would not
     * know about it — so an entry read back in the same request looked like
     * it had no state at all.
     */
    protected $attributes = [
        'state'    => self::PENDING,
        'currency' => 'USD',
    ];

    protected $fillable = [
        'booking_id', 'event_id', 'kind', 'direction', 'amount', 'currency', 'reason',
        'source_type', 'source_id', 'state', 'processor_reference', 'settled_at',
        'reverses', 'recorded_by', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'occurred_at' => 'datetime',
            'settled_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            $entry->occurred_at ??= now();
            $entry->event_id    ??= $entry->booking?->event_id;
        });

        // Append-only, enforced rather than documented. The one field that may
        // change is settlement, because that is the processor answering later.
        static::updating(function (self $entry) {
            $changed = array_keys($entry->getDirty());

            if (array_diff($changed, ['state', 'processor_reference', 'settled_at', 'updated_at']) !== []) {
                throw new \RuntimeException('A held-funds entry cannot be edited. Post a reversing entry instead.');
            }
        });

        static::deleting(function () {
            throw new \RuntimeException('A held-funds entry cannot be deleted. Post a reversing entry instead.');
        });
    }

    public function isSettled(): bool
    {
        return $this->state === self::SETTLED;
    }

    /** Signed against the held balance: `in` adds, `out` takes away. */
    public function signedAmount(): float
    {
        return (float) $this->amount * ($this->direction === self::IN ? 1 : -1);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
