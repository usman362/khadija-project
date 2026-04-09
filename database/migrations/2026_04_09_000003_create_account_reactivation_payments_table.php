<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_reactivation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('gateway', 20); // stripe | paypal
            $table->string('gateway_session_id')->nullable();
            $table->string('gateway_payment_id')->nullable();

            // pending | processing | completed | failed | cancelled
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('gateway_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_reactivation_payments');
    }
};
