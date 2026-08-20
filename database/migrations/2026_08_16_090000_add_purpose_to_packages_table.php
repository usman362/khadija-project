<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Primary Package Purpose" from the Create a Package mockup — one line saying
 * what the package is designed to deliver.
 *
 * Not the same field as `description`, which is a paragraph of selling copy.
 * This is the one-line answer a client reads first, and it is rendered on the
 * package page rather than stored and forgotten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('purpose', 160)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
