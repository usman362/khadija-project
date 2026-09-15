<?php

namespace App\Domain\Requests;

use App\Models\Event;

/**
 * The level 4 detail a client adds under a service, and the words they type
 * when the list has no name for what they want.
 *
 * Khadijah, 2026-09-15: one picker across the bidding, direct and emergency
 * forms, so the three of them keep the same taxonomy. The same goes for what
 * happens to the answers, which is what this holds: the rules, the pruning and
 * the pivot shape live here once rather than three times.
 */
class ServiceDetails
{
    public static function rules(): array
    {
        return [
            'service_details'   => ['nullable', 'array'],
            'service_details.*' => ['nullable', 'integer', new \App\Rules\ServiceDetail],
            'service_missing'   => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * The details that still belong to a service on the request.
     *
     * A client who ticks Buffet Catering, picks Breakfast, then unticks the
     * service must not leave Breakfast behind on a request that no longer asks
     * for catering.
     *
     * @param  array<int>  $serviceIds
     * @return array<int, int>
     */
    public static function prune(array $details, array $serviceIds): array
    {
        return array_filter(array_intersect_key(
            array_map('intval', array_filter($details)),
            array_flip(array_map('intval', $serviceIds)),
        ));
    }

    /**
     * Services and their details, ready for categories()->sync().
     *
     * @param  array<int>  $serviceIds
     * @return array<int, array{specialty_id: int|null}>
     */
    public static function sync(array $serviceIds, array $details): array
    {
        $details = self::prune($details, $serviceIds);

        return collect($serviceIds)
            ->mapWithKeys(fn ($id) => [(int) $id => ['specialty_id' => $details[(int) $id] ?? null]])
            ->all();
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
