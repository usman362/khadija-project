<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Starter costs nothing.
 *
 * Khadijah's revised Founding Starter Plan of 19 October, and Peter's own
 * membership guide the day before: "Starter is FREE every month for
 * Professionals and Influencers. GigResource keeps 5% of their completed paid
 * jobs. This is not just a six-month special."
 *
 * It was $4.99 a month under the July pricing. Pro at $39.99 and Elite at
 * $59.99 are deliberately untouched; the same document says not to raise them
 * because "GigResource has no customers yet".
 *
 * The seeder carries the same figure, but seeders do not run on a live site, so
 * the change has to travel as a migration to reach the deployed database.
 *
 * Only the price moves here. What Starter is ALLOWED to do, the daily bid caps
 * and the rest of the limits in that document, is a separate piece of work and
 * several of its numbers are still unresolved between the two documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('membership_plans')
            ->where('slug', 'starter')
            ->update([
                'price'       => 0,
                'description' => 'Free to join. GigResource takes a share only when you are paid.',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('membership_plans')
            ->where('slug', 'starter')
            ->update([
                'price'       => 4.99,
                'description' => 'Perfect for new professionals building their business.',
                'updated_at'  => now(),
            ]);
    }
};
