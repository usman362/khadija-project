<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A number the guest-count filter can actually compare against.
 *
 * `guests` is prose the professional typed — "Up to 150", "150 guests",
 * "Up to 200 seated". Filtering on it meant digging the digits out in SQL, and
 * the two databases in play spell that differently, so the filter would have
 * behaved one way in tests and another in production. The number is parsed
 * once, on save, and stored.
 *
 * Null is a real answer: a package that never stated a capacity is not a
 * package for zero guests, and the filter leaves it alone rather than
 * pretending it knows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('guests_max')->nullable()->after('guests');
        });

        // Backfill what is already there, using the same reading the model does.
        foreach (DB::table('packages')->select('id', 'guests')->get() as $row) {
            $max = \App\Models\Package::parseGuests($row->guests);

            if ($max !== null) {
                DB::table('packages')->where('id', $row->id)->update(['guests_max' => $max]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('guests_max');
        });
    }
};
