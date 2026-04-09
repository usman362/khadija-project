<?php

namespace App\Domain\Influencer\Services;

use App\Domain\Auth\Enums\RoleName;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Domain\Influencer\Enums\CommissionTier;
use App\Domain\Influencer\Enums\InfluencerStatus;
use App\Domain\Influencer\Enums\PayoutRequestStatus;
use App\Domain\Influencer\Enums\ReferralStatus;
use App\Domain\Influencer\Enums\ReferralType;
use App\Domain\Influencer\Events\InfluencerApplied;
use App\Domain\Influencer\Events\InfluencerApproved;
use App\Domain\Influencer\Events\ReferralAttributed;
use App\Mail\PayoutPaid;
use App\Mail\PayoutRejected;
use App\Mail\PayoutRequested;
use App\Models\Booking;
use App\Models\Influencer;
use App\Models\InfluencerPayoutRequest;
use App\Models\InfluencerReferral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class EloquentInfluencerService implements InfluencerServiceInterface
{
    public function apply(InfluencerApplicationData $data): Influencer
    {
        return DB::transaction(function () use ($data) {
            $influencer = Influencer::create([
                'user_id' => auth()->id(),
                'full_name' => $data->fullName,
                'email' => $data->email,
                'social_media_links' => $data->socialMediaLinks,
                'audience_description' => $data->audienceDescription,
                'monthly_reach' => $data->monthlyReach,
                'referral_code' => Influencer::generateReferralCode(),
                'commission_tier' => CommissionTier::STARTER->value,
                'status' => InfluencerStatus::PENDING->value,
            ]);

            InfluencerApplied::dispatch($influencer);

            return $influencer;
        });
    }

    public function approve(Influencer $influencer, ?User $admin = null, ?string $notes = null): Influencer
    {
        return DB::transaction(function () use ($influencer, $notes) {
            $influencer->update([
                'status' => InfluencerStatus::APPROVED->value,
                'approved_at' => now(),
                'admin_notes' => $notes ?: $influencer->admin_notes,
            ]);

            if ($influencer->user && ! $influencer->user->hasRole(RoleName::INFLUENCER->value)) {
                $influencer->user->assignRole(RoleName::INFLUENCER->value);
            }

            InfluencerApproved::dispatch($influencer);

            return $influencer->fresh();
        });
    }

    public function reject(Influencer $influencer, ?User $admin = null, ?string $notes = null): Influencer
    {
        $influencer->update([
            'status' => InfluencerStatus::REJECTED->value,
            'rejected_at' => now(),
            'admin_notes' => $notes ?: $influencer->admin_notes,
        ]);

        return $influencer->fresh();
    }

    public function attributeSignup(User $user, string $referralCode): ?InfluencerReferral
    {
        $influencer = Influencer::where('referral_code', $referralCode)
            ->where('status', InfluencerStatus::APPROVED->value)
            ->first();

        if (! $influencer) {
            return null;
        }

        return DB::transaction(function () use ($user, $influencer) {
            $user->update([
                'referred_by_influencer_id' => $influencer->id,
                'referral_attributed_at' => now(),
            ]);

            $bonus = (float) config('influencer.signup_bonus', 5);

            $referral = InfluencerReferral::create([
                'influencer_id' => $influencer->id,
                'referred_user_id' => $user->id,
                'type' => ReferralType::SIGNUP_BONUS->value,
                'base_amount' => 0,
                'commission_rate' => 0,
                'commission_amount' => $bonus,
                'status' => ReferralStatus::EARNED->value,
                'source' => 'system',
            ]);

            $this->incrementBalances($influencer, $bonus, countReferral: true);

            ReferralAttributed::dispatch($referral);

            return $referral;
        });
    }

    public function attributeBookingCommission(Booking $booking): ?InfluencerReferral
    {
        if (! $booking->price || $booking->price <= 0) {
            return null;
        }

        $client = $booking->client;
        if (! $client || ! $client->referred_by_influencer_id) {
            return null;
        }

        $influencer = Influencer::find($client->referred_by_influencer_id);
        if (! $influencer || ! $influencer->isApproved()) {
            return null;
        }

        // Avoid duplicate commission per booking
        $existing = InfluencerReferral::where('booking_id', $booking->id)
            ->where('type', ReferralType::BOOKING_COMMISSION->value)
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($booking, $influencer) {
            $rate = $influencer->commission_tier->rate();
            $base = (float) $booking->price;
            $amount = round($base * $rate / 100, 2);

            $referral = InfluencerReferral::create([
                'influencer_id' => $influencer->id,
                'referred_user_id' => $booking->client_id,
                'booking_id' => $booking->id,
                'type' => ReferralType::BOOKING_COMMISSION->value,
                'base_amount' => $base,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'status' => ReferralStatus::EARNED->value,
                'source' => 'system',
            ]);

            $this->incrementBalances($influencer, $amount, countReferral: false);

            // Re-evaluate tier based on total successful referrals
            $this->recalculateTier($influencer);

            ReferralAttributed::dispatch($referral);

            return $referral;
        });
    }

    public function requestPayout(Influencer $influencer, float $amount, ?string $method, ?string $account, ?string $notes): InfluencerPayoutRequest
    {
        $min = (float) config('influencer.min_payout_threshold', 50);

        if ($amount < $min) {
            throw new RuntimeException("Minimum payout amount is {$min}.");
        }
        if ($amount > (float) $influencer->available_balance) {
            throw new RuntimeException('Amount exceeds available balance.');
        }

        $request = DB::transaction(function () use ($influencer, $amount, $method, $account, $notes) {
            $req = InfluencerPayoutRequest::create([
                'influencer_id' => $influencer->id,
                'amount' => $amount,
                'payout_method' => $method,
                'payout_account' => $account,
                'user_notes' => $notes,
                'status' => PayoutRequestStatus::PENDING->value,
            ]);

            // Reserve the amount
            $influencer->decrement('available_balance', $amount);

            return $req;
        });

        $this->notifyInfluencer($influencer, fn($email) => Mail::to($email)->send(new PayoutRequested($request)));

        return $request;
    }

    public function markPayoutPaid(InfluencerPayoutRequest $request, User $admin, ?string $notes = null): InfluencerPayoutRequest
    {
        $updated = DB::transaction(function () use ($request, $admin, $notes) {
            $request->update([
                'status' => PayoutRequestStatus::PAID->value,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'paid_at' => now(),
                'admin_notes' => $notes,
            ]);

            $request->influencer->increment('paid_out', $request->amount);

            return $request->fresh(['influencer']);
        });

        $this->notifyInfluencer($updated->influencer, fn($email) => Mail::to($email)->send(new PayoutPaid($updated)));

        return $updated;
    }

    public function rejectPayout(InfluencerPayoutRequest $request, User $admin, ?string $notes = null): InfluencerPayoutRequest
    {
        $updated = DB::transaction(function () use ($request, $admin, $notes) {
            // Return the reserved amount
            $request->influencer->increment('available_balance', $request->amount);

            $request->update([
                'status' => PayoutRequestStatus::REJECTED->value,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_notes' => $notes,
            ]);

            return $request->fresh(['influencer']);
        });

        $this->notifyInfluencer($updated->influencer, fn($email) => Mail::to($email)->send(new PayoutRejected($updated)));

        return $updated;
    }

    /**
     * Send an email to an influencer's best known contact address.
     * Failures are swallowed so that transactional payout logic is not interrupted.
     */
    private function notifyInfluencer(Influencer $influencer, \Closure $sender): void
    {
        $email = $influencer->user?->email ?? $influencer->email;

        if (!$email) {
            return;
        }

        try {
            $sender($email);
        } catch (Throwable $e) {
            Log::warning('Failed to send payout notification email', [
                'influencer_id' => $influencer->id,
                'email'         => $email,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    protected function incrementBalances(Influencer $influencer, float $amount, bool $countReferral): void
    {
        $influencer->increment('total_earnings', $amount);
        $influencer->increment('available_balance', $amount);
        if ($countReferral) {
            $influencer->increment('total_referrals');
        }
    }

    protected function recalculateTier(Influencer $influencer): void
    {
        $count = (int) $influencer->total_referrals;
        $newTier = CommissionTier::fromReferralCount($count);
        if ($newTier->value !== $influencer->commission_tier->value) {
            $influencer->update(['commission_tier' => $newTier->value]);
        }
    }
}
