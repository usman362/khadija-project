<?php

use Database\Seeders\CategoryArtworkSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Carry the old site's category artwork onto the live v2 tree, for databases
 * that were already seeded before CategoryArtworkSeeder existed.
 *
 * The work itself lives in the seeder — a fresh server runs migrations before
 * any category exists, so a migration on its own would have nothing to copy.
 * One implementation, called from both places.
 */
return new class extends Migration
{
    public function up(): void
    {
        CategoryArtworkSeeder::carry();
    }

    public function down(): void
    {
        // Deliberately not reversed. The images are the same files either way,
        // and stripping them back off v2 would leave the live tree blank again
        // to undo something that only ever added.
    }
};
