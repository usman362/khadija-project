<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Whether somebody is here right now.
 *
 * Sir Peter asked for status dots on avatars. The chat used to carry a note
 * saying this could not be done — "nothing records whether someone is online,
 * and a green dot we cannot back is a claim" — and that was true when it was
 * written. TouchLastActive has since stamped users.last_active_at on every
 * signed-in request, so the dot has something behind it.
 *
 * Three answers, not two:
 *
 *   here     — seen inside the window. Green.
 *   away     — we know when they were last here, and it was longer ago. Grey,
 *              with the time, because "offline" alone loses the only useful
 *              part: whether it was four minutes or four weeks.
 *   unknown  — we have never recorded them. No dot. An account that predates
 *              the stamp, or one that has not signed in since, is not offline;
 *              it is unmeasured, and a grey dot would say we checked.
 *
 * Do Not Disturb is deliberately absent. It exists in the message dock, but
 * it lives in that browser's localStorage: it silences your own sound and
 * never leaves your machine, so nobody else can be shown it. Making it a
 * state other people see means storing it on the account, which changes what
 * the setting means and touches every role's dock.
 */
final class Presence
{
    /**
     * How recently counts as here.
     *
     * TouchLastActive writes at most every five minutes, so a stamp is up to
     * five minutes stale while someone is sitting there reading. A window of
     * five would blink them out; six is the write interval plus room.
     */
    public const WINDOW_MINUTES = 6;

    public const HERE = 'here';

    public const AWAY = 'away';

    public const UNKNOWN = 'unknown';

    /** Green for here, grey for away, nothing for never measured. */
    public const COLOURS = [
        self::HERE => '#10b981',
        self::AWAY => '#9ca3af',
    ];

    public static function stateOf(?User $user): string
    {
        $at = self::lastSeen($user);

        if (! $at) {
            return self::UNKNOWN;
        }

        return $at->gt(now()->subMinutes(self::WINDOW_MINUTES)) ? self::HERE : self::AWAY;
    }

    public static function isHere(?User $user): bool
    {
        return self::stateOf($user) === self::HERE;
    }

    public static function colour(?User $user): ?string
    {
        return self::COLOURS[self::stateOf($user)] ?? null;
    }

    /**
     * What the dot means, in words, for a title and for screen readers.
     *
     * Never "Offline": we know when they were last here, and saying so is
     * both truer and more use than a word that hides it.
     */
    public static function label(?User $user): ?string
    {
        $at = self::lastSeen($user);

        return match (self::stateOf($user)) {
            self::HERE => 'Online now',
            self::AWAY => 'Last active ' . $at->diffForHumans(),
            default    => null,
        };
    }

    private static function lastSeen(?User $user): ?Carbon
    {
        // A narrowed eager load that leaves the column out answers null, which
        // would read as "never here" for someone sitting on the page. Unknown
        // is the honest answer to a question that was not asked.
        if (! $user || ! array_key_exists('last_active_at', $user->getAttributes())) {
            return null;
        }

        return $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
    }
}
