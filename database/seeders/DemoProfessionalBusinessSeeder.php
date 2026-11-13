<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DemoBusiness;
use Database\Seeders\Concerns\OnlyOutsideProduction;
use Illuminate\Database\Seeder;

/**
 * A demonstration professional with a business behind them.
 *
 * The dashboard was built panel by panel against Khadijah's reference screen
 * and every panel was correct, but the account anybody signs in as had no
 * bookings at all. So the page did exactly what it is supposed to do when a
 * professional has done no work: it said "Nothing booked for today", "Not
 * enough of your own bookings yet", "You have not set a goal", and showed
 * $0.00 four times. Against the reference, which shows a working season, that
 * reads as an unfinished page rather than an empty account.
 *
 * The work itself lives in App\Support\DemoBusiness, because a seeder is the
 * one place it could not be used for a demonstration on the live site: a
 * seeder runs as a set, against everything, and refuses on production. The
 * `demo:business` command drives the same code one named account at a time.
 *
 * LOCAL AND DEMO ONLY. Idempotent: everything is matched on a marker before
 * it is made, so a second run adds nothing.
 */
class DemoProfessionalBusinessSeeder extends Seeder
{
    use OnlyOutsideProduction;

    public function run(): void
    {
        if ($this->refusedOnProduction()) {
            return;
        }

        $client = User::where('email', 'client@example.com')->first();

        if (! $client) {
            $this->command?->warn('  No demo client, so there is nobody to book the demo work.');

            return;
        }

        /*
         * The demo LOGINS only.
         *
         * Not every professional on the database: the sixteen generated
         * profiles exist to fill search results, and giving each of them a
         * year of bookings would put invented money on accounts nobody signs
         * in as.
         */
        $pros = User::whereIn('email', ['professional@example.com', 'supplier@example.com'])->get();

        $demo    = new DemoBusiness;
        $touched = 0;

        foreach ($pros as $pro) {
            $why = $demo->build($pro, $client);

            if ($why !== null) {
                $this->command?->warn('  '.$why);

                continue;
            }

            $touched++;
        }

        $this->command?->info("Demo business seeded for {$touched} professionals.");
    }
}
