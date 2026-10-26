<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rule R56 — a run of incorrect passwords within a rolling 24 hours locks the
 * account until a password reset is completed.
 *
 * R56 SAID THREE. It is ten now, on Sir Peter's instruction of 26 October,
 * after he was shut out of the shared client account twice in an afternoon:
 * "there are many users trying to access it so 3 incorrect passwords limits
 * should not be the issue". He is right about this account. The count is kept
 * per address, not per person, so several people sharing one login have three
 * attempts between them, and one of them mistyping twice spends most of it.
 *
 * Ten still stops guessing: a password worth having is not found in ten tries,
 * and the lock after them is permanent until somebody lifts it rather than
 * expiring on a timer.
 *
 * The figure is a setting now, so the next revision of it needs no code.
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
    /** The agreed default. `config('auth.lockout.attempts')` overrides it. */
    public const DEFAULT_ATTEMPTS = 10;
    public const WINDOW_HOURS = 24;

    /**
     * How many wrong passwords an address gets before it is shut.
     *
     * Read through a method rather than used as a constant so the number can
     * be changed without a deploy. Everything that shows it to a person reads
     * this too, so the screen and the rule cannot disagree.
     */
    public static function maxAttempts(): int
    {
        return max(1, (int) config('auth.lockout.attempts', self::DEFAULT_ATTEMPTS));
    }

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

        if (RateLimiter::attempts(self::key($email)) < self::maxAttempts()) {
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
        return max(0, self::maxAttempts() - RateLimiter::attempts(self::key($email)));
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
