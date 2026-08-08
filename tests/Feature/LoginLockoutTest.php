<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginLockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Rule R56, locked 2026-08-06: max 3 incorrect passwords per rolling 24-hour
 * period; the third failure locks the account until a password reset is
 * completed — explicitly "no self-unlock after a cooldown".
 *
 * Laravel's stock behaviour was 5 tries with a one-minute cooldown that lifts
 * itself, so all three numbers were wrong and the cooldown had to go entirely.
 * That last part is what forces a column: a rate limiter only ever unlocks by
 * expiring, which is the one thing R56 rules out.
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login-lock:someone@example.com');

        // An early version of this called `parent::` on methods that come
        // from a trait. Every request 500'd, and these tests still passed —
        // they only read the database, and the counter had already been
        // written by the time the response blew up. Letting exceptions
        // through is what makes the assertions mean anything.
        $this->withoutExceptionHandling([ValidationException::class]);
    }

    private function account(): User
    {
        return User::factory()->create([
            'email'    => 'someone@example.com',
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function attempt(string $password): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', [
            'email'    => 'someone@example.com',
            'password' => $password,
        ]);
    }

    public function test_two_wrong_passwords_do_not_lock_the_account(): void
    {
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');

        $this->assertNull($user->fresh()->login_locked_at);
        $this->assertSame(1, LoginLockout::remaining('someone@example.com'));
    }

    public function test_the_right_password_still_works_after_two_failures(): void
    {
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');
        $this->attempt(self::PASSWORD);

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->login_locked_at);
    }

    public function test_the_third_wrong_password_locks_the_account(): void
    {
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');
        $this->attempt('wrong-three');

        $this->assertNotNull($user->fresh()->login_locked_at);
    }

    public function test_a_locked_account_is_refused_even_with_the_right_password(): void
    {
        // The point of the rule. If remembering the password got you back in,
        // the lock would be a speed bump rather than a lock.
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');
        $this->attempt('wrong-three');
        $this->attempt(self::PASSWORD);

        $this->assertGuest();
    }

    public function test_waiting_does_not_unlock_it(): void
    {
        // R56's "no self-unlock after a cooldown", stated as a test. The rate
        // limiter's own 24-hour window has expired here; the lock has not.
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');
        $this->attempt('wrong-three');

        $this->travel(25)->hours();
        RateLimiter::clear('login-lock:someone@example.com');

        $this->attempt(self::PASSWORD);

        $this->assertGuest();
    }

    public function test_a_completed_password_reset_lifts_the_lock(): void
    {
        $user = $this->account();
        $user->forceFill(['login_locked_at' => now()])->saveQuietly();

        $token = Password::broker()->createToken($user);

        $response = $this->post('/password/reset', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->login_locked_at);
    }

    public function test_the_message_says_how_to_get_back_in(): void
    {
        // R26 bars "Blocked"/"Rejected"/"Unsupported", but there is a plainer
        // reason to check the wording: a reset is the ONLY way out, so an
        // account whose message omits it is a dead end.
        $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');
        $response = $this->attempt('wrong-three');

        $response->assertSessionHasErrors('email');
        $message = session('errors')->first('email');

        $this->assertStringContainsString('Reset your password', $message);
        foreach (['Blocked', 'Rejected', 'Unsupported'] as $banned) {
            $this->assertStringNotContainsString($banned, $message);
        }
    }

    public function test_three_failures_against_an_unknown_address_lock_nothing(): void
    {
        // Attempts on an address with no account are still counted, so
        // guessing addresses is throttled — but a stranger must not be able
        // to leave a locked record behind for an email that never signed up.
        foreach (['a', 'b', 'c'] as $try) {
            $this->post('/login', ['email' => 'nobody@example.com', 'password' => $try]);
        }

        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.com']);
        $this->assertFalse(LoginLockout::isLocked('nobody@example.com'));
    }

    public function test_the_window_is_a_rolling_twenty_four_hours(): void
    {
        // Two failures today and one tomorrow is not three in a day.
        $user = $this->account();

        $this->attempt('wrong-one');
        $this->attempt('wrong-two');

        $this->travel(25)->hours();
        RateLimiter::clear('login-lock:someone@example.com');   // the window has passed

        $this->attempt('wrong-three');

        $this->assertNull($user->fresh()->login_locked_at);
    }
}
