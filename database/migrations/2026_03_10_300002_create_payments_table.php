<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // stripe, paypal
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_method')->nullable(); // card, paypal_account
            $table->string('gateway_session_id')->nullable(); // Stripe Checkout Session ID / PayPal Order ID
            $table->string('gateway_payment_id')->nullable(); // Final payment reference
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('gateway_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
