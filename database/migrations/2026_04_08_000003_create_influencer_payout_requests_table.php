<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influencer_payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payout_method')->nullable(); // paypal|bank|other
            $table->string('payout_account')->nullable(); // email/account identifier
            $table->string('status')->default('pending'); // pending|approved|paid|rejected
            $table->text('user_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['influencer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_payout_requests');
    }
};
