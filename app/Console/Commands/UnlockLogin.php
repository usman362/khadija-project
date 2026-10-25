<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\LoginLockout;
use Illuminate\Console\Command;

/**
 * Lift a login lock from the server, when the password reset cannot be used.
 *
 * Rule R56 locks an account after three wrong passwords and keeps it locked
 * "until password reset/recovery is completed". That is deliberate and it
 * works. What was missing is what happens when the reset itself cannot reach
 * anybody.
 *
 * Found on 25 October, with the live admin account locked: there is exactly
 * one admin, and its address is admin@example.com. example.com is a reserved
 * domain that accepts no mail, so the reset email goes nowhere. The only way
 * out of the lock was a route that account cannot travel, and there was no
 * command to lift it either. The owner was shut out of their own site.
 *
 * This is deliberately a console command and not a screen. Anything on the web
 * that lifts a login lock is a way around the lock, which is the thing R56
 * exists to prevent. Reaching a shell means you already hold the server.
 */
class UnlockLogin extends Command
{
    protected $signature = 'login:unlock
                            {email : The account to unlock}
                            {--new-email= : Also change the address, for an account that cannot receive mail}';

    protected $description = 'Lift the R56 login lock from an account';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account with that address.');

            return self::FAILURE;
        }

        $this->line('Account: ' . $user->name . ' (' . $user->email . ')');
        $this->line('Roles:   ' . ($user->getRoleNames()->implode(', ') ?: 'none'));
        $this->line('Locked:  ' . ($user->login_locked_at ?? 'no'));

        if ($new = $this->option('new-email')) {
            if (User::where('email', $new)->whereKeyNot($user->id)->exists()) {
                $this->error('Another account already uses ' . $new . '.');

                return self::FAILURE;
            }

            $user->forceFill(['email' => $new])->saveQuietly();

            $this->info('Address changed to ' . $new . '. A password reset can reach this account now.');
        }

        if ($user->login_locked_at === null) {
            // Still clear the counter: two failures already recorded means the
            // next wrong password locks it again straight away.
            LoginLockout::release($user->fresh());

            $this->info('Not locked. Failed-attempt counter cleared.');

            return self::SUCCESS;
        }

        LoginLockout::release($user->fresh());

        $this->info('Unlocked. They can sign in with their existing password.');

        return self::SUCCESS;
    }
}
