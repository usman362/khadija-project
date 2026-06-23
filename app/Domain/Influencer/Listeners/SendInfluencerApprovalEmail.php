<?php

namespace App\Domain\Influencer\Listeners;

use App\Domain\Influencer\Events\InfluencerApproved;
use App\Mail\InfluencerApplicationApproved;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInfluencerApprovalEmail
{
    public function handle(InfluencerApproved $event): void
    {
        $influencer = $event->influencer;
        $email = $influencer->user?->email ?? $influencer->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new InfluencerApplicationApproved($influencer));
        } catch (Throwable $e) {
            // Never let a mail failure roll back an approval.
            Log::warning('Failed to send influencer approval email', [
                'influencer_id' => $influencer->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
