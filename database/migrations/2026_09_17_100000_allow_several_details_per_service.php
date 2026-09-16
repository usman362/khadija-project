<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * More than one level 4 detail under a service.
 *
 * Sir Peter, 2026-09-16: "you will still need to allow it to select more than
 * one services (level 4) in case the client needs more than one", on the BR,
 * ER and DR alike. A client booking Full-Service Catering for both Lunch and
 * Dinner could only say one of them, because the request kept one detail id
 * per service.
 *
 * The details are a short list that is only ever read with its service, so
 * they sit on the same pivot row as a JSON list rather than in a table of
 * their own. Anything already saved moves across, then the single column goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->json('specialty_ids')->nullable()->after('category_id');
        });

        DB::table('category_event')->whereNotNull('specialty_id')->orderBy('id')
            ->each(fn ($row) => DB::table('category_event')->where('id', $row->id)
                ->update(['specialty_ids' => json_encode([(int) $row->specialty_id])]));

        Schema::table('category_event', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialty_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('category_id')
                ->constrained('categories')->nullOnDelete();
        });

        // Only the first survives the way back: the old column holds one.
        DB::table('category_event')->whereNotNull('specialty_ids')->orderBy('id')
            ->each(function ($row) {
                $first = json_decode($row->specialty_ids, true)[0] ?? null;

                DB::table('category_event')->where('id', $row->id)->update(['specialty_id' => $first]);
            });

        Schema::table('category_event', function (Blueprint $table) {
            $table->dropColumn('specialty_ids');
        });
    }
};
