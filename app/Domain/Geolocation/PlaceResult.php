<?php

namespace App\Domain\Geolocation;

/**
 * One geocode attempt. Coordinates are present only when precision is
 * exact or zip. Unresolved never carries a invented point.
 */
final class PlaceResult
{
    public function __construct(
        public readonly string $precision,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?string $zip = null,
        public readonly ?string $message = null,
    ) {}

    public static function exact(float $lat, float $lng, ?string $zip = null): self
    {
        return new self(LocationPrecision::EXACT, $lat, $lng, $zip);
    }

    public static function zip(float $lat, float $lng, string $zip): self
    {
        return new self(LocationPrecision::ZIP, $lat, $lng, $zip, 'Location is approximate (ZIP).');
    }

    public static function unresolved(?string $message = null, ?string $zip = null): self
    {
        return new self(
            LocationPrecision::UNRESOLVED,
            null,
            null,
            $zip,
            $message ?? 'We could not place this location.',
        );
    }

    public function isMatchable(): bool
    {
        return LocationPrecision::isMatchable($this->precision);
    }
}
