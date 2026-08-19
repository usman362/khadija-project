<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Q9 — who counts toward a public city-directory total.
 *
 * There is no Active/Suspended enum on a Professional account. These flags
 * are the real gates. Verification badges and membership are intentionally
 * not required until product stamps that (Q9-A / Q9-B).
 */
final class DirectoryEligibility
{
    public static function scopeProfessionals(Builder $query): Builder
    {
        return $query
            ->whereHas('user', function (Builder $u) {
                $u->whereHas('roles', fn ($r) => $r->where('name', 'professional'))
                    ->whereNull('login_locked_at')
                    ->whereNull('deletion_requested_at');
            })
            ->where('service_area_status', ServiceArea::SUPPORTED)
            ->where(fn (Builder $q) => $q->whereNull('availability')
                ->orWhere('availability', '!=', 'not_available'));
    }

    public static function qualifies(?User $user): bool
    {
        if ($user === null || ! $user->hasRole('professional')) {
            return false;
        }

        if ($user->login_locked_at !== null || $user->deletion_requested_at !== null) {
            return false;
        }

        $p = $user->profile;
        if ($p === null) {
            return false;
        }

        if ($p->service_area_status !== ServiceArea::SUPPORTED) {
            return false;
        }

        if ($p->availability === 'not_available') {
            return false;
        }

        $city  = trim((string) $p->city);
        $state = strtoupper(trim((string) $p->state));

        return $city !== ''
            && $state !== ''
            && array_key_exists($state, config('geo.allowed_states', []));
    }

    public static function cityMinimum(): int
    {
        $fromSettings = \App\Models\Setting::get('directory.city_min_professionals');

        if ($fromSettings !== null && $fromSettings !== '') {
            return max(1, (int) $fromSettings);
        }

        return (int) config('geo.directory_city_min', 2);
    }
}
