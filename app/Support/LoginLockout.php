<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rule R56 — max 3 incorrect password attempts per rolling 24 hours; the third
 * failure locks the account until a password reset is completed.
 *
 * Two halves that have to work together:
 *
 *   The COUNTER is a rate limiter, keyed to the email, decaying over 24 hours.
 *   Two failures a day apart never lock anything.
 *
 *   The LOCK is a column on the user. It has to outlive the counter, because
 *   R56 rules out a self-unlock after a cooldown and every rate limiter
 *   unlocks by expiring. Only `release()` clears it, and only the password
 *   reset flow calls that.
 *
 * Only a real account is ever locked. Failures against an address that has no
 * account still count toward the limiter — so guessing addresses is throttled
 * — but there is nothing to lock, and a stranger cannot create a locked record
 * for an email that never signed up.
 */
final class LoginLockout
{
    public const MAX_ATTEMPTS = 3;
    public const WINDOW_HOURS = 24;

    /** Is this account locked out until it resets its password? */
    public static function isLocked(?string $email): bool
    {
        return self::userFor($email)?->login_locked_at !== null;
    }

    /**
     * Record one failed attempt, and lock the account if it was the third.
     *
     * Returns true when this attempt is the one that locked it, so the caller
     * can say so rather than repeating the generic "wrong password".
     */
    public static function recordFailure(?string $email): bool
    {
        RateLimiter::hit(self::key($email), self::WINDOW_HOURS * 3600);

        if (RateLimiter::attempts(self::key($email)) < self::MAX_ATTEMPTS) {
            return false;
        }

        $user = self::userFor($email);

        if ($user === null || $user->login_locked_at !== null) {
            return false;
        }

        $user->forceFill(['login_locked_at' => now()])->saveQuietly();

        return true;
    }

    /** A clean sign-in clears the counter; it does NOT clear a lock. */
    public static function clearCounter(?string $email): void
    {
        RateLimiter::clear(self::key($email));
    }

    /**
     * Lift the lock. The password reset flow is the only caller — that is the
     * whole of R56's "until password reset/recovery is completed".
     */
    public static function release(User $user): void
    {
        if ($user->login_locked_at !== null) {
            $user->forceFill(['login_locked_at' => null])->saveQuietly();
        }

        RateLimiter::clear(self::key($user->email));
    }

    /** How many tries are left before the account locks. */
    public static function remaining(?string $email): int
    {
        return max(0, self::MAX_ATTEMPTS - RateLimiter::attempts(self::key($email)));
    }

    private static function key(?string $email): string
    {
        return 'login-lock:' . mb_strtolower(trim((string) $email));
    }

    private static function userFor(?string $email): ?User
    {
        $email = trim((string) $email);

        return $email === '' ? null : User::where('email', $email)->first();
    }
}
