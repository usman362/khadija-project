<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Taking somebody's access away ends their sessions too.
 *
 * Sir Peter, 26 October: remove the fired designer's access, and change the
 * password because it was posted in a chat thread.
 *
 * The half that is easy to miss: changing a password signs nobody out. The
 * session driver here is `database`, so a session opened before the change
 * keeps working until it expires, whatever the password becomes. Somebody
 * signed in when they were let go stays signed in. These tests hold both
 * halves together, because only doing one of them looks exactly like having
 * done the job.
 */
class TakingAccessAwayActuallyTakesItAwayTest extends TestCase
{
    use RefreshDatabase;

    private function withOpenSessions(User $user, int $count = 3): User
    {
        foreach (range(1, $count) as $i) {
            DB::table('sessions')->insert([
                'id'            => 'session-' . $user->id . '-' . $i,
                'user_id'       => $user->id,
                'ip_address'    => '198.51.100.' . $i,
                'user_agent'    => 'Test',
                'payload'       => base64_encode('x'),
                'last_activity' => now()->timestamp,
            ]);
        }

        return $user;
    }

    public function test_it_ends_every_session_the_account_has_open(): void
    {
        $user = $this->withOpenSessions(User::factory()->create());

        $this->artisan('account:revoke', ['user' => $user->email])->assertSuccessful();

        $this->assertSame(
            0,
            DB::table('sessions')->where('user_id', $user->id)->count(),
            'They are still signed in on the devices they already had.',
        );
    }

    public function test_it_changes_the_password(): void
    {
        $user = User::factory()->create(['password' => 'the-leaked-one']);

        $this->artisan('account:revoke', ['user' => $user->id])->assertSuccessful();

        $this->assertFalse(
            Hash::check('the-leaked-one', $user->fresh()->password),
            'The password that was posted in a chat still works.',
        );
    }

    /** Somebody else's sessions are not collateral. */
    public function test_it_leaves_other_people_signed_in(): void
    {
        $gone  = $this->withOpenSessions(User::factory()->create());
        $stays = $this->withOpenSessions(User::factory()->create(), 2);

        $this->artisan('account:revoke', ['user' => $gone->id])->assertSuccessful();

        $this->assertSame(2, DB::table('sessions')->where('user_id', $stays->id)->count());
    }

    /** Sessions alone, for an account that is only being signed out. */
    public function test_the_password_can_be_left_alone(): void
    {
        $user = $this->withOpenSessions(User::factory()->create(['password' => 'still-mine']));

        $this->artisan('account:revoke', ['user' => $user->id, '--keep-password' => true])
            ->assertSuccessful();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertTrue(Hash::check('still-mine', $user->fresh()->password));
    }

    /** And the account can be held shut pending a decision. */
    public function test_it_can_lock_the_account_as_well(): void
    {
        $user = User::factory()->create();

        $this->artisan('account:revoke', ['user' => $user->id, '--disable' => true])
            ->assertSuccessful();

        $this->assertNotNull($user->fresh()->login_locked_at);
        $this->assertTrue(\App\Support\LoginLockout::isLocked($user->email));
    }

    public function test_an_unknown_account_fails_rather_than_pretending(): void
    {
        $this->artisan('account:revoke', ['user' => 'nobody@gigresource.com'])->assertFailed();
    }

    /**
     * The new password is never written anywhere it could be read later.
     *
     * The command exists because a password ended up in a chat thread. One
     * that ends up in a log file is the same problem with extra steps.
     */
    public function test_the_new_password_is_not_logged(): void
    {
        $user = User::factory()->create();
        $log  = storage_path('logs/laravel.log');
        $before = file_exists($log) ? filesize($log) : 0;

        $this->artisan('account:revoke', ['user' => $user->id])->assertSuccessful();

        $after = file_exists($log) ? file_get_contents($log) : '';
        $added = substr($after, $before);

        $this->assertStringNotContainsString('New password', $added);
    }
}
