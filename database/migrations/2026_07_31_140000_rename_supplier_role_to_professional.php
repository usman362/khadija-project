<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the role `supplier` to `professional`.
 *
 * The product has called these users professionals everywhere a person can see
 * — the portal, the nav, the copy, the docs — while the code called them
 * suppliers. RegisterController even carried a comment explaining the
 * translation. One word for one thing.
 *
 * Two places store the string. The pivot linking users to roles holds an id, so
 * it needs nothing; `primary_role` holds the word itself.
 *
 * NOT touched: `supplier_id` on events, bookings and bids, and the `supplier()`
 * relation on Booking. Those are column and relation names — a schema change of
 * a different size, and nobody sees them.
 *
 * A session carrying the old `active_role` is safe: activeRole() checks the
 * value against the user's real roles and falls back when it does not match.
 */
return new class extends Migration
{
    private const OLD = 'supplier';
    private const NEW = 'professional';

    public function up(): void
    {
        $this->rename(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->rename(self::NEW, self::OLD);
    }

    /** Constants either side, so a find-and-replace over this file cannot quietly turn it into a no-op. */
    private function rename(string $from, string $to): void
    {
        DB::table('roles')->where('name', $from)->update(['name' => $to]);
        DB::table('users')->where('primary_role', $from)->update(['primary_role' => $to]);
    }
};
