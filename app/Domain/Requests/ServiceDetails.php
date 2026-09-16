<?php

namespace App\Domain\Requests;

use App\Models\Event;

/**
 * The level 4 details a client adds under a service, and the words they type
 * when the list has no name for what they want.
 *
 * Khadijah, 2026-09-15: one picker across the bidding, direct and emergency
 * forms, so the three of them keep the same taxonomy. The same goes for what
 * happens to the answers, which is what this holds: the rules, the pruning and
 * the pivot shape live here once rather than three times.
 *
 * Several details per service (Sir Peter, 2026-09-16): catering for Lunch AND
 * Dinner is one service with two details, not a choice between them. The
 * input is `service_details[<service id>][]`.
 */
class ServiceDetails
{
    public static function rules(): array
    {
        return [
            'service_details'     => ['nullable', 'array'],
            'service_details.*'   => ['nullable', 'array'],
            'service_details.*.*' => ['nullable', 'integer', new \App\Rules\ServiceDetail],
            'service_missing'     => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * The details that still belong to a service on the request, as lists of
     * ids keyed by service.
     *
     * A client who ticks Buffet Catering, picks Breakfast, then unticks the
     * service must not leave Breakfast behind on a request that no longer asks
     * for catering.
     *
     * A single id per service (a draft saved before details could be several)
     * is read as a list of one.
     *
     * @param  array<int>  $serviceIds
     * @return array<int, array<int, int>>
     */
    public static function prune(array $details, array $serviceIds): array
    {
        $keep = array_flip(array_map('intval', $serviceIds));
        $out = [];

        foreach ($details as $serviceId => $ids) {
            if (! isset($keep[(int) $serviceId])) {
                continue;
            }

            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));

            if ($ids !== []) {
                $out[(int) $serviceId] = $ids;
            }
        }

        return $out;
    }

    /**
     * Services and their details, ready for categories()->sync().
     *
     * @param  array<int>  $serviceIds
     * @return array<int, array{specialty_ids: string|null}>
     */
    public static function sync(array $serviceIds, array $details): array
    {
        $details = self::prune($details, $serviceIds);

        return collect($serviceIds)
            ->mapWithKeys(fn ($id) => [(int) $id => [
                'specialty_ids' => isset($details[(int) $id]) ? json_encode($details[(int) $id]) : null,
            ]])
            ->all();
    }

    /**
     * The details saved on a request, keyed by service, read back off the
     * pivot rows of `$event->categories`.
     *
     * @return array<int, array<int, int>>
     */
    public static function of(Event $event): array
    {
        return $event->categories
            ->mapWithKeys(fn ($c) => [$c->id => array_map('intval', (array) json_decode((string) $c->pivot->specialty_ids, true))])
            ->filter()
            ->all();
    }

    /**
     * "Full-Service Catering (Lunch, Dinner)" for each service, in the order
     * given.
     *
     * @param  iterable<\App\Models\Category>  $services
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function labels(iterable $services, array $details): \Illuminate\Support\Collection
    {
        $names = \App\Models\Category::whereIn('id', collect($details)->flatten()->filter()->all())
            ->pluck('name', 'id');

        return collect($services)->map(function ($service) use ($details, $names) {
            $picked = collect($details[$service->id] ?? [])->map(fn ($id) => $names[$id] ?? null)->filter();

            return $picked->isEmpty() ? $service->name : "{$service->name} ({$picked->implode(', ')})";
        })->values();
    }

    /** Write both onto a request in one step. */
    public static function apply(Event $event, array $serviceIds, array $input): void
    {
        $event->categories()->sync(self::sync($serviceIds, (array) ($input['service_details'] ?? [])));

        $missing = trim((string) ($input['service_missing'] ?? ''));

        if ($missing !== '' || $event->service_missing !== null) {
            $event->forceFill(['service_missing' => $missing ?: null])->saveQuietly();
        }
    }
}
