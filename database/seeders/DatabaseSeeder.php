<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * One command seeds everything: `php artisan db:seed`.
 *
 * Two rules hold here, and SeederIdempotencyTest enforces both.
 *
 *   Every seeder is listed. A seeder that has to be remembered and run by hand
 *   is one that gets forgotten on the server, and the screen it fills stays
 *   empty until somebody notices.
 *
 *   Running it twice changes nothing. Every seeder below either matches on a
 *   natural key (email, slug, reference) or checks before it writes, so this
 *   can be run on a database that already has data — which is what happens on
 *   every deploy.
 *
 * Order is dependency order, not alphabetical: roles before the users that get
 * them, plans before the subscriptions that point at them, categories before
 * the professionals filed under them, bookings before the disputes and requests
 * that hang off them.
 */
class DatabaseSeeder extends Seeder
{
    /*
     * WithoutModelEvents was on this class and had to come off.
     *
     * A dozen models stamp data in a `creating` hook — a category's taxonomy
     * version, a package's state, the reference on every dispute, cancellation
     * and form submission. Suppressing model events meant a seeder wrote
     * different rows depending on how it was invoked: run on its own,
     * CategorySeeder stamped v2 and passed; run through this class the hook
     * never fired, the column default put the rows on v1, and the second run
     * collided on the unique key.
     *
     * These hooks are not side effects to be silenced. They are the reason the
     * rows are valid.
     */
    public function run(): void
    {
        $this->call([
            // ── Access control, before anything that assigns a role ──
            PermissionSeeder::class,
            RolePermissionSeeder::class,

            // ── Platform reference data ──
            MembershipPlanSeeder::class,
            // v1 first: it carries the artwork the old site produced, and v2 —
            // the tree TAXONOMY_VERSION actually points at — borrows from it.
            CategorySeeder::class,
            TaxonomyV2Seeder::class,
            CategoryArtworkSeeder::class,
            InsuranceMatrixSeeder::class,

            // ── Published content ──
            PolicyPageSeeder::class,
            PlatformDisclaimerSeeder::class,
            PageSectionSeeder::class,
            FaqSeeder::class,

            // ── Accounts ──
            AdminUserSeeder::class,
            DemoUsersSeeder::class,
            DemoProfessionalsSeeder::class,

            // ── What those accounts own ──
            DemoPackagesSeeder::class,
            DemoGigsSeeder::class,

            // ── Things that hang off a booking, so they come after the gigs ──
            DemoDisputesSeeder::class,
            DemoFormSubmissionsSeeder::class,

            // ── Influencer programme ──
            InfluencerResourceSeeder::class,
            InfluencerReferralSeeder::class,
            InfluencerAnalyticsSeeder::class,
        ]);

        Artisan::call('permission:cache-reset');
    }
}
