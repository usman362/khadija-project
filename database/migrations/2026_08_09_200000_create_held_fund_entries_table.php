<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist row 181 — the held-funds ledger.
 *
 * Nothing recorded money sitting held. Payments is subscription-oriented,
 * payouts is professional withdrawals, and finalizations holds the contract
 * terms — the position between "the client funded a deposit" and "the
 * professional was paid" existed only as an inference. Three features already
 * built lean on it: dispute financial outcomes (R34), cancellation refunds,
 * and release milestones.
 *
 * The row's old blocker is gone: it used to be held behind Live Event
 * Upgrades' fee, and that entire subject was pulled from scope on 2026-08-03.
 * The row says so itself.
 *
 * APPEND-ONLY, and that is the whole design. A `balance` column would be a
 * second copy of the truth, and the day it disagreed with the movements
 * behind it, somebody would be paid the wrong amount with no way to tell
 * which number was right. Nothing here is ever updated or deleted; a mistake
 * is corrected by a reversing entry that stays visible next to it.
 *
 * Per service line (R12): one booking, never a whole event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('held_fund_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();

            // What moved, and which way relative to the held balance.
            $table->string('kind', 30);              // deposit|balance|release|refund|commission|adjustment
            $table->string('direction', 3);          // in | out
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            $table->text('reason');

            // What caused it — a decision, a cancellation, a signed contract.
            // Morphed so a new cause does not need a new column.
            $table->nullableMorphs('source');

            /*
             * `pending` means the platform has decided it; `settled` means the
             * processor confirmed the money actually moved. They are different
             * facts and §8 of the dispute rules is explicit that a platform
             * decision is not a payment. Keeping one column for both would let
             * a professional be told they were paid because somebody clicked
             * approve.
             */
            $table->string('state', 12)->default('pending');
            $table->string('processor_reference')->nullable();
            $table->timestamp('settled_at')->nullable();

            // A correction is a new entry pointing at the one it reverses.
            $table->foreignId('reverses')->nullable()->constrained('held_fund_entries')->nullOnDelete();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['booking_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_fund_entries');
    }
};
