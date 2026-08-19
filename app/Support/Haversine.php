<?php

namespace App\Support;

/**
 * Great-circle distance in miles (WGS84 sphere). Used for marketplace
 * eligibility. Driving miles are a later feature and must not share this.
 */
final class Haversine
{
    public const EARTH_MILES = 3958.7613;

    public static function miles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $p = pi() / 180;
        $a = 0.5 - cos(($lat2 - $lat1) * $p) / 2
            + cos($lat1 * $p) * cos($lat2 * $p) * (1 - cos(($lng2 - $lng1) * $p)) / 2;

        return 2 * self::EARTH_MILES * asin(min(1, sqrt($a)));
    }
}
