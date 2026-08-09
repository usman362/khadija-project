<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist row 155 — the gap an earlier brief called "the single most
 * important" one: a professional had no way to report a client who never
 * turned up or cancelled on them.
 *
 * Both directions land in one table, because they are the same record from
 * two ends and the pair only means anything read together. A client
 * cancelling on the day and a professional reporting a no-show on the same
 * booking are two accounts of one morning, and a reviewer needs both.
 *
 * The quoted figures are SNAPSHOT here rather than recomputed on display.
 * The refund a client is shown depends on how many days remain before the
 * event, so a later date change would silently rewrite what they were told
 * when they cancelled — and that is precisely the number they would dispute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();     // CR-YYYY-NNNNNN

            // Per-service (R12): one booking, never a whole event.
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            $table->string('raised_role', 20);             // client | professional

            // What is being reported. Kept as one vocabulary across both
            // directions so the admin queue is one list, not two.
            $table->string('kind', 40);
            $table->text('reason');
            $table->text('detail')->nullable();

            // No-shows happen at a time; cancellations happen on a notice
            // period. Both matter to whoever reads this later.
            $table->timestamp('occurred_at')->nullable();
            $table->unsignedSmallInteger('waited_minutes')->nullable();

            // The quote as it stood when the request was made.
            $table->decimal('quoted_agreed', 12, 2)->nullable();
            $table->decimal('quoted_deposit', 12, 2)->nullable();
            $table->decimal('quoted_balance', 12, 2)->nullable();
            $table->decimal('quoted_refund', 12, 2)->nullable();
            $table->string('quoted_tier')->nullable();
            $table->integer('days_before')->nullable();

            $table->string('status', 20)->default('submitted');   // submitted|acknowledged|actioned|withdrawn
            $table->text('resolution_note')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();

            // A report can become a dispute (R34). Recorded so the two are
            // not investigated twice as if they were unrelated.
            $table->foreignId('dispute_case_id')->nullable()->constrained()->nullOnDelete();

            // §1 of the policy is a signature-bearing statement about facts.
            $table->boolean('certified')->default(false);
            $table->text('certification_text')->nullable();

            $table->timestamps();

            $table->index(['status', 'raised_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
