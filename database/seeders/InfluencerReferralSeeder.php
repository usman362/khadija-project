<?php

namespace Database\Seeders;

use App\Domain\Influencer\Enums\CommissionTier;
use App\Domain\Influencer\Enums\ReferralStatus;
use App\Domain\Influencer\Enums\ReferralType;
use App\Models\Influencer;
use App\Models\InfluencerReferral;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic referral rows for demo influencers so the Referral Center,
 * Commissions, and Leaderboards portal pages are populated from real data.
 * Idempotent — clears this influencer's referrals and recomputes aggregates.
 */
class InfluencerReferralSeeder extends Seeder
{
    public function run(): void
    {
        // [influencer_id => how many referrals] — gives the leaderboard a clear ranking.
        $plan = [1 => 30, 2 => 14];

        foreach ($plan as $influencerId => $count) {
            $influencer = Influencer::find($influencerId);
            if (! $influencer) {
                continue;
            }

            InfluencerReferral::where('influencer_id', $influencerId)->delete();

            $rate = $influencer->commission_tier?->rate() ?? 5.0;

            for ($n = 0; $n < $count; $n++) {
                $isBooking = $n % 3 !== 0; // ~2/3 booking commissions, ~1/3 signup bonuses
                $status = $this->pickStatus($n, $count);
                $base = $isBooking ? rand(150, 900) : 0;
                $commission = $isBooking ? round($base * $rate / 100, 2) : (float) config('influencer.signup_bonus', 5);

                InfluencerReferral::create([
                    'influencer_id' => $influencerId,
                    'referred_user_id' => null,
                    'booking_id' => null,
                    'type' => $isBooking ? ReferralType::BOOKING_COMMISSION : ReferralType::SIGNUP_BONUS,
                    'base_amount' => $base,
                    'commission_rate' => $isBooking ? $rate : 0,
                    'commission_amount' => $commission,
                    'status' => $status,
                    'source' => ['social', 'email', 'website', 'direct'][$n % 4],
                    'created_at' => now()->subDays(rand(1, 175)),
                    'updated_at' => now(),
                ]);
            }

            $this->recomputeAggregates($influencer);
        }
    }

    private function pickStatus(int $n, int $count): ReferralStatus
    {
        $r = $n % 20;
        return match (true) {
            $r < 11 => ReferralStatus::PAID,      // 55%
            $r < 16 => ReferralStatus::EARNED,    // 25%
            $r < 19 => ReferralStatus::PENDING,   // 15%
            default => ReferralStatus::CANCELLED, // 5%
        };
    }

    private function recomputeAggregates(Influencer $influencer): void
    {
        $rows = InfluencerReferral::where('influencer_id', $influencer->id)->get();

        $earnedPaid = $rows->whereIn('status', [ReferralStatus::EARNED, ReferralStatus::PAID]);
        $totalReferrals = $rows->where('status', '!=', ReferralStatus::CANCELLED)->count();

        $influencer->total_referrals = $totalReferrals;
        $influencer->total_earnings = round($earnedPaid->sum('commission_amount'), 2);
        $influencer->available_balance = round($rows->where('status', ReferralStatus::EARNED)->sum('commission_amount'), 2);
        $influencer->paid_out = round($rows->where('status', ReferralStatus::PAID)->sum('commission_amount'), 2);
        $influencer->commission_tier = $this->tierFor($totalReferrals);
        $influencer->save();
    }

    private function tierFor(int $referrals): CommissionTier
    {
        $tiers = collect(config('influencer.tiers'))
            ->sortByDesc('min_referrals');

        foreach ($tiers as $key => $cfg) {
            if ($referrals >= ($cfg['min_referrals'] ?? 0)) {
                return CommissionTier::from($key);
            }
        }

        return CommissionTier::from('starter');
    }
}
