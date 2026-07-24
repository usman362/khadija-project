<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Packages carried only a boolean is_active (published / draft). The locked
 * package model (Q11) is a four-state lifecycle: draft · active · paused ·
 * archived. Add the canonical column and backfill from the boolean; is_active
 * stays as a fast "is this publicly visible" flag, kept === (status === active).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_active');
            $table->index(['user_id', 'status']);
        });

        // Backfill: a live package becomes 'active', an unpublished one 'draft'.
        DB::table('packages')->where('is_active', true)->update(['status' => 'active']);
        DB::table('packages')->where('is_active', false)->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
