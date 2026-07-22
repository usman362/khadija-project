<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A professional's payout (withdrawal of earned funds). The payout ledger —
 * see the migration for how it relates to escrow.
 */
class Payout extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'currency', 'method', 'status', 'reference', 'note',
        'requested_at', 'paid_at',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'requested_at' => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /** Requested or paid — anything that draws against the balance. */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['requested', 'paid']);
    }
}
