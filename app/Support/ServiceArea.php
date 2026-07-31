<?php

namespace App\Support;

use App\Models\User;

/**
 * Is a user somewhere we operate?
 *
 * One place decides it, so registration, the post-registration screen, the
 * middleware and the admin waitlist can never disagree.
 *
 * The two statuses are `supported` and `coming_soon`. There is deliberately no
 * "unsupported": a registration from outside the launch area is a demand
 * signal, and the wording in front of the user and inside the admin should say
 * the same thing (Peter, 2026-07-30).
 */
final class ServiceArea
{
    public const SUPPORTED   = 'supported';
    public const COMING_SOON = 'coming_soon';

    /**
     * Work out the status from a location. Only US states inside the launch
     * area count; everywhere else — including the rest of the US — is
     * coming soon.
     */
    public static function statusFor(?string $country, ?string $state): string
    {
        $country = self::countryCode($country);

        if ($country !== null && $country !== 'US') {
            return self::COMING_SOON;
        }

        return $state !== null && array_key_exists($state, config('geo.allowed_states', []))
            ? self::SUPPORTED
            : self::COMING_SOON;
    }

    /** May this user use the marketplace? */
    public static function allows(?User $user): bool
    {
        // Not signed in, or no profile yet — nothing to restrict here; the
        // auth middleware handles that.
        $status = $user?->profile?->service_area_status;

        return $status === null || $status === self::SUPPORTED;
    }

    /**
     * Country to its ISO code.
     *
     * The registration form posts a code, but the column has also been written
     * with the label ("United States") by seeders and by older profile forms.
     * Comparing the raw value put every demo professional out of area despite
     * all of them sitting in launch states, so the comparison is done on the
     * code and both spellings are accepted.
     */
    public static function countryCode(?string $country): ?string
    {
        if ($country === null || $country === '') {
            return null;
        }

        $countries = config('geo.countries', []);

        if (array_key_exists($country, $countries)) {
            return $country;
        }

        $code = array_search($country, $countries, true);

        return $code === false ? $country : $code;
    }

    /** "Baltimore, Maryland, United States" — for the message shown to the user. */
    public static function describe(?string $city, ?string $state, ?string $country): string
    {
        $parts = array_filter([
            $city ?: null,
            $state ? (config('geo.us_states', [])[$state] ?? $state) : null,
            $country ? (config('geo.countries', [])[$country] ?? $country) : null,
        ]);

        return $parts ? implode(', ', $parts) : 'your area';
    }

    /** Never "Unsupported" — see the class note. */
    public static function label(string $status): string
    {
        return $status === self::SUPPORTED ? 'Supported' : 'Coming Soon';
    }
}
