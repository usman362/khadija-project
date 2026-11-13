<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DemoBusiness;
use Illuminate\Console\Command;

/**
 * Give ONE named professional account a business, so the dashboard can be
 * shown full on a site where nobody has worked yet.
 *
 * The seeders refuse on production, and that rule stays: `db:seed` runs
 * everything against everybody, and one of those seeders once left 47
 * duplicate categories on the live database. This is the other shape of the
 * same need — one account, named on the command line, dry by default, and
 * reversible.
 *
 * What it writes is invisible to everyone else. The jobs are awarded, so the
 * bidding board and the Opportunity Feed (which both ask for work with no
 * professional on it) cannot return them, and the money and reviews belong to
 * the two accounts involved. Nothing here reaches a real professional's
 * screen.
 *
 * The one thing it will not do is publish open requests. Those are the only
 * part of a demonstration business other people can see, and on a live site
 * that means real professionals reading, and bidding on, work that does not
 * exist. If the Opportunity Feed has to be full for a demonstration, post a
 * request from the demonstration client through the site itself and take it
 * down afterwards, so at least it is a real request that a real professional
 * could be told about.
 */
class BuildDemoBusiness extends Command
{
    protected $signature = 'demo:business
        {professional : email of the professional account to build the business on}
        {--client= : email of the client who books it (default: the demo client)}
        {--force : actually do it (otherwise this only says what it would do)}
        {--remove : take it all back off again}';

    protected $description = 'Give one professional account a demonstration business (dry run unless --force)';

    public function handle(): int
    {
        $pro = User::where('email', $this->argument('professional'))->first();

        if (! $pro) {
            $this->error('No account with that email.');
            $this->suggest('professional');

            return self::FAILURE;
        }

        if (! $pro->hasRole('professional')) {
            $this->error($pro->email.' is not a professional account.');
            $this->suggest('professional');

            return self::FAILURE;
        }

        $demo = new DemoBusiness;

        return $this->option('remove')
            ? $this->take($demo, $pro)
            : $this->give($demo, $pro);
    }

    /**
     * Name the accounts that would have worked.
     *
     * The demonstration logins were created as name@example.com and renamed
     * to the live domain afterwards, so the defaults here are wrong on the
     * live site and right everywhere else. Guessing a replacement would be
     * worse than saying what is actually there: this command writes a year of
     * bookings onto whichever account it is pointed at.
     */
    private function suggest(string $role): void
    {
        $found = User::whereHas('roles', fn ($q) => $q->where('name', $role))
            ->orderBy('id')
            ->limit(12)
            ->get(['name', 'email']);

        if ($found->isEmpty()) {
            $this->line("There are no {$role} accounts on this database at all.");

            return;
        }

        $this->newLine();
        $this->line(ucfirst($role).' accounts here:');

        foreach ($found as $one) {
            $this->line(sprintf('  %-38s %s', $one->email, $one->name));
        }
    }

    private function give(DemoBusiness $demo, User $pro): int
    {
        $email  = $this->option('client') ?: 'client@example.com';
        $client = User::where('email', $email)->first();

        if (! $client || ! $client->hasRole('client')) {
            $this->error("No client account at {$email}.");
            $this->suggest('client');

            return self::FAILURE;
        }

        if ($pro->is($client)) {
            $this->error('A professional cannot book their own work.');

            return self::FAILURE;
        }

        $this->line("Professional:  {$pro->name} <{$pro->email}>");
        $this->line("Booked by:     {$client->name} <{$client->email}>");
        $this->newLine();

        foreach ($demo->plan($pro) as $what => $many) {
            $this->line(sprintf('  %-36s %d', $what, $many));
        }

        $this->newLine();
        $this->line('All of it is awarded work between these two accounts, so it');
        $this->line('cannot appear on anybody else\'s board, feed or search.');
        $this->line('No open requests are published. Take it off again with --remove.');
        $this->newLine();

        if (! $this->option('force')) {
            $this->warn('Dry run. Nothing written. Add --force to do it.');

            return self::SUCCESS;
        }

        if (app()->isProduction() && ! $this->confirm('This is PRODUCTION. Build it anyway?', false)) {
            $this->line('Stopped.');

            return self::SUCCESS;
        }

        $why = $demo->build($pro, $client);

        if ($why !== null) {
            $this->error($why);

            return self::FAILURE;
        }

        $this->info('Done. Open the professional dashboard to see it.');

        return self::SUCCESS;
    }

    private function take(DemoBusiness $demo, User $pro): int
    {
        if (! $this->option('force')) {
            $this->warn('Dry run. Add --force to actually remove it.');
            $this->line('It would delete every row carrying '.DemoBusiness::MARK.' on '.$pro->email.'.');

            return self::SUCCESS;
        }

        $counts = $demo->remove($pro);

        foreach ($counts as $what => $many) {
            $this->line(sprintf('  removed %-10s %d', $what, $many));
        }

        $this->newLine();
        $this->line('Left alone: the travel radius, the revenue goal and the identity');
        $this->line('verification on the profile. Those are settings somebody may have');
        $this->line('meant to keep, and a preference is not the same as data.');

        return self::SUCCESS;
    }
}
