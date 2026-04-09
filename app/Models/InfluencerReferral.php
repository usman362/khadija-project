<?php

namespace App\Models;

use App\Domain\Influencer\Enums\ReferralStatus;
use App\Domain\Influencer\Enums\ReferralType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_id',
        'referred_user_id',
        'booking_id',
        'type',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'type' => ReferralType::class,
            'status' => ReferralStatus::class,
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
