<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The event type must be one we actually have.
 *
 * Free text here would break the Archetype Relevance Matrix, which is what
 * orders the services on every request form — an event type nothing recognises
 * has no archetype, so there is nothing to order by. A client whose event is
 * not on the list picks "Other Event" and types their own wording into
 * `event_title` beside it (Peter + Khadijah, 2026-08-20).
 *
 * Matched case-insensitively on the name, because that is what the form posts
 * and what every other entry point into a request already compares.
 */
class KnownEventType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Choose the kind of event this is.');

            return;
        }

        $exists = Category::active()->eventTypes()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($value))])
            ->exists();

        if (! $exists) {
            $fail('Choose an event type from the list, or pick "Other / not on this list".');
        }
    }
}
