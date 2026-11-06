<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Starter plan's one line, as the pricing design has it.
 *
 * It read "Free to join. GigResource takes a share only when you are paid."
 * The sentence is true and the share is still stated, twice: in the tinted
 * band under the price and in the fee list above it. What the design asks
 * the line to do is say who the plan is for, which nothing else on the card
 * does, so it says that instead.
 */
return new class extends Migration
{
    private const WAS = 'Free to join. GigResource takes a share only when you are paid.';
    private const NOW = 'Perfect for new professionals building their business.';

    public function up(): void
    {
        DB::table('membership_plans')->where('slug', 'starter')
            ->where('description', self::WAS)
            ->update(['description' => self::NOW]);
    }

    public function down(): void
    {
        DB::table('membership_plans')->where('slug', 'starter')
            ->where('description', self::NOW)
            ->update(['description' => self::WAS]);
    }
};
