<?php

namespace App\Domain\Influencer\Enums;

enum CommissionTier: string
{
    case STARTER = 'starter';
    case RISING = 'rising';
    case PRO = 'pro';
    case ELITE = 'elite';

    public function label(): string
    {
        return (string) (config("influencer.tiers.{$this->value}.label") ?? ucfirst($this->value));
    }

    public function rate(): float
    {
        return (float) (config("influencer.tiers.{$this->value}.rate") ?? 15);
    }

    public static function fromReferralCount(int $count): self
    {
        $tiers = config('influencer.tiers', []);
        $result = self::STARTER;
        foreach ($tiers as $key => $data) {
            if ($count >= ($data['min_referrals'] ?? 0)) {
                $result = self::from($key);
            }
        }
        return $result;
    }
}
