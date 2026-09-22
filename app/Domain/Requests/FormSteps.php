<?php

namespace App\Domain\Requests;

/**
 * Which numbered step on a request form each field belongs to.
 *
 * Sir Peter, 2026-09-22: "if an error occurs then it should list if possible
 * which step # its referring to". The forms are one long page with numbered
 * sections, so an error at the top saying "Event Name is required" leaves the
 * client scrolling to find which section that was.
 *
 * Only fields that are ASKED appear here. A field left blank that nothing
 * requires never produces an error in the first place — the other half of his
 * note — so nothing has to be listed for it.
 */
final class FormSteps
{
    /** Send a Direct Request. */
    public const DIRECT = [
        'services' => 1, 'service_details' => 1, 'service_single' => 1, 'service_missing' => 1,
        'professional_id' => 2,
        'event_type' => 3, 'event_name' => 3, 'organization_type' => 3, 'event_date' => 3,
        'guests' => 3, 'venue' => 3, 'description' => 3, 'delivery_mode' => 3,
        'budget_min' => 5, 'budget_max' => 5, 'service_budgets' => 5,
        'fee_agreed' => 6,
    ];

    /** Post a Rush Request. */
    public const EMERGENCY = [
        'organization_type' => 1, 'reason' => 1, 'needed_by' => 1,
        'event_type' => 1, 'event_name' => 1, 'description' => 1, 'event_date' => 1,
        'services' => 2, 'service_details' => 2, 'service_missing' => 2, 'delivery_mode' => 2,
        'budget_min' => 3, 'service_budgets' => 3,
        'fee_agreed' => 3,
    ];

    /**
     * The step a message belongs to, or null when the field is not on a
     * numbered step. Array fields carry their index, so `services.0` is
     * matched by its own name.
     */
    public static function numberFor(array $map, string $field): ?int
    {
        return $map[$field] ?? $map[explode('.', $field)[0]] ?? null;
    }
}
