<?php

namespace App\Domain\Geolocation;

/**
 * How precisely a stored point was placed.
 *
 * Matching reads this flag, never guesses. Unresolved has no coordinates —
 * a failed geocode must not become an in-range match.
 */
final class LocationPrecision
{
    public const EXACT      = 'exact';
    public const ZIP        = 'zip';
    public const UNRESOLVED = 'unresolved';

    /** May this point be used for radius eligibility? */
    public static function isMatchable(?string $precision): bool
    {
        return $precision === self::EXACT || $precision === self::ZIP;
    }
}
