<?php

namespace App\Models;

use App\Models\Concerns\ShowsTimesWhereTheReaderIs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One money event, kept for good.
 *
 * Sir Peter's rule: every charge, fee, commission, upgrade, refund and payout is
 * its own separately auditable record, and a booking total is never a field that
 * gets overwritten.
 *
 * Nothing here is ever updated once it settles. A correction is a new row
 * pointing at the one it reverses, so the history always reconciles and the
 * reason for every figure can be traced.
 */
class LedgerEntry extends Model
{
    use ShowsTimesWhereTheReaderIs;

    /** The nine records Peter asked for, one entry type each. */
    public const BOOKING_CHARGE     = 'booking_charge';
    public const PLATFORM_FEE       = 'platform_fee';
    public const COMMISSION         = 'commission';
    public const UPGRADE            = 'upgrade';
    public const PROVIDER_FEE       = 'provider_fee';
    public const TAX                = 'tax';
    public const REFUND             = 'refund';
    public const MILESTONE_FUNDING  = 'milestone_funding';
    public const MILESTONE_RELEASE  = 'milestone_release';
    public const PAYOUT             = 'payout';

    public const TYPES = [
        self::BOOKING_CHARGE    => 'Booking charge',
        self::PLATFORM_FEE      => 'Platform fee',
        self::COMMISSION        => 'Commission',
        self::UPGRADE           => 'Upgrade',
        self::PROVIDER_FEE      => 'Payment processing fee',
        self::TAX               => 'Tax',
        self::REFUND            => 'Refund',
        self::MILESTONE_FUNDING => 'Milestone funding',
        self::MILESTONE_RELEASE => 'Milestone release',
        self::PAYOUT            => 'Payout',
    ];

    /**
     * Where an entry is in its life.
     *
     * `secured` is the one that matters: money the platform can rely on,
     * because the gateway has it. `pending` is money asked for and not yet
     * received, and totalling the two together is how a dashboard comes to
     * promise a balance nobody can withdraw.
     */
    public const PENDING  = 'pending';
    public const SECURED  = 'secured';
    public const RELEASED = 'released';
    public const REFUNDED = 'refunded';
    public const FAILED   = 'failed';

    public const STATES = [
        self::PENDING  => 'Pending',
        self::SECURED  => 'Secured',
        self::RELEASED => 'Released',
        self::REFUNDED => 'Refunded',
        self::FAILED   => 'Failed',
    ];

    protected $fillable = [
        'event_id', 'booking_id', 'party_user_id', 'entry_type',
        'amount', 'sign', 'currency', 'state',
        'subject_type', 'subject_id', 'payment_id', 'reverses_id',
        'metadata', 'occurred_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'metadata'    => 'array',
        'occurred_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(User::class, 'party_user_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** The entry this one corrects, if it is a correction. */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** What this is called on a statement. */
    public function label(): string
    {
        return self::TYPES[$this->entry_type] ?? ucfirst(str_replace('_', ' ', $this->entry_type));
    }

    /**
     * The amount with its direction applied.
     *
     * Stored positive on purpose: a negative number in the amount column is one
     * careless SUM away from cancelling out a charge it was never meant to
     * touch.
     */
    public function signed(): float
    {
        return $this->sign === '-' ? -(float) $this->amount : (float) $this->amount;
    }

    /** Has this entry been corrected by a later one? */
    public function wasReversed(): bool
    {
        return self::where('reverses_id', $this->id)->exists();
    }
}
