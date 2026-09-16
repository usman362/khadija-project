<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The level 4 detail belongs to the service it was submitted under.
 *
 * The picker offers each service its own details, so the pairing is right on
 * screen. This is what holds when the value arrives by another route: the
 * attribute carries the service id (`service_details.41.0` is service 41,
 * first detail), and a detail filed under a different service is refused
 * rather than saved onto a request where it means nothing.
 */
class ServiceDetail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // The segment after `service_details`, whatever follows it.
        $serviceId = (int) (explode('.', $attribute)[1] ?? 0);

        $detail = Category::where('kind', Category::SERVICE_SPECIALTY)->find($value);

        if (! $detail) {
            $fail('That option is no longer available. Choose another.');

            return;
        }

        if ((int) $detail->parent_id !== $serviceId) {
            $fail("“{$detail->name}” is not an option for the service it was chosen under.");
        }
    }
}
