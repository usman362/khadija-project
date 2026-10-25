<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An administrator signs in from the admin portal, and nowhere else.
 *
 * Reported 25 October: the ordinary sign-in page was letting an administrator
 * straight into the admin side. Both forms post to the same route, so nothing
 * could tell one door from the other.
 *
 * The admin form now carries a hidden field naming its door. That is not a
 * permission and is not treated as one: anything a browser sends, anybody can
 * send. It is read only AFTER the password has been accepted, so the sign-in
 * page never answers "is this address an administrator?" to somebody who does
 * not already have the password.
 */
class AdminsSignInAtTheAdminDoorTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['password' => 'correct-horse', 'primary_role' => $role]);
        $user->assignRole($role);

        return User::findOrFail($user->id);
    }

    private function signIn(User $user, array $extra = [], string $password = 'correct-horse')
    {
        return $this->post(route('login'), $extra + [
            'email'    => $user->email,
            'password' => $password,
        ]);
    }

    // ── The front door ───────────────────────────────────────

    public function test_an_admin_is_turned_away_from_the_ordinary_sign_in_page(): void
    {
        $admin = $this->person('admin');

        $this->signIn($admin)
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', 'Administrators sign in from the admin portal.');

        $this->assertGuest(); // signed out again, not merely redirected
    }

    public function test_an_admin_signs_in_at_the_admin_portal(): void
    {
        $admin = $this->person('admin');

        $this->signIn($admin, ['portal' => 'admin'])->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    // ── And the admin door is not a second front door ─────────

    /** The page says "Not an admin? Go to user login", so it means it. */
    public function test_a_client_is_sent_back_from_the_admin_portal(): void
    {
        $client = $this->person('client');

        $this->signIn($client, ['portal' => 'admin'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_a_client_signs_in_at_the_ordinary_page(): void
    {
        $client = $this->person('client');

        $this->signIn($client)->assertRedirect();

        $this->assertAuthenticatedAs($client);
    }

    // ── What the hidden field is not ─────────────────────────

    /**
     * Claiming the admin door does not make somebody an admin.
     *
     * The field says which page was used. It is never read as permission, so a
     * client who posts portal=admin is still only a client, and is turned back.
     */
    public function test_posting_the_admin_field_grants_nothing(): void
    {
        $client = $this->person('client');

        $this->signIn($client, ['portal' => 'admin']);

        $this->assertGuest();
        $this->assertFalse($client->fresh()->hasRole('admin'));
    }

    /**
     * A wrong password at the ordinary page says nothing about being an admin.
     *
     * This is why the check runs after authentication. Before it, the sign-in
     * page would tell anybody who typed an administrator's address that it
     * belonged to an administrator.
     */
    public function test_a_wrong_password_reveals_nothing_about_the_account(): void
    {
        $admin  = $this->person('admin');
        $client = $this->person('client');

        $forAdmin  = $this->signIn($admin, [], 'wrong')->assertRedirect();
        $forClient = $this->signIn($client, [], 'wrong')->assertRedirect();

        $this->assertSame(
            $forAdmin->getSession()->get('errors')->first('email'),
            $forClient->getSession()->get('errors')->first('email'),
            'The two answers differ, so a wrong password tells you which is an admin.',
        );
    }

    /** The admin form actually carries the field, or none of this applies. */
    public function test_the_admin_page_names_its_own_door(): void
    {
        $this->get(route('admin.login'))
            ->assertSuccessful()
            ->assertSee('name="portal"', false)
            ->assertSee('value="admin"', false);
    }

    /** And the ordinary page does not. */
    public function test_the_ordinary_page_does_not(): void
    {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertDontSee('name="portal"', false);
    }
}
