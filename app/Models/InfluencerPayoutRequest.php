<?php

namespace App\Models;

use App\Domain\Influencer\Enums\PayoutRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerPayoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_id',
        'amount',
        'payout_method',
        'payout_account',
        'status',
        'user_notes',
        'admin_notes',
        'processed_by',
        'processed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutRequestStatus::class,
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
