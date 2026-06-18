<?php

namespace App\Domain\AddressVerification;

use App\Domain\AddressVerification\Providers\AddressProvider;
use App\Domain\AddressVerification\Providers\GoogleAddressProvider;
use App\Domain\AddressVerification\Providers\UspsAddressProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Risk-based address verification flow (Developer Feedback v1.1 §7.3–7.5).
 *
 *   Layer 1 (free)  — AddressFilter rejects blanks / PO Boxes / junk.
 *   Layer 2 (paid)  — provider call, capped at max_paid_attempts, then lock.
 *
 * Until ADDRESS_VERIFICATION_GO_LIVE=true the paid call is skipped (guarded);
 * a filter-passing address lands in "Manual Review Required" without spending
 * an attempt. State is persisted on the user's profile.
 */
class AddressVerificationService
{
    public function __construct(private AddressFilter $filter) {}

    /**
     * Verify a professional / business address.
     *
     * @param array{line1:string,line2?:string,city:string,state:string,zip:string} $address
     * @return array{status:string,label:string,locked:bool,reason:?string,attempts:int,flag_home:bool}
     */
    public function verifyBusiness(User $user, array $address): array
    {
        $profile = $user->getOrCreateProfile();
        $max     = (int) config('address_verification.max_paid_attempts', 2);
        $attempts = (int) ($profile->address_verification_attempts ?? 0);

        // Already passed — nothing to do.
        if (AddressStatus::isVerified((string) $profile->address_status)) {
            return $this->result($profile->address_status, $attempts >= $max, null, $attempts, (bool) $profile->address_flagged_home);
        }

        // Already locked out — must go through support / docs.
        if ($attempts >= $max) {
            return $this->persist($profile, AddressStatus::MANUAL_REVIEW_REQUIRED, $attempts, [
                'reason' => 'Attempt limit reached — manual review required.',
            ], locked: true);
        }

        // ── Layer 1 (free) ──────────────────────────────────────────────
        $verdict = $this->filter->inspect($address);

        if (! $verdict['ok']) {
            // PO Box / blank / junk — correctable, no paid attempt consumed.
            return $this->persist($profile, AddressStatus::NEEDS_CORRECTION, $attempts, [
                'reason'    => $verdict['reason'],
                'free_block' => $verdict['block'],
            ]);
        }

        $flagHome = (bool) $verdict['flag_home'];

        // ── Layer 2 (paid) — guarded ────────────────────────────────────
        if (! AddressVerificationGuard::paidVerificationEnabled()) {
            // Pre-launch: no billable call. Honest "manual review" without
            // burning an attempt; re-runs automatically once go-live flips.
            return $this->persist($profile, AddressStatus::MANUAL_REVIEW_REQUIRED, $attempts, [
                'reason'    => 'Automated verification is not active yet; queued for manual review.',
                'flag_home' => $flagHome,
            ], flagHome: $flagHome);
        }

        // Launched — call the provider (this is the only place money is spent).
        try {
            AddressVerificationGuard::assertPaidVerificationAllowed();
            $providerResult = $this->provider()->verify($address);
        } catch (Throwable $e) {
            Log::warning('Address verification provider error', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->persist($profile, AddressStatus::MANUAL_REVIEW_REQUIRED, $attempts, [
                'reason' => 'Verification service unavailable; queued for manual review.',
            ], flagHome: $flagHome);
        }

        $attempts++;

        if ($providerResult['matched'] ?? false) {
            // Address matched. (KYB / business-name match → BUSINESS_MATCH_CONFIRMED
            // is a follow-up step once a KYB provider is wired; §7.3 Step 2.)
            return $this->persist($profile, AddressStatus::ADDRESS_VERIFIED, $attempts, [
                'normalized' => $providerResult['normalized'] ?? null,
                'flag_home'  => $flagHome,
            ], verified: true, flagHome: $flagHome);
        }

        // Failed paid attempt. §7.4: attempt 1 → Needs Correction; attempt 2 → lock.
        $locked = $attempts >= $max;

        return $this->persist(
            $profile,
            $locked ? AddressStatus::MANUAL_REVIEW_REQUIRED : AddressStatus::NEEDS_CORRECTION,
            $attempts,
            ['reason' => $providerResult['reason'] ?? 'Address could not be verified.', 'flag_home' => $flagHome],
            locked: $locked,
            flagHome: $flagHome,
        );
    }

    /**
     * Client / resident addresses are frictionless (§7.3): full address is only
     * collected at booking and stored encrypted, hidden from professionals until
     * the booking is confirmed. This marks that posture on the profile.
     */
    public function markClientAddressPrivate(User $user): array
    {
        $profile = $user->getOrCreateProfile();

        return $this->persist($profile, AddressStatus::PRIVATE_CLIENT_HIDDEN, 0, [
            'reason' => 'Client address stored privately; revealed only after a booking is confirmed.',
        ]);
    }

    /**
     * Resolve the configured paid provider.
     */
    private function provider(): AddressProvider
    {
        return match (config('address_verification.driver')) {
            'google' => new GoogleAddressProvider(),
            default  => new UspsAddressProvider(),
        };
    }

    private function persist($profile, string $status, int $attempts, array $meta, bool $verified = false, bool $locked = false, bool $flagHome = false)
    {
        $profile->update([
            'address_status'                => $status,
            'address_verification_attempts' => $attempts,
            'address_flagged_home'          => $flagHome,
            'address_verified_at'           => $verified ? now() : $profile->address_verified_at,
            'address_locked_at'             => $locked ? now() : null,
            'address_verification_meta'     => $meta,
        ]);

        return $this->result($status, $locked, $meta['reason'] ?? null, $attempts, $flagHome);
    }

    private function result(string $status, bool $locked, ?string $reason, int $attempts, bool $flagHome): array
    {
        return [
            'status'    => $status,
            'label'     => AddressStatus::label($status),
            'locked'    => $locked,
            'reason'    => $reason,
            'attempts'  => $attempts,
            'flag_home' => $flagHome,
        ];
    }
}
