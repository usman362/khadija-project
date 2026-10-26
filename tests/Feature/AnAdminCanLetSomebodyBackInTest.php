<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * An administrator can let somebody back in from the admin screen.
 *
 * Sir Peter, 26 October, locked out of the client account minutes after its
 * password was changed: "For your security this account is paused after 3
 * incorrect passwords... there are many users trying to access it so 3
 * incorrect passwords limits should not be the issue... now i can gain access
 * lol."
 *
 * Two things were wrong, and the second is the worse one.
 *
 * There was no way to lift a lock except the console. R56's only exit is a
 * password reset, and these accounts are on addresses that accept no mail, so
 * there was no exit at all for the person who owns the site.
 *
 * And setting a password from the admin screen did NOT lift the lock, because
 * only the password-reset flow called release(). An administrator could hand
 * somebody a working password and watch them stay shut out, with nothing on
 * the page saying the account was locked in the first place. That is the same
 * confusion that cost a day on the admin account, arriving from another
 * direction.
 */
class AnAdminCanLetSomebodyBackInTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return User::findOrFail($admin->id);
    }

    private function lockedOut(string $email = 'shut@gigresource.com'): User
    {
        $user = User::factory()->create(['email' => $email, 'password' => 'their-password']);

        for ($i = 0; $i < LoginLockout::maxAttempts(); $i++) {
            LoginLockout::recordFailure($email);
        }

        $this->assertTrue(LoginLockout::isLocked($email), 'The account did not lock.');

        return $user->fresh();
    }

    // ── The unlock action ────────────────────────────────────

    public function test_an_admin_lifts_the_lock(): void
    {
        $user = $this->lockedOut();

        $this->actingAs($this->admin())
            ->post(route('app.users.unlock', $user))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertFalse(LoginLockout::isLocked($user->email));
        $this->assertNull($user->fresh()->login_locked_at);
    }

    /** And their existing password still works, because it was never touched. */
    public function test_unlocking_leaves_the_password_alone(): void
    {
        $user = $this->lockedOut();

        $this->actingAs($this->admin())->post(route('app.users.unlock', $user));

        $this->assertTrue(Hash::check('their-password', $user->fresh()->password));
    }

    /** The counter goes too, or the next wrong password locks it again at once. */
    public function test_unlocking_clears_the_attempts_behind_it(): void
    {
        $user = $this->lockedOut();

        $this->actingAs($this->admin())->post(route('app.users.unlock', $user));

        $this->assertFalse(
            LoginLockout::recordFailure($user->email),
            'One wrong password locked it again, so the counter survived.',
        );
    }

    /** Only an admin. */
    public function test_a_client_cannot_unlock_anybody(): void
    {
        $admin  = $this->admin();   // seeds the roles
        $user   = $this->lockedOut();
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client->fresh())
            ->post(route('app.users.unlock', $user))
            ->assertForbidden();

        $this->assertTrue(LoginLockout::isLocked($user->email));
    }

    // ── Setting a password from that screen ──────────────────

    /**
     * It lifts the lock as well, which it did not before.
     *
     * The giving of a working password that still does not work is the exact
     * shape of the problem reported.
     */
    public function test_setting_a_password_there_also_lets_them_back_in(): void
    {
        $user = $this->lockedOut();

        $this->actingAs($this->admin())
            ->patch(route('app.users.update', $user), [
                'name'     => $user->name,
                'email'    => $user->email,
                'password' => 'a-brand-new-one',
            ])
            ->assertRedirect();

        $this->assertFalse(LoginLockout::isLocked($user->email), 'Still locked, with a new password.');
        $this->assertTrue(Hash::check('a-brand-new-one', $user->fresh()->password));
    }

    /**
     * And ends their open sessions.
     *
     * A password change that leaves old sessions signed in has not taken
     * anything back.
     */
    public function test_setting_a_password_there_ends_their_sessions(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'sess-1', 'user_id' => $user->id, 'ip_address' => '198.51.100.1',
            'user_agent' => 'Test', 'payload' => base64_encode('x'), 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin())->patch(route('app.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'password' => 'replaced',
        ]);

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    /** Editing a name is not a password change and leaves sessions alone. */
    public function test_editing_without_a_password_leaves_sessions_signed_in(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'sess-2', 'user_id' => $user->id, 'ip_address' => '198.51.100.2',
            'user_agent' => 'Test', 'payload' => base64_encode('x'), 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin())->patch(route('app.users.update', $user), [
            'name' => 'A New Name', 'email' => $user->email,
        ]);

        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    // ── The screen says so ───────────────────────────────────

    /** A locked account is marked, so nobody has to guess why they cannot get in. */
    public function test_the_users_screen_shows_who_is_locked_out(): void
    {
        $locked = $this->lockedOut();
        $fine   = User::factory()->create(['email' => 'fine@gigresource.com']);

        $html = $this->actingAs($this->admin())->get(route('app.users.index'))
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('Locked out', $html);
        $this->assertStringContainsString(route('app.users.unlock', $locked), $html);
        $this->assertStringNotContainsString(route('app.users.unlock', $fine), $html);
    }
}
