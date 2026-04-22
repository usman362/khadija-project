<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves membership pricing from Monthly/Quarterly/Yearly/OneTime to a fixed
 * ladder of contract terms — 6-month / 12-month / 18-month — per client feedback
 * ("it's a business, not a get-in-then-out contract").
 *
 * Remap of any existing rows so historical data continues to render:
 *     monthly, quarterly  → 6_month
 *     yearly              → 12_month
 *     one_time            → 12_month  (treat as annual-equivalent)
 *
 * The default value for new rows becomes `12_month`.
 *
 * NOTE: The column stays a string — no schema type change needed, only data
 * and default value. Existing UserSubscription rows reference the plan
 * through membership_plan_id, so no data migration on that side is needed.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Remap existing plan rows to the new vocabulary.
        DB::table('membership_plans')
            ->whereIn('billing_cycle', ['monthly', 'quarterly'])
            ->update(['billing_cycle' => '6_month']);

        DB::table('membership_plans')
            ->whereIn('billing_cycle', ['yearly', 'one_time'])
            ->update(['billing_cycle' => '12_month']);

        // 2. Flip the column default from 'monthly' to '12_month' so fresh
        //    records (e.g. from tests) land in the new vocabulary.
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('billing_cycle')->default('12_month')->change();
        });
    }

    public function down(): void
    {
        // Best-effort reverse — we can't perfectly reconstruct the original
        // split between monthly/quarterly or yearly/one_time. Collapse back
        // to the closest legacy values.
        DB::table('membership_plans')
            ->where('billing_cycle', '6_month')
            ->update(['billing_cycle' => 'monthly']);

        DB::table('membership_plans')
            ->whereIn('billing_cycle', ['12_month', '18_month'])
            ->update(['billing_cycle' => 'yearly']);

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('billing_cycle')->default('monthly')->change();
        });
    }
};
