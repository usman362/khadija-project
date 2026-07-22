<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A professional's withdrawal of earned (completed-booking) funds.
        // The payout ledger; true money-movement escrow waits on the payment
        // gateway, but this records requests/payments and drives the
        // withdrawn / available balance on the Transactions page.
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the professional
            $table->unsignedInteger('amount');                              // whole currency units
            $table->string('currency', 3)->default('USD');
            $table->string('method', 40)->nullable();                       // bank | paypal | stripe …
            $table->string('status', 12)->default('requested');            // requested | paid | rejected
            $table->string('reference')->nullable();                        // payout txn reference
            $table->string('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
