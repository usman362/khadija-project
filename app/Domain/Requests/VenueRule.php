<?php

namespace App\Domain\Requests;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * A client who needs to find a venue has to ask for one.
 *
 * Sir Peter's request flow: on Event Details the client says whether they
 * already have a location, need to find a venue, or are not sure yet. "I need
 * to find a venue" only reaches venue professionals if the request includes a
 * Venues & Event Spaces service, so without one the request cannot go on, and
 * the client is sent back to add it. The venue types offered on that step are
 * those same services, so ticking one there is adding it.
 */
final class VenueRule
{
    public const CATEGORY = 'Venues & Event Spaces';

    public const HAVE = 'have';
    public const NEED = 'need_venue';
    public const UNSURE = 'unsure';

    public const MAX_PREFERRED = 5;

    /** The venue services (level 3 under Venues & Event Spaces). */
    public static function services(): Collection
    {
        return Category::query()->bookableServices()->active()
            ->whereHas('parent', fn ($q) => $q->where('name', self::CATEGORY))
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /** @return array<int> */
    public static function serviceIds(): array
    {
        return self::services()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @param  array<int>  $serviceIds */
    public static function includesVenue(array $serviceIds): bool
    {
        return array_intersect(array_map('intval', $serviceIds), self::serviceIds()) !== [];
    }

    /** The one thing this rule can find wrong with a request's answers. */
    public static function problem(array $data): ?string
    {
        if (($data['location_need'] ?? null) !== self::NEED) {
            return null;
        }

        return self::includesVenue((array) ($data['services'] ?? []))
            ? null
            : 'You said you need to find a venue, but no Venues & Event Spaces service is selected. Add one so venue professionals can see your request.';
    }
}
