<?php

namespace App\Rules;

use App\Domain\AddressVerification\AddressFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The free half of an address check, applied wherever somebody types one.
 *
 * Sir Peter, 20 October: "ALL addresses (in the website) needs be checked for
 * accuracy, I created a fake address and it went right by it, and accepted a
 * fake address, this is not good."
 *
 * He is right that nothing was checking. AddressFilter has existed since the
 * address-verification scaffold was written, and it was wired into exactly one
 * screen: a professional verifying their own business address. Event locations,
 * client profiles and everything else had no filter at all. This rule puts the
 * free layer everywhere an address is entered.
 *
 * WHAT THIS CANNOT DO, and it is the important half of the answer. It catches
 * blanks, PO Boxes, strings with no number in them and mashed keyboard input.
 * It cannot tell that a well-formed address does not exist. "18 Henry Street,
 * Canton" has a number, a street and a real town, and no free check will ever
 * refuse it, because confirming a building is there means asking somebody who
 * knows: USPS or Google Address Validation.
 *
 * That call is already written, in AddressVerificationService's Layer 2, and it
 * is dormant behind the same launch lock as payments because the API account
 * has not been provisioned. Until it is, this rule narrows the gap rather than
 * closing it, and it is better that the limit is stated here than that a filter
 * is mistaken for a verification.
 */
class PlausibleAddress implements ValidationRule
{
    /**
     * @param  bool  $requireNumber  false where a town or a venue name is a
     *                               legitimate answer, as it is on a request
     *                               whose client has not settled on a venue.
     */
    public function __construct(private bool $requireNumber = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $line   = trim($value);
        $filter = app(AddressFilter::class);

        if ($filter->isPoBox($line)) {
            $fail('A PO Box cannot be used as an address. Please give a physical street address.');

            return;
        }

        // Keyboard mash: one character repeated, or a line with no letters.
        $squashed = preg_replace('/\s+/', '', mb_strtolower($line));

        if ($squashed !== '' && preg_match('/^(.)\1{4,}$/', $squashed)) {
            $fail('That does not look like a real address. Please check it.');

            return;
        }

        if (! preg_match('/\p{L}/u', $line)) {
            $fail('That does not look like a real address. Please check it.');

            return;
        }

        /*
         * A street address has a number on it. Only asked for where the field
         * is meant to be a street: a client who has not found a venue yet
         * answers with a town, and demanding a house number of them would
         * refuse the honest answer.
         */
        if ($this->requireNumber && ! preg_match('/\d/', $line)) {
            $fail('Add the building or street number, so professionals can find it.');
        }
    }
}
