<?php

namespace App\Domain\Geolocation;

use Illuminate\Http\Request;

/**
 * Hybrid geolocation (Developer Feedback v1.1 §7.2).
 *
 * IP-only geolocation is unreliable for a hyper-local marketplace, so we layer
 * cheap-and-accurate signals first and fall back to IP last:
 *
 *   Step 1 — Session / cookie : did the user already give us a zip/city/state?
 *   Step 2 — Browser HTML5    : navigator.geolocation coords (frontend → fromHtml5)
 *   Step 3 — IP fallback      : Cloudflare edge headers (free, zero-code) →
 *                               MaxMind GeoLite2 (self-hosted, needs license key)
 *
 * Returns a best-guess US state, scoped to the launch states. Steps 1 & the
 * Cloudflare part of Step 3 work today with no credentials; MaxMind + HTML5
 * reverse-geocoding are stubbed until keys / a provider are wired.
 */
class GeolocationService
{
    private const SESSION_KEY = 'geo_state';

    /**
     * Best-effort guess of the user's launch state.
     *
     * @return array{state:string,name:string,source:string}|null
     */
    public function guessState(Request $request): ?array
    {
        // Step 1 — session (set from a prior zip/city entry; cheapest & best).
        $fromSession = $this->normalize(session(self::SESSION_KEY));
        if ($fromSession) {
            return $this->hit($fromSession, 'session');
        }

        // Step 3a — Cloudflare edge headers (free, present when behind Cloudflare).
        if (strtoupper((string) $request->header('CF-IPCountry')) === 'US') {
            $region = $this->normalize($request->header('CF-Region-Code'));
            if ($region) {
                return $this->hit($region, 'cloudflare');
            }
        }

        // Step 3b — MaxMind GeoLite2 IP lookup (self-hosted DB).
        $fromIp = $this->lookupMaxmind($request->ip());
        if ($fromIp) {
            return $this->hit($fromIp, 'ip');
        }

        return null;
    }

    /**
     * Persist a known state to the session so future requests skip IP guessing
     * (Step 1). Call this whenever the user gives a zip/city/state.
     */
    public function rememberState(?string $state): void
    {
        $state = $this->normalize($state);
        if ($state) {
            session([self::SESSION_KEY => $state]);
        }
    }

    /**
     * Map a 5-digit zip to a launch state via the §7.1 prefix table (free).
     */
    public function stateFromZip(?string $zip): ?string
    {
        if (! $zip) {
            return null;
        }

        return config('geo.zip_prefixes', [])[substr((string) $zip, 0, 3)] ?? null;
    }

    /**
     * Step 2 — resolve browser HTML5 coordinates to a state.
     *
     * SCAFFOLD: precise lat/lng → state needs a reverse-geocoder (Google/Census).
     * Returns null until one is wired; the frontend should still capture coords
     * and POST them so they can be stored.
     */
    public function fromHtml5(float $lat, float $lng): ?string
    {
        // TODO(launch): reverse-geocode via Census/Google → 2-letter state.
        return null;
    }

    /**
     * Step 3b — MaxMind GeoLite2 lookup.
     *
     * SCAFFOLD: requires the GeoLite2-City DB (MAXMIND_DB_PATH) + the
     * geoip2/geoip2 reader. Returns null until the DB is present.
     */
    private function lookupMaxmind(?string $ip): ?string
    {
        $dbPath = env('MAXMIND_DB_PATH');
        if (! $ip || ! $dbPath || ! is_readable($dbPath)) {
            return null;
        }

        // TODO(launch): (new \GeoIp2\Database\Reader($dbPath))->city($ip)
        //   ->mostSpecificSubdivision->isoCode → normalize().
        return null;
    }

    /**
     * Keep only states inside the launch allow-list; uppercase & validate.
     */
    private function normalize(?string $state): ?string
    {
        $state = strtoupper(trim((string) $state));

        return array_key_exists($state, config('geo.allowed_states', [])) ? $state : null;
    }

    private function hit(string $state, string $source): array
    {
        return [
            'state'  => $state,
            'name'   => config('geo.allowed_states', [])[$state] ?? $state,
            'source' => $source,
        ];
    }
}
