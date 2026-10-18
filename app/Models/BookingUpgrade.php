<?php

namespace App\Models;

use App\Models\Concerns\ShowsTimesWhereTheReaderIs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Staying longer, more food, a second photographer: one line each.
 *
 * This is Sir Peter's own October question answered by his own August decision.
 * He asked what happens when a client wants the professional to stay longer
 * after the fact and the professional wants paying for it. B3 already said:
 * an upgrade is an amendment to the existing booking, shown to the client as
 * one running total but recorded internally as SEPARATE line items, never
 * collapsed, at the professional's ordinary tier rate with no special fee.
 *
 * Two rules live in the status flow rather than in a comment:
 *
 *   Payment is secured BEFORE the upgrade is confirmed, so an upgrade can never
 *   be owed without the money behind it being real. That is `payment_secured`
 *   standing between `approved` and `confirmed`.
 *
 *   The client approves before anything is charged, so a professional cannot
 *   bill for an extra hour the client never agreed to.
 */
class BookingUpgrade extends Model
{
    use ShowsTimesWhereTheReaderIs;

    public const REQUESTED       = 'requested';
    public const APPROVED        = 'approved';
    public const PAYMENT_SECURED = 'payment_secured';
    public const CONFIRMED       = 'confirmed';
    public const REJECTED        = 'rejected';

    public const STATUSES = [
        self::REQUESTED       => 'Requested',
        self::APPROVED        => 'Approved, awaiting payment',
        self::PAYMENT_SECURED => 'Payment secured',
        self::CONFIRMED       => 'Confirmed',
        self::REJECTED        => 'Declined',
    ];

    protected $fillable = [
        'booking_id', 'requested_by', 'description', 'amount',
        'commission_rate', 'status', 'secured_payment_id',
        'approved_at', 'confirmed_at', 'rejected_at',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'approved_at'     => 'datetime',
        'confirmed_at'    => 'datetime',
        'rejected_at'     => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function securedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'secured_payment_id');
    }

    /** Only a confirmed upgrade counts towards what the client owes. */
    public function counts(): bool
    {
        return $this->status === self::CONFIRMED;
    }

    public function label(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /** What GigResource takes on this line, at the pro's ordinary rate. */
    public function commission(): float
    {
        return round((float) $this->amount * ((float) $this->commission_rate / 100), 2);
    }
}
