<?php

namespace Database\Seeders\Concerns;

/**
 * PM-2 / OA-25, the safeguard half: a demo seeder must not be able to plant
 * sample data on the live site.
 *
 * This is not hypothetical. `php artisan db:seed` runs DatabaseSeeder, which
 * calls every Demo* seeder in turn, and it has been run against production —
 * that is how "Corporate Gala — Full Production" arrived on an owner's real
 * account. Nothing in the command's name warns you; `db:seed` sounds like
 * reference data.
 *
 * So the check lives in the seeders themselves rather than in a deployment
 * habit. Reference data (categories, policies, plans, permissions) does NOT
 * use this trait — that data belongs on production and is the reason to run
 * the command at all.
 *
 * The escape hatch is deliberately awkward: an environment variable set for
 * one invocation, so seeding a staging copy that reports itself as production
 * is still possible and still a decision someone made on purpose.
 */
trait OnlyOutsideProduction
{
    /**
     * True when this seeder should stop. Says why, so a skipped seeder does
     * not read as a silent success.
     */
    protected function refusedOnProduction(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        if (env('SEED_DEMO_DATA_ON_PRODUCTION', false)) {
            $this->command?->warn(
                static::class.': planting demo data on PRODUCTION because '
                .'SEED_DEMO_DATA_ON_PRODUCTION is set.'
            );

            return false;
        }

        $this->command?->warn(
            static::class.' skipped: this is production and demo data does not '
            .'belong here. Set SEED_DEMO_DATA_ON_PRODUCTION=1 for one run if you '
            .'really mean it.'
        );

        return true;
    }
}
