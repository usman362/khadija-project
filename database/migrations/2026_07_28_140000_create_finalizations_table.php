<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The step-by-step agreement between a client and the professional they chose.
 *
 * Accepting a bid used to jump straight to a confirmed Booking — scope, price,
 * schedule, deposit terms, contract and funding all assumed rather than agreed.
 * Peter's rule is that either side may back out until a final agreement is
 * made, which only means something if there is a record of how far the
 * agreement has actually got. This is that record.
 *
 * Each step stores WHO completed it and WHEN, so "both parties must approve
 * each step to continue" is checkable rather than a claim in the copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bid_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // in_progress | booked | cancelled
            $table->string('status', 20)->default('in_progress');

            // 1 · Review bid, 2 · Scope
            $table->timestamp('bid_reviewed_at')->nullable();
            $table->text('scope')->nullable();
            $table->timestamp('scope_agreed_at')->nullable();

            // 3 · Price & fees
            $table->decimal('agreed_price', 10, 2)->nullable();
            $table->timestamp('price_agreed_at')->nullable();

            // 4 · Schedule
            $table->dateTime('service_start')->nullable();
            $table->dateTime('service_end')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->timestamp('schedule_agreed_at')->nullable();

            // 5 · Deposit & payment terms
            $table->unsignedTinyInteger('deposit_percent')->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->date('balance_due_on')->nullable();
            $table->text('payment_terms')->nullable();
            $table->timestamp('terms_agreed_at')->nullable();

            // 6 · Contract — both parties sign, and we keep what they signed.
            $table->longText('contract_body')->nullable();
            $table->string('client_signature', 120)->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('supplier_signature', 120)->nullable();
            $table->timestamp('supplier_signed_at')->nullable();

            // 7 · Secure payment. `payment_mode` records whether the money was
            // real, so a test-mode booking can never be mistaken for a paid one.
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_mode', 10)->nullable();      // test | live
            $table->timestamp('funded_at')->nullable();

            $table->timestamps();

            // One finalization per professional per request.
            $table->unique(['event_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finalizations');
    }
};
