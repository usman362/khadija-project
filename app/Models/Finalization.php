<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The step-by-step agreement between a client and the professional they chose.
 * See the migration for why each step records who completed it and when.
 */
class Finalization extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'bid_reviewed_at'    => 'datetime',
        'scope_agreed_at'    => 'datetime',
        'price_agreed_at'    => 'datetime',
        'schedule_agreed_at' => 'datetime',
        'terms_agreed_at'    => 'datetime',
        'client_signed_at'   => 'datetime',
        'supplier_signed_at' => 'datetime',
        'funded_at'          => 'datetime',
        'service_start'      => 'datetime',
        'service_end'        => 'datetime',
        'balance_due_on'     => 'date',
        'agreed_price'       => 'decimal:2',
        'deposit_amount'     => 'decimal:2',
    ];

    public function event(): BelongsTo    { return $this->belongsTo(Event::class); }
    public function bid(): BelongsTo      { return $this->belongsTo(Bid::class); }
    public function category(): BelongsTo { return $this->belongsTo(\App\Models\Category::class); }
    public function client(): BelongsTo   { return $this->belongsTo(User::class, 'client_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(User::class, 'supplier_id'); }
    public function booking(): BelongsTo  { return $this->belongsTo(Booking::class); }
    public function payment(): BelongsTo  { return $this->belongsTo(Payment::class); }

    /** Both parties have signed the contract. */
    public function isSigned(): bool
    {
        return $this->client_signed_at && $this->supplier_signed_at;
    }

    /** Money is secured — in whichever mode the platform was running. */
    public function isFunded(): bool
    {
        return (bool) $this->funded_at;
    }

    /**
     * The step keys in order, mapped to the column that marks each one done.
     * The wizard, the checklist and the progress bar all read this, so they
     * cannot disagree about how far along a finalization is.
     */
    public const STEPS = [
        'bid'      => ['Review Bid',        'bid_reviewed_at'],
        'scope'    => ['Confirm Scope',     'scope_agreed_at'],
        'price'    => ['Price & Fees',      'price_agreed_at'],
        'schedule' => ['Schedule',          'schedule_agreed_at'],
        'terms'    => ['Deposit & Terms',   'terms_agreed_at'],
        'contract' => ['Contract',          'supplier_signed_at'],
        'payment'  => ['Secure Payment',    'funded_at'],
    ];

    public function completed(string $step): bool
    {
        $col = self::STEPS[$step][1] ?? null;

        return $col ? (bool) $this->{$col} : false;
    }
}
