<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * There is always a way back into a locked account.
 *
 * Rule R56 locks an account after three wrong passwords and keeps it locked
 * until a password reset is completed. That is deliberate, and on 25 October it
 * did exactly what it says: the live admin account was locked out.
 *
 * The problem was what came next. There is one admin account and its address is
 * admin@example.com. example.com is a reserved domain that accepts no mail, so
 * the password reset, which is R56's only exit, could never arrive. There was
 * no command to lift a lock either. The owner was shut out of their own site
 * with no route back at all.
 *
 * So the lock is unchanged and a second exit exists, on the server rather than
 * on the web: a screen that lifts a login lock is a way around the lock.
 */
class AnAccountCanAlwaysBeLetBackInTest extends TestCase
{
    use RefreshDatabase;

    private function locked(string $email = 'someone@gigresource.com'): User
    {
        $user = User::factory()->create(['email' => $email]);

        for ($i = 0; $i < LoginLockout::MAX_ATTEMPTS; $i++) {
            LoginLockout::recordFailure($email);
        }

        $this->assertTrue(LoginLockout::isLocked($email), 'The account did not lock.');

        return $user->fresh();
    }

    public function test_the_command_lifts_the_lock(): void
    {
        $user = $this->locked();

        $this->artisan('login:unlock', ['email' => $user->email])
            ->assertSuccessful();

        $this->assertFalse(LoginLockout::isLocked($user->email));
        $this->assertNull($user->fresh()->login_locked_at);
    }

    /**
     * And clears the counter with it.
     *
     * Lifting the lock while two failures are still on record means the next
     * wrong password locks it again immediately, which reads as the unlock not
     * having worked.
     */
    public function test_unlocking_also_clears_the_attempts_behind_it(): void
    {
        $user = $this->locked();

        $this->artisan('login:unlock', ['email' => $user->email])->assertSuccessful();

        $this->assertFalse(
            LoginLockout::recordFailure($user->email),
            'One wrong password locked it again, so the counter survived the unlock.',
        );
    }

    /** An account that was never locked still gets its counter cleared. */
    public function test_an_unlocked_account_is_not_an_error(): void
    {
        $user = User::factory()->create(['email' => 'fine@gigresource.com']);

        LoginLockout::recordFailure($user->email);
        LoginLockout::recordFailure($user->email);

        $this->artisan('login:unlock', ['email' => $user->email])
            ->expectsOutputToContain('Not locked')
            ->assertSuccessful();

        $this->assertFalse(LoginLockout::recordFailure($user->email));
    }

    /**
     * The address can be corrected in the same breath.
     *
     * Which is the actual fix for the admin account: unlocking it changes
     * nothing about the fact that the next lock would strand it again.
     */
    public function test_the_address_can_be_corrected_so_a_reset_can_reach_them(): void
    {
        $user = $this->locked('admin@example.com');

        $this->artisan('login:unlock', [
            'email'       => 'admin@example.com',
            '--new-email' => 'admin@gigresource.com',
        ])->assertSuccessful();

        $this->assertSame('admin@gigresource.com', $user->fresh()->email);
        $this->assertFalse(LoginLockout::isLocked('admin@gigresource.com'));
    }

    /** It will not hand one person's address to another account. */
    public function test_an_address_in_use_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@gigresource.com']);
        $user = $this->locked();

        $this->artisan('login:unlock', [
            'email'       => $user->email,
            '--new-email' => 'taken@gigresource.com',
        ])->assertFailed();

        $this->assertTrue(LoginLockout::isLocked($user->email), 'It unlocked despite refusing.');
    }

    public function test_an_unknown_address_fails_rather_than_pretending(): void
    {
        $this->artisan('login:unlock', ['email' => 'nobody@gigresource.com'])->assertFailed();
    }

    // ── The lock itself is unchanged ─────────────────────────

    /** No web route lifts a lock, because that would be a way around it. */
    public function test_nothing_on_the_web_can_lift_a_lock(): void
    {
        $offenders = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($r) => str_contains(strtolower($r->uri()), 'unlock'))
            ->map(fn ($r) => $r->uri())
            ->values()
            ->all();

        $this->assertSame([], $offenders, 'These routes expose unlocking over HTTP.');
    }

    /** Three wrong passwords still lock an account. */
    public function test_three_wrong_passwords_still_lock_the_account(): void
    {
        $this->locked();
    }
}
