<?php

namespace App\Domain\Messaging;

use App\Models\Conversation;
use App\Models\User;

/**
 * Who may message whom (Sir Peter's messaging rules, 23 September 2026).
 *
 * The four role documents set the same table from four sides:
 *
 *   Admin        anyone
 *   Client       Professional, Influencer, Admin      never another Client
 *   Professional Client, Admin                        never another Professional or an Influencer
 *   Influencer   Client, Admin                        never another Influencer or a Professional
 *
 * "The restriction must be enforced by the backend, not merely hidden in the
 * interface": a pair that is not allowed must not be reachable through Recent,
 * Unread, Favorites, search, a direct URL, the API, or a typed user id. So the
 * rule lives here and the policy, the lists and the recipient pickers all read
 * it, rather than each screen hiding rows its own way.
 */
class MessagingPairs
{
    /** Pairs that may never talk, each written once, roles in alphabetical order. */
    private const FORBIDDEN = [
        'client|client',
        'influencer|influencer',
        'influencer|professional',
        'professional|professional',
    ];

    /** The roles an account can register as, for filtering a recipient list. */
    public const ROLES = ['client', 'professional', 'influencer', 'admin', 'affiliate'];

    /**
     * Whose rules are live.
     *
     * The client side is the side being built (Ali, standing scope). The other
     * three rows above are the same documents' rules and switch on by adding
     * the role here — the table itself is already complete, so nothing has to
     * be worked out again when that side is built.
     */
    public const ENFORCED = ['client'];

    /** An admin talks to anyone; everyone else follows the table. */
    public static function allowed(?string $a, ?string $b): bool
    {
        $a = self::role($a);
        $b = self::role($b);

        if ($a === 'admin' || $b === 'admin') {
            return true;
        }

        $pair = [$a, $b];
        sort($pair);

        return ! in_array(implode('|', $pair), self::FORBIDDEN, true);
    }

    /** Whether this pair of people may hold a conversation. */
    public static function allows(User $a, User $b): bool
    {
        if (! self::enforcedFor($a) && ! self::enforcedFor($b)) {
            return true;
        }

        return self::allowed($a->primary_role, $b->primary_role);
    }

    /**
     * Whether this conversation is out of bounds for this person.
     *
     * Any participant they may not talk to closes the whole conversation, not
     * just that person's messages: a client added to a thread with another
     * client can read every word in it.
     */
    public static function blocks(User $viewer, Conversation $conversation): bool
    {
        if (! self::enforcedFor($viewer) || $viewer->isAdmin()) {
            return false;
        }

        return $conversation->participants
            ->where('id', '!=', $viewer->id)
            ->contains(fn (User $other) => ! self::allowed($viewer->primary_role, $other->primary_role));
    }

    /** The roles this person may not talk to; empty when their side is not enforced. */
    public static function barredRoles(User $viewer): array
    {
        if (! self::enforcedFor($viewer)) {
            return [];
        }

        return array_values(array_filter(self::ROLES, fn ($r) => ! self::allowed($viewer->primary_role, $r)));
    }

    /** The people this person may start a conversation with. */
    public static function scopeRecipients(\Illuminate\Database\Eloquent\Builder $query, User $viewer): \Illuminate\Database\Eloquent\Builder
    {
        if (! self::enforcedFor($viewer)) {
            return $query;
        }

        $barred = self::barredRoles($viewer);

        return $barred ? $query->whereNotIn('primary_role', $barred) : $query;
    }

    private static function enforcedFor(User $user): bool
    {
        return in_array(self::role($user->primary_role), self::ENFORCED, true);
    }

    private static function role(?string $role): string
    {
        return strtolower(trim((string) $role)) ?: 'member';
    }
}
