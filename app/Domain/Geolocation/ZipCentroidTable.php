<?php

namespace App\Domain\Geolocation;

/**
 * Static ZIP/ZCTA internal points for the launch states.
 *
 * Today's live helper only maps a 3-digit prefix to a state. Miles matching
 * needs a point. This table is that point — not a guess drawn on the fly.
 */
final class ZipCentroidTable
{
    /** @var array<string, array{lat:float,lng:float,sq_mi:float,city?:string,state?:string}>|null */
    private static ?array $rows = null;

    public static function find(string $zip): ?array
    {
        $zip = self::normalize($zip);

        if ($zip === null) {
            return null;
        }

        return self::all()[$zip] ?? null;
    }

    public static function isTooBroad(string $zip, ?float $travelRadiusMiles = null): bool
    {
        $row = self::find($zip);

        if ($row === null) {
            return false;
        }

        $maxSqMi = (float) config('geo.zip_max_land_sq_mi', 150);
        if (($row['sq_mi'] ?? 0) > $maxSqMi) {
            return true;
        }

        if ($travelRadiusMiles !== null && $travelRadiusMiles > 0) {
            // A circle whose radius is smaller than the ZIP's implied span
            // cannot honestly use the ZIP centre as the venue.
            $impliedRadius = sqrt(max(0, (float) $row['sq_mi']) / pi());
            if ($impliedRadius > ($travelRadiusMiles / 2)) {
                return true;
            }
        }

        return false;
    }

    public static function normalize(?string $zip): ?string
    {
        if ($zip === null) {
            return null;
        }

        if (! preg_match('/(\d{5})/', $zip, $m)) {
            return null;
        }

        return $m[1];
    }

    /** @return array<string, array{lat:float,lng:float,sq_mi:float,city?:string,state?:string}> */
    public static function all(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        $path = database_path('seeders/data/zip_centroids_v1.json');
        $raw  = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        unset($raw['_comment']);

        self::$rows = [];
        foreach ((array) $raw as $zip => $row) {
            if (! is_array($row) || ! isset($row['lat'], $row['lng'])) {
                continue;
            }
            $key = self::normalize((string) $zip);
            if ($key === null) {
                continue;
            }
            self::$rows[$key] = [
                'lat'   => (float) $row['lat'],
                'lng'   => (float) $row['lng'],
                'sq_mi' => (float) ($row['sq_mi'] ?? 0),
                'city'  => $row['city'] ?? null,
                'state' => $row['state'] ?? null,
            ];
        }

        return self::$rows;
    }

    /** Tests may swap the table. */
    public static function swap(?array $rows): void
    {
        self::$rows = $rows;
    }
}
