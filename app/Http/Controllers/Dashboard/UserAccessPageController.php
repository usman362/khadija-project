<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessPageController
{
    public function index(): View
    {
        return view('dashboard.users.index', [
            'users' => User::query()->with(['roles', 'permissions'])->latest()->paginate(15),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if ($request->user()->can('users.update_roles_permissions')) {
            $user->syncRoles($validated['roles'] ?? []);
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        return back()->with('status', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $changingPassword = filled($validated['password'] ?? null);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $changingPassword ? $validated['password'] : $user->password,
        ]);

        /*
         * A new password here lifts the R56 lock and ends their open sessions.
         *
         * Sir Peter, 26 October, locked out of the client account: "there are
         * many users trying to access it so 3 incorrect passwords limits
         * should not be the issue... now i can gain access lol."
         *
         * Setting a password from this screen used to leave the lock exactly
         * where it was, because only the password-reset flow called release().
         * So an administrator could give somebody a working password and watch
         * them stay locked out, with nothing on screen explaining why. That is
         * the same confusion that cost a day on the admin account the day
         * before, arriving from a different direction.
         *
         * The sessions go too: a password change that leaves old sessions
         * signed in has not actually taken anything back.
         */
        if ($changingPassword) {
            \App\Support\LoginLockout::release($user->fresh());

            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if ($request->user()->can('users.update_roles_permissions')) {
            $user->syncRoles($validated['roles'] ?? []);
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        return back()->with('status', 'User updated successfully.');
    }

    /**
     * Lift a login lock, without touching the password.
     *
     * R56 locks an account after three wrong passwords and keeps it locked
     * until a password reset is completed. On an address that cannot receive
     * mail, which both demo accounts and the admin account are, that reset
     * never arrives and the account is simply shut.
     *
     * This is on an admin screen rather than anywhere public. The objection to
     * unlocking over HTTP is that it becomes a way around the lock; it does
     * not apply to somebody who can already set this user's password, change
     * their email, alter their roles and delete them outright from the same
     * page.
     */
    public function unlock(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('users.update'), 403);

        if ($user->login_locked_at === null) {
            // Still clear the counter: two failures on record means the next
            // wrong password locks it again immediately, which reads as the
            // unlock not having worked.
            \App\Support\LoginLockout::release($user);

            return back()->with('status', $user->name . ' was not locked. Failed attempts cleared.');
        }

        \App\Support\LoginLockout::release($user);

        return back()->with('status', $user->name . ' can sign in again with their existing password.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return back()->with('status', 'User deleted successfully.');
    }
}
