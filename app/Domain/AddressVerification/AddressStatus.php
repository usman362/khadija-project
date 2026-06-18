<?php

namespace App\Domain\AddressVerification;

/**
 * Dashboard status labels for address verification (Developer Feedback v1.1 §7.3).
 *
 * The value strings are what's persisted in user_profiles.address_status; the
 * label() / color() helpers drive the dashboard badge.
 */
final class AddressStatus
{
    public const PENDING                  = 'pending';                   // not yet attempted
    public const ADDRESS_VERIFIED         = 'address_verified';          // address matched
    public const BUSINESS_MATCH_CONFIRMED = 'business_match_confirmed';  // KYB / Places passed
    public const NEEDS_CORRECTION         = 'needs_correction';          // close match — fix & retry
    public const MANUAL_REVIEW_REQUIRED   = 'manual_review_required';    // locked — submit docs
    public const REGISTRATION_BLOCKED     = 'registration_blocked';      // invalid / fraudulent
    public const PRIVATE_CLIENT_HIDDEN    = 'private_client_hidden';     // client addr encrypted, hidden until booking

    /**
     * Statuses that count as "passed" — no further action needed.
     */
    public static function isVerified(string $status): bool
    {
        return in_array($status, [self::ADDRESS_VERIFIED, self::BUSINESS_MATCH_CONFIRMED], true);
    }

    /**
     * Statuses that lock the input (user must open a support ticket / submit docs).
     */
    public static function isLocked(string $status): bool
    {
        return in_array($status, [self::MANUAL_REVIEW_REQUIRED, self::REGISTRATION_BLOCKED], true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::ADDRESS_VERIFIED         => 'Address Verified',
            self::BUSINESS_MATCH_CONFIRMED => 'Business Match Confirmed',
            self::NEEDS_CORRECTION         => 'Needs Correction',
            self::MANUAL_REVIEW_REQUIRED   => 'Manual Review Required',
            self::REGISTRATION_BLOCKED     => 'Registration Blocked',
            self::PRIVATE_CLIENT_HIDDEN    => 'Private Client Address — Hidden Until Booking Confirmed',
            default                        => 'Pending Verification',
        };
    }

    /**
     * Badge colour token (maps to dashboard semantic colours).
     */
    public static function color(string $status): string
    {
        return match ($status) {
            self::ADDRESS_VERIFIED, self::BUSINESS_MATCH_CONFIRMED => 'green',
            self::NEEDS_CORRECTION                                 => 'amber',
            self::MANUAL_REVIEW_REQUIRED                           => 'amber',
            self::REGISTRATION_BLOCKED                             => 'red',
            self::PRIVATE_CLIENT_HIDDEN                            => 'blue',
            default                                                => 'gray',
        };
    }
}
