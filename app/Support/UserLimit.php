<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * How often one account may do a thing.
 *
 * Khadijah's revised sheet, 29 Aug 2026. See config/limits.php for the numbers
 * and for what is deliberately NOT here.
 *
 * Two rules this follows, both from her framing:
 *
 *   It counts what SUCCEEDED. A request refused by validation has not used
 *   anything up — being told a field is missing must not cost somebody one of
 *   their ten postings for the day.
 *
 *   It says when. "Try again later" leaves a person guessing; every refusal
 *   carries the actual wait, so they know whether to wait five minutes or come
 *   back tomorrow.
 *
 * Admins are exempt. Support answering ten people in a row is the job, and an
 * admin hitting a spam limit is the limit misfiring.
 */
final class UserLimit
{
    /**
     * Record one use and stop the request if that puts them over.
     *
     * Call it AFTER the work has been validated and is about to happen.
     *
     * @throws ValidationException  so the message lands on the form the person
     *                              is looking at, rather than a bare 429 page
     */
    public static function hit(string $rule, ?User $user = null, ?string $scope = null, string $field = 'limit'): void
    {
        if (! self::applies($rule, $user)) {
            return;
        }

        [$config, $key] = self::resolve($rule, $user, $scope);

        // A window of zero minutes means the count is per SCOPE and never
        // expires — invitations to one event, not invitations this week.
        $decay = $config['minutes'] > 0 ? $config['minutes'] * 60 : 60 * 60 * 24 * 365 * 5;

        if (RateLimiter::tooManyAttempts($key, $config['max'])) {
            throw ValidationException::withMessages([
                $field => self::messageFor($config, $key),
            ]);
        }

        RateLimiter::hit($key, $decay);
    }

    /** How many of this rule the account has left. Null when it does not apply. */
    public static function remaining(string $rule, ?User $user = null, ?string $scope = null): ?int
    {
        if (! self::applies($rule, $user)) {
            return null;
        }

        [$config, $key] = self::resolve($rule, $user, $scope);

        return RateLimiter::remaining($key, $config['max']);
    }

    /** Give somebody one back — used when an action is undone rather than done. */
    public static function release(string $rule, ?User $user = null, ?string $scope = null): void
    {
        if (! self::applies($rule, $user)) {
            return;
        }

        [, $key] = self::resolve($rule, $user, $scope);

        // RateLimiter has no decrement; this is the closest honest thing.
        $attempts = RateLimiter::attempts($key);

        if ($attempts > 0) {
            RateLimiter::clear($key);

            for ($i = 1; $i < $attempts; $i++) {
                RateLimiter::hit($key);
            }
        }
    }

    /** Wipe a rule for one account. For tests and for support unblocking somebody. */
    public static function clear(string $rule, ?User $user = null, ?string $scope = null): void
    {
        [, $key] = self::resolve($rule, $user, $scope);

        RateLimiter::clear($key);
    }

    private static function applies(string $rule, ?User $user): bool
    {
        if (! config('limits.enabled', true)) {
            return false;
        }
        if (! config("limits.rules.{$rule}")) {
            return false;
        }

        // Support doing its job is not abuse.
        return ! ($user ?? auth()->user())?->isAdmin();
    }

    /** @return array{0: array, 1: string} */
    private static function resolve(string $rule, ?User $user, ?string $scope): array
    {
        $config = config("limits.rules.{$rule}", ['max' => PHP_INT_MAX, 'minutes' => 1, 'message' => '']);
        $user ??= auth()->user();

        // Signed out — the IP is the only identity there is. It is a blunt key
        // (a shared office looks like one person) and it only guards the two
        // rules that exist before an account does.
        $who = $user?->id ? 'u'.$user->id : 'ip'.request()->ip();

        return [$config, 'limit:'.$rule.':'.$who.($scope ? ':'.$scope : '')];
    }

    private static function messageFor(array $config, string $key): string
    {
        $message = $config['message'] ?? 'You have done that too many times. Please try again later.';
        $seconds = RateLimiter::availableIn($key);

        // A per-scope rule has no wait — it is a total, not a window.
        if (($config['minutes'] ?? 0) === 0) {
            return $message;
        }

        return $message.' '.self::wait($seconds);
    }

    private static function wait(int $seconds): string
    {
        if ($seconds < 90) {
            return 'Try again in about a minute.';
        }
        if ($seconds < 3600) {
            return 'Try again in about '.max(1, (int) round($seconds / 60)).' minutes.';
        }

        return 'Try again in about '.max(1, (int) round($seconds / 3600)).' hours.';
    }
}
