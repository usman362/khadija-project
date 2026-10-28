<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Put every service on one professional's profile, for testing.
 *
 * Ali, 28 October: the surviving professional on production offers nothing at
 * all, so it cannot bid on anything, which means it has no proposals, no
 * bookings and no agreements. Opening the new agreement screens on that
 * account shows an empty page and reads as "nothing was built". This fills it
 * in so the flow can be walked end to end with the two accounts that are left.
 *
 * A command rather than a seeder on purpose. The seeders run on their own
 * tables and CategorySeeder running once on this site made a second copy of
 * all 360 categories, which the client read as their pictures having been
 * deleted. This touches one account, names it, and says what it did.
 *
 * Writes through User::syncServices, which exists because the services and
 * the level-4 specialties share one pivot: serviceCategories()->sync() wipes
 * every specialty on its way past, since sync's delete ignores the kind
 * filter the relation puts on its reads.
 */
class GiveAProfessionalEveryService extends Command
{
    protected $signature = 'professional:offer-everything
                            {email : The professional account to fill in}
                            {--apply : Write it. Without this the command only reports.}';

    protected $description = 'Give one professional every active service, for testing';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No account with the email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $user->hasRole('professional') && $user->primary_role !== 'professional') {
            $this->error("{$user->email} is not a professional, so services would never be read.");

            return self::FAILURE;
        }

        /*
         * Level 3 only. Level 4 specialties sit in the same pivot and are a
         * different question; level 2 groups and event types are not services
         * somebody offers.
         */
        $services = Category::where('kind', Category::SERVICE)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $before = $user->serviceCategories()->count();

        $this->line("Account:  {$user->name} <{$user->email}>");
        $this->line("Offers now: {$before}");
        $this->line('Active services on the platform: ' . $services->count());

        /*
         * Distinct NAMES, not rows. The legacy tree repeats one service under
         * many event types, so 241 rows are far fewer real services, and a
         * count of rows read as a count of services is how category numbers
         * have been misquoted here before.
         */
        $this->line('Distinct service names: ' . $services->unique()->count());

        if (! $this->option('apply')) {
            $this->newLine();
            $this->warn('Nothing was written. Run it again with --apply.');

            return self::SUCCESS;
        }

        $user->syncServices($services->keys()->all());

        $after = $user->fresh()->serviceCategories()->count();

        $this->newLine();
        $this->info("Done. {$user->email} now offers {$after} services (was {$before}).");
        $this->line('Clients browsing any category can now find this account.');

        return self::SUCCESS;
    }
}
