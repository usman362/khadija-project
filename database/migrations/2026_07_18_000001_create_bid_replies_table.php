<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Negotiation thread on a bid — Accept / Decline / Reply is the
        // universal rule (Fix Spec). Either party may reply, optionally with a
        // counter-offer amount, unlimited, until the bid is accepted/declined.
        Schema::create('bid_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('counter_amount')->nullable(); // set when it's a counter-offer
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['bid_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_replies');
    }
};
