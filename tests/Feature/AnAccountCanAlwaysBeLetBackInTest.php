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

        for ($i = 0; $i < LoginLockout::maxAttempts(); $i++) {
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

    // ── How many tries somebody gets ─────────────────────────

    /**
     * Ten, which is Sir Peter's revision of R56 on 26 October.
     *
     * He was shut out of the shared client account twice in an afternoon:
     * "there are many users trying to access it so 3 incorrect passwords
     * limits should not be the issue". The count is per address, not per
     * person, so several people sharing one login had three attempts between
     * them.
     */
    public function test_the_limit_is_the_one_he_asked_for(): void
    {
        $this->assertSame(10, LoginLockout::maxAttempts());
    }

    /** Nine wrong passwords is still nine chances, not a lock. */
    public function test_it_does_not_lock_before_the_limit(): void
    {
        $email = 'nearly@gigresource.com';
        User::factory()->create(['email' => $email]);

        foreach (range(1, LoginLockout::maxAttempts() - 1) as $i) {
            LoginLockout::recordFailure($email);

            $this->assertFalse(LoginLockout::isLocked($email), "Locked on attempt {$i}.");
        }

        LoginLockout::recordFailure($email);

        $this->assertTrue(LoginLockout::isLocked($email), 'It never locked at all.');
    }

    /**
     * And the screen quotes the real figure.
     *
     * The message was written with the number spelled into it once. A rule
     * that changes and a sentence that does not is how a screen starts lying.
     */
    public function test_the_message_quotes_the_real_limit(): void
    {
        $user = $this->locked();

        $message = $this->post(route('login'), ['email' => $user->email, 'password' => 'whatever'])
            ->getSession()->get('errors')->first('email');

        $this->assertStringContainsString((string) LoginLockout::maxAttempts(), $message);
    }

    /** It is a setting, so the next revision needs no code. */
    public function test_the_limit_can_be_changed_without_touching_code(): void
    {
        config(['auth.lockout.attempts' => 4]);

        $this->assertSame(4, LoginLockout::maxAttempts());
    }

    // ── The lock itself is unchanged ─────────────────────────

    /**
     * Nothing unlocks an account without being an administrator.
     *
     * This test used to demand that NO web route could lift a lock at all,
     * which was the wrong rule written down too strongly. The objection is to
     * a way AROUND the lock, and that is about who can reach it, not about
     * HTTP. On 26 October the owner was locked out of an account whose address
     * accepts no mail, so R56's only exit did not exist and neither did a
     * screen: the console was the sole way back into his own site.
     *
     * An administrator can already set this user's password, change their
     * email, alter their roles and delete them from the same page, so
     * unlocking gives them nothing new. What must stay true is that nobody
     * else can reach it.
     */
    public function test_only_an_admin_can_lift_a_lock_over_the_web(): void
    {
        $unlocks = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($r) => str_contains(strtolower($r->uri()), 'unlock'));

        foreach ($unlocks as $route) {
            $guards = $route->gatherMiddleware();

            $this->assertContains('role:admin', $guards, $route->uri() . ' is not admin only.');
            $this->assertContains('permission:users.update', $guards, $route->uri() . ' has no permission on it.');
        }
    }

    /** And a signed-out stranger gets nowhere near it. */
    public function test_a_stranger_cannot_reach_the_unlock_route(): void
    {
        $user = $this->locked();

        $this->post(route('app.users.unlock', $user))->assertRedirect(route('login'));

        $this->assertTrue(LoginLockout::isLocked($user->email), 'It unlocked for nobody in particular.');
    }

    /** Three wrong passwords still lock an account. */
    public function test_three_wrong_passwords_still_lock_the_account(): void
    {
        $this->locked();
    }

    // ── What a throttled stranger is told ────────────────────

    /**
     * An address with no account is not "paused", and has no password to reset.
     *
     * The limiter counts failures against unknown addresses on purpose, so
     * that guessing addresses is throttled. Both situations reached one
     * message, so anybody who mistyped their email three times was told their
     * account was paused and to reset their password. There was no account and
     * no password, and the only instruction given led nowhere.
     *
     * This cost a day on 25 October: an admin account was reported locked, an
     * unlock command was written for it, and the account did not exist on that
     * server at all. The screen said paused, so it was believed.
     */
    public function test_an_address_with_no_account_is_not_told_it_is_paused(): void
    {
        $email = 'nobody@gigresource.com';

        for ($i = 0; $i < LoginLockout::maxAttempts(); $i++) {
            LoginLockout::recordFailure($email);
        }

        $this->assertFalse(LoginLockout::isLocked($email), 'There is nothing to lock.');

        $message = $this->post(route('login'), ['email' => $email, 'password' => 'whatever'])
            ->assertSessionHasErrors('email')
            ->getSession()->get('errors')->first('email');

        $this->assertStringNotContainsString('paused', $message);
        $this->assertStringNotContainsString('Reset your password', $message);
        $this->assertStringContainsString('Too many sign-in attempts', $message);
    }

    /** A genuinely locked account still gets told how to get back in. */
    public function test_a_locked_account_is_still_told_to_reset_its_password(): void
    {
        $user = $this->locked();

        $message = $this->post(route('login'), ['email' => $user->email, 'password' => 'whatever'])
            ->assertSessionHasErrors('email')
            ->getSession()->get('errors')->first('email');

        $this->assertStringContainsString('paused', $message);
        $this->assertStringContainsString('Reset your password', $message);
    }
}
