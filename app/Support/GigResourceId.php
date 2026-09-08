<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The public account reference — CL-482731, PRO-100482, INF-900117.
 *
 * Sir Peter, 2026-09-03 (Idea 1): every account should carry a permanent,
 * unique reference the user and staff can quote — for support, verification,
 * transactions, reports, disputes and messaging, and to tell apart two people
 * with the same name.
 *
 * Deliberately NOT the database id. Exposing `users.id` tells anyone who looks
 * how many accounts exist and lets them walk the range; it also ties a public
 * reference to a storage detail we might one day change.
 *
 * Three properties this has to hold, and each shapes the code below:
 *
 *  1. Permanent. Assigned once, at registration, and never rewritten — not by
 *     a name change, an email change, a membership change, or a role switch.
 *  2. Unique. Enforced by a unique index, not by checking first: two people
 *     registering in the same second must not be able to take the same number.
 *  3. Not the user's to edit. It is never in a form, and never in $fillable.
 *
 * The prefix records what the account registered AS. A client who later also
 * works as a professional keeps CL-, because the reference is an identity, not
 * a description of what they are doing this week — and a reference that can
 * change is not a reference. Staff read the prefix as history, not as a role
 * lookup; the account's current roles are on the account.
 */
class GigResourceId
{
    /** Registration role => the prefix that account keeps for life. */
    public const PREFIXES = [
        'client' => 'CL',
        'professional' => 'PRO',
        'influencer' => 'INF',
        'admin' => 'ADM',
    ];

    /** Used when a role has no prefix of its own — never guessed from elsewhere. */
    public const FALLBACK_PREFIX = 'GR';

    /**
     * Six digits, which is 900,000 references per prefix.
     *
     * Widening this later is safe: the column is a string, nothing parses the
     * number out of it, and a CL-1234567 sorts and compares perfectly well
     * beside a CL-482731. That is the "expand the format later" the idea asks
     * for, and it is only true because nothing anywhere treats the digits as a
     * number.
     */
    public const DIGITS = 6;

    public static function prefixFor(?string $role): string
    {
        return self::PREFIXES[$role] ?? self::FALLBACK_PREFIX;
    }

    /**
     * Make one for this user, and store it.
     *
     * Collisions are handled by trying again rather than by checking first.
     * A "does it exist?" check followed by an insert is two operations with a
     * gap in the middle, and two registrations in that gap take the same
     * number — the unique index is the only thing that actually decides.
     */
    public static function assign(User $user): string
    {
        if (filled($user->public_id)) {
            return $user->public_id;
        }

        $prefix = self::prefixFor($user->primary_role);

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $candidate = self::compose($prefix);

            try {
                DB::table('users')->where('id', $user->id)->update(['public_id' => $candidate]);

                $user->setAttribute('public_id', $candidate);
                $user->syncOriginalAttribute('public_id');

                return $candidate;
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // Taken between generating and writing. Try another.
                continue;
            }
        }

        throw new \RuntimeException("Could not allocate a GigResource ID for user {$user->id}.");
    }

    /**
     * A candidate reference.
     *
     * random_int, not rand or a sequence: sequential references leak how many
     * accounts exist and roughly when each signed up, which is the thing not
     * using the database id was meant to avoid.
     */
    public static function compose(string $prefix): string
    {
        $floor = (int) str_pad('1', self::DIGITS, '0');      // 100000
        $ceil = (int) str_repeat('9', self::DIGITS);         // 999999

        return $prefix.'-'.random_int($floor, $ceil);
    }

    /** Is this the shape of one? Used by tests and by search. */
    public static function looksValid(?string $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // The digit count is a minimum, not a maximum — see DIGITS.
        return (bool) preg_match('/^[A-Z]{2,4}-\d{'.self::DIGITS.',}$/', $value);
    }
}
