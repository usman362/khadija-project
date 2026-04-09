<?php

namespace App\Domain\Influencer\Events;

use App\Models\InfluencerReferral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralAttributed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly InfluencerReferral $referral)
    {
    }
}
