<?php

namespace App\Rules;

use App\Domain\Requests\VenueRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Issue #14: a booking's venue read "social.bxlpubcrawl.com".
 *
 * A web address is not somewhere anyone can turn up to, and a professional
 * reading a request has no way to act on one. The pages already refuse to
 * print a link where a place belongs; this stops one being stored in the
 * first place, so the two ends agree.
 */
class PlaceNotALink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) !== '' && VenueRule::place($value) === null) {
            $fail('Give the place itself, not a web address. A town, a venue name or a street address all work.');
        }
    }
}
