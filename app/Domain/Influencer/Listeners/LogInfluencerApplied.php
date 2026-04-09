<?php

namespace App\Domain\Influencer\Listeners;

use App\Domain\Influencer\Events\InfluencerApplied;
use Illuminate\Support\Facades\Log;

class LogInfluencerApplied
{
    public function handle(InfluencerApplied $event): void
    {
        Log::info('Influencer application submitted', [
            'influencer_id' => $event->influencer->id,
            'email' => $event->influencer->email,
        ]);
    }
}
