<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The money ledger (A1), approved by Sir Peter on 16 October 2026.
 *
 * His instruction, Decision Log item 7: before refunds, milestone payments and
 * payouts are built, agree ONE financial model that tracks every money event as
 * a discrete, separately auditable record, and never represent the picture by
 * editing a single booking-total field.
 *
 * So: a booking total is never stored. It is the sum of a list. Every charge,
 * fee, commission, upgrade, refund and payout is one append-only row, and a
 * correction is a new reversing row rather than an edit. "What is this booking
 * worth" is a question answered by adding up the list, so it can never silently
 * drift from what actually happened.
 *
 * That is the opposite of the trap he named: one total field that gets
 * overwritten, where last month's number is gone and nothing reconciles.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * The spine. One row per money event, never updated once it settles.
         */
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            // What this concerns. Both are nullable because a platform fee can
            // belong to a request that has no booking yet.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // Whose money this entry is about: the client, the professional, or
            // GigResource itself. Null is GigResource.
            $table->foreignId('party_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('entry_type', 32);

            // Always positive. Direction lives in `sign`, so a reversal reads as
            // an amount with a direction rather than a negative number that can
            // be summed by accident.
            $table->decimal('amount', 12, 2);
            $table->enum('sign', ['+', '-']);
            $table->string('currency', 3)->default('USD');

            $table->string('state', 16)->default('pending');

            // The thing this entry is about: a Booking, an upgrade, a milestone,
            // a Payment, a payout.
            $table->nullableMorphs('subject');

            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            // Set when this row corrects an earlier one. Nothing is ever edited
            // or deleted, so the history reconciles and an auditor can trace
            // every dollar.
            $table->foreignId('reverses_id')->nullable()->constrained('ledger_entries')->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // The three questions this table is asked: what does this booking
            // come to, what does this request come to, and what is owed to or
            // by this person.
            $table->index(['booking_id', 'entry_type']);
            $table->index(['event_id', 'entry_type']);
            $table->index(['party_user_id', 'state']);
            $table->index('occurred_at');
        });

        /*
         * Each upgrade its own record, which is the B3 rule.
         *
         * Peter confirmed on 22 August: a mid-event upgrade is an amendment to
         * the existing booking, shown to the client as one running total but
         * recorded internally as separate line items, never collapsed. Payment
         * must be secured BEFORE the upgrade is confirmed, which is why
         * secured_payment_id exists and why 'confirmed' is its own state.
         *
         * This is the answer to his October question about a professional who
         * stays longer or adds more food and wants paying for it.
         */
        Schema::create('booking_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            $table->string('description');
            $table->decimal('amount', 12, 2);

            // The pro's ordinary tier rate. No special upgrade fee, per B3.
            $table->decimal('commission_rate', 5, 2);

            $table->string('status', 24)->default('requested');
            $table->foreignId('secured_payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });

        /*
         * A payment can now back something that is not a subscription.
         *
         * The column was already nullable; what was missing was any way to tell
         * a request fee from a booking charge from a refund.
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 24)->default('subscription')->after('user_subscription_id');
            $table->index('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropColumn('purpose');
        });

        Schema::dropIfExists('booking_upgrades');
        Schema::dropIfExists('ledger_entries');
    }
};
