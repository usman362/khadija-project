<?php

namespace App\Domain\Influencer\Contracts;

use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Models\Booking;
use App\Models\Influencer;
use App\Models\InfluencerPayoutRequest;
use App\Models\InfluencerReferral;
use App\Models\User;

interface InfluencerServiceInterface
{
    public function apply(InfluencerApplicationData $data): Influencer;

    public function approve(Influencer $influencer, ?User $admin = null, ?string $notes = null): Influencer;

    public function reject(Influencer $influencer, ?User $admin = null, ?string $notes = null): Influencer;

    public function attributeSignup(User $user, string $referralCode): ?InfluencerReferral;

    public function attributeBookingCommission(Booking $booking): ?InfluencerReferral;

    public function requestPayout(Influencer $influencer, float $amount, ?string $method, ?string $account, ?string $notes): InfluencerPayoutRequest;

    public function markPayoutPaid(InfluencerPayoutRequest $request, User $admin, ?string $notes = null): InfluencerPayoutRequest;

    public function rejectPayout(InfluencerPayoutRequest $request, User $admin, ?string $notes = null): InfluencerPayoutRequest;
}
