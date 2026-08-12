<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The submitted category is something a professional can actually be booked
 * to do.
 *
 * Checklist row 91. Every request form validated its services with
 * `exists:categories,id`, which an event type satisfies perfectly well — so
 * "Baby Shower" could be submitted as the service requested and then sat on
 * the booking card beside real services like Event Staffing. Narrowing the
 * pickers fixes what a client is offered; this is what stops the value being
 * accepted when it arrives by another route.
 */
class BookableService implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $category = Category::find($value);

        if (! $category) {
            $fail('That service no longer exists.');

            return;
        }

        $isBookable = Category::whereKey($category->id)->bookableServices()->exists();

        if (! $isBookable) {
            $fail("“{$category->name}” is a type of event, not a service you can book. Choose the service you need for it.");
        }
    }
}
