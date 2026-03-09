<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ownership: user = user-confirmed, ai = AI-derived/suggested, system = system-generated.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('source', 32)->default('user')->after('supplier_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('source', 32)->default('user')->after('status');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->string('source', 32)->default('user')->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
