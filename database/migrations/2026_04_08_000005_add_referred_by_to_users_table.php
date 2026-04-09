<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('referred_by_influencer_id')
                ->nullable()
                ->after('phone')
                ->constrained('influencers')
                ->nullOnDelete();
            $table->timestamp('referral_attributed_at')->nullable()->after('referred_by_influencer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by_influencer_id');
            $table->dropColumn('referral_attributed_at');
        });
    }
};
