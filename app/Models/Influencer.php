<?php

namespace App\Models;

use App\Domain\Influencer\Enums\CommissionTier;
use App\Domain\Influencer\Enums\InfluencerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Influencer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'analytics_meta',
        'social_media_links',
        'audience_description',
        'monthly_reach',
        'followers_count',
        'engagement_rate',
        'profile_score',
        'referral_code',
        'commission_tier',
        'status',
        'total_earnings',
        'available_balance',
        'paid_out',
        'total_referrals',
        'admin_notes',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'social_media_links' => 'array',
            'monthly_reach' => 'integer',
            'followers_count' => 'integer',
            'engagement_rate' => 'decimal:2',
            'profile_score' => 'integer',
            'total_earnings' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'paid_out' => 'decimal:2',
            'total_referrals' => 'integer',
            'status' => InfluencerStatus::class,
            'commission_tier' => CommissionTier::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'analytics_meta' => 'array',
        ];
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(InfluencerReferral::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(InfluencerPayoutRequest::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(InfluencerCampaign::class);
    }

    public function content(): HasMany
    {
        return $this->hasMany(InfluencerContent::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(InfluencerDailyStat::class);
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_influencer_id');
    }

    public function isApproved(): bool
    {
        return $this->status === InfluencerStatus::APPROVED;
    }

    public function referralUrl(): string
    {
        return url('/ref/' . $this->referral_code);
    }
}
