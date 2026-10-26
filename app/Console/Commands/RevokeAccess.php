<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Take somebody's access away, properly, in one step.
 *
 * Sir Peter, 26 October: "Don't we need to remove the fired designer access",
 * and then "please change the clients password like asap due a dumb fuck
 * freelancer posted in his messages".
 *
 * THE THING THAT IS EASY TO MISS: changing a password does not sign anybody
 * out. The session driver here is `database`, so an existing session keeps
 * working until it expires, whatever the password becomes. Somebody who was
 * signed in when they were let go stays signed in. So this does both, and
 * reports the number of sessions it ended, because that is the half nobody
 * thinks to check.
 *
 * A console command rather than a one-liner for the shell: the credential
 * one-liners used on 25 October lost most of a day to quoting, twice setting
 * one string and checking another.
 *
 * WHAT THIS CANNOT UNDO, and Peter should hear it from the start: the admin
 * account was shared with more than one person, so there is no individual
 * access to withdraw. Rotating it removes everybody at once, including him.
 * One account per person is what makes "remove this person" a thing that can
 * be done at all.
 */
class RevokeAccess extends Command
{
    protected $signature = 'account:revoke
                            {user : An id or an email address}
                            {--keep-password : End the sessions but leave the password alone}
                            {--disable : Also stop the account being used again, pending a decision}';

    protected $description = 'Change an account password and end every session it has open';

    public function handle(): int
    {
        $who = $this->argument('user');

        $user = ctype_digit((string) $who)
            ? User::find((int) $who)
            : User::where('email', trim((string) $who))->first();

        if (! $user) {
            $this->error('No account matches "' . $who . '".');

            return self::FAILURE;
        }

        $this->line('Account: ' . $user->name . ' <' . $user->email . '>  (id ' . $user->id . ')');
        $this->line('Roles:   ' . ($user->getRoleNames()->implode(', ') ?: 'none'));

        // Ended first. A password change on its own leaves every open session
        // working, which is the gap this command exists to close.
        $ended = DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->info($ended . ' open ' . Str::plural('session', $ended) . ' ended.');

        if (! $this->option('keep-password')) {
            $password = Str::password(20);

            $user->password = $password;
            $user->saveQuietly();

            // Printed once, to a terminal, and never written to a file or a
            // log. The whole reason this command was asked for is a password
            // that ended up in a chat thread.
            $this->newLine();
            $this->warn('New password (shown once, do not paste it into chat):');
            $this->line('  ' . $password);
            $this->newLine();
        }

        if ($this->option('disable')) {
            $user->forceFill(['login_locked_at' => now()])->saveQuietly();

            $this->info('Account locked. `php artisan login:unlock ' . $user->email . '` lifts it.');
        }

        return self::SUCCESS;
    }
}
