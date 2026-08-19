<?php

namespace App\Domain\Geolocation;

use Illuminate\Support\Facades\Http;

/**
 * Place a street or ZIP without inventing a point.
 *
 * Hierarchy (Q7): street geocode → ZIP centroid (if not too broad) → unresolved.
 * Search never calls this; it runs on save.
 */
class Geocoder
{
    public function place(?string $line, ?string $city, ?string $state, ?string $zip, ?float $travelRadiusMiles = null): PlaceResult
    {
        $zip = ZipCentroidTable::normalize($zip) ?? ZipCentroidTable::normalize($line);
        $state = $state ? strtoupper(trim($state)) : null;
        $line = trim((string) $line) ?: null;
        $city = trim((string) $city) ?: null;

        if ($line || ($city && $state)) {
            $street = $this->census($line, $city, $state, $zip);
            if ($street !== null) {
                return PlaceResult::exact($street['lat'], $street['lng'], $zip);
            }
        }

        if ($zip !== null) {
            return $this->fromZip($zip, $travelRadiusMiles);
        }

        return PlaceResult::unresolved('We could not place this location. Please enter a street, venue, or a more specific ZIP.');
    }

    public function fromFreeText(?string $text, ?string $state = null, ?float $travelRadiusMiles = null): PlaceResult
    {
        $text = trim((string) $text);
        $zip  = ZipCentroidTable::normalize($text);

        $city = null;
        $line = $text !== '' ? $text : null;

        if (preg_match('/^\s*([^,]+),\s*([A-Za-z]{2})\b/', $text, $m)) {
            $city  = trim($m[1]);
            $state = $state ?: strtoupper($m[2]);
        }

        return $this->place($line, $city, $state, $zip, $travelRadiusMiles);
    }

    public function fromZip(string $zip, ?float $travelRadiusMiles = null): PlaceResult
    {
        $zip = ZipCentroidTable::normalize($zip);
        if ($zip === null) {
            return PlaceResult::unresolved('We could not place this location.');
        }

        $state = app(GeolocationService::class)->stateFromZip($zip);
        if ($state !== null && ! array_key_exists($state, config('geo.allowed_states', []))) {
            return PlaceResult::unresolved('That ZIP is outside the area GigResource currently serves.', $zip);
        }

        if (ZipCentroidTable::isTooBroad($zip, $travelRadiusMiles)) {
            return PlaceResult::unresolved(
                'This ZIP covers too large an area to use as a location. Please enter a city, venue, or street.',
                $zip,
            );
        }

        $row = ZipCentroidTable::find($zip);
        if ($row !== null) {
            return PlaceResult::zip($row['lat'], $row['lng'], $zip);
        }

        $census = $this->census(null, null, $state, $zip);
        if ($census !== null) {
            return PlaceResult::zip($census['lat'], $census['lng'], $zip);
        }

        return PlaceResult::unresolved('We could not place this ZIP. Please enter a city, venue, or street.', $zip);
    }

    /** @return array{lat:float,lng:float}|null */
    private function census(?string $line, ?string $city, ?string $state, ?string $zip): ?array
    {
        if (config('geo.geocoder') !== 'census') {
            return null;
        }

        $address = trim(implode(', ', array_filter([$line, $city, $state, $zip])));
        if ($address === '') {
            return null;
        }

        try {
            $response = Http::timeout(4)
                ->get('https://geocoding.geo.census.gov/geocoder/locations/onelineaddress', [
                    'address'   => $address,
                    'benchmark' => 'Public_AR_Current',
                    'format'    => 'json',
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $match = $response->json('result.addressMatches.0.coordinates');
        if (! is_array($match) || ! isset($match['y'], $match['x'])) {
            return null;
        }

        return ['lat' => (float) $match['y'], 'lng' => (float) $match['x']];
    }
}
