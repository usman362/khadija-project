<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Category;
use Illuminate\Validation\ValidationException;

/**
 * Who a client may accept a bid from, in regulated categories (OA-104).
 *
 * Only acceptance is limited. Anyone can bid in any category; in alcohol,
 * catering, security and pyrotechnics the bid can only be accepted when the
 * professional is Verified (licence, insurance and workers' comp approved).
 */
final class RegulatedAcceptance
{
    /** The regulated Level 2 category this bid falls under, if any. */
    public static function categoryFor(Bid $bid): ?string
    {
        $names = array_map('mb_strtolower', config('regulated.categories', []));
        $node = $bid->category ?? ($bid->category_id ? Category::find($bid->category_id) : null);

        // Walk up from the service to its category.
        for ($i = 0; $node && $i < 5; $i++, $node = $node->parent) {
            if (in_array(mb_strtolower(trim($node->name)), $names, true)) {
                return $node->name;
            }
        }

        return null;
    }

    public static function allows(Bid $bid): bool
    {
        return self::categoryFor($bid) === null || (bool) $bid->supplier?->isVerified();
    }

    /** Refuses, in the client's words, when the bid cannot be accepted. */
    public static function ensure(Bid $bid): void
    {
        if (self::allows($bid)) {
            return;
        }

        throw ValidationException::withMessages([
            'bid' => (($bid->supplier?->name) ?: 'This professional') . ' is not verified yet. For '
                . self::categoryFor($bid) . ', a bid can only be accepted from a professional whose license and insurance are verified.',
        ]);
    }
}
