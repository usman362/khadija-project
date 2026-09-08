<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse a field's own placeholder as its value.
 *
 * OA-131: an account had "e.g. Technology, Healthcare" saved as its industry,
 * "Your Company LLC" as its company name and "https://iyourcompany.com" as its
 * website. The form is written correctly — those strings are placeholder
 * attributes, which never submit — so somebody typed the hints in, typo and
 * all, and the platform stored them as facts about a business.
 *
 * A hint is an example of what to write. It is never the answer, and there is
 * no client for whom "e.g. Technology, Healthcare" is the true industry. So it
 * is refused at the point it would be saved rather than cleaned up afterwards,
 * because afterwards is how it reached a reviewer's screenshot.
 *
 * Matching is loose about case and spacing and tolerant of a typo, since the
 * value that got through last time was a mistyped placeholder rather than an
 * exact copy.
 */
class NotTheHintText implements ValidationRule
{
    /**
     * @param  array<int, string>  $hints  the placeholder(s) shown on this field
     */
    public function __construct(private array $hints) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        foreach ($this->hints as $hint) {
            if (self::looksLike($value, $hint)) {
                $fail('Please replace the example with your own details.');

                return;
            }
        }

        // "e.g." is how every hint on these forms opens, so anything starting
        // that way is an example whoever wrote it forgot to replace.
        if (preg_match('/^\s*e\.?g\.?\b/i', $value)) {
            $fail('Please replace the example with your own details.');
        }
    }

    /**
     * Close enough to the hint to be it.
     *
     * "https://iyourcompany.com" is not the placeholder, but it is nobody's
     * website either — one stray character is the difference between a mistyped
     * hint and a real address, so the comparison ignores what a slip would
     * change: case, spaces, punctuation and the scheme.
     */
    public static function looksLike(string $value, string $hint): bool
    {
        $strip = fn (string $s) => preg_replace(
            '/[^a-z0-9]/',
            '',
            strtolower(preg_replace('#^https?://#i', '', trim($s))),
        );

        $a = $strip($value);
        $b = $strip($hint);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || levenshtein($a, $b) <= 2;
    }
}
