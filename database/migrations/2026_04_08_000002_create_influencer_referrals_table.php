<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influencer_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('type'); // signup_bonus|booking_commission
            $table->decimal('base_amount', 12, 2)->default(0); // booking price for commission, 0 for signup
            $table->decimal('commission_rate', 5, 2)->default(0); // percentage
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending|earned|paid|cancelled
            $table->string('source')->default('user'); // user|ai|system
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['influencer_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_referrals');
    }
};
