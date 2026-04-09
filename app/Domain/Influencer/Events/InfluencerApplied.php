<?php

namespace App\Domain\Influencer\Events;

use App\Models\Influencer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InfluencerApplied
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Influencer $influencer)
    {
    }
}
