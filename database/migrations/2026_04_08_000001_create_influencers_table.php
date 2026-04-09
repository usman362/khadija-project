<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influencers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->json('social_media_links')->nullable();
            $table->text('audience_description')->nullable();
            $table->unsignedInteger('monthly_reach')->default(0);
            $table->string('referral_code')->unique();
            $table->string('commission_tier')->default('starter'); // starter|rising|pro|elite
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('available_balance', 12, 2)->default(0);
            $table->decimal('paid_out', 12, 2)->default(0);
            $table->unsignedInteger('total_referrals')->default(0);
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('commission_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencers');
    }
};
