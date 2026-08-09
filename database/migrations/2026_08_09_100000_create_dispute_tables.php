<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R34 Phase 1 artifact 1 — the dispute data model.
 *
 * The architecture lists the entities in §11 and the rules they have to carry
 * in §4, §5, §6 and §10. The ones that shaped these tables:
 *
 *   §6 — a case is scoped to exactly ONE service line, never a whole event
 *   (R12). `booking_id` is required for that reason, and `category_id` names
 *   the service line within an MSR.
 *
 *   §6 — the public case number is DR-YYYY-NNNNNN on one global sequence,
 *   with an immutable internal id behind it. Users only ever see the former.
 *
 *   §4 — evidence is hashed on upload, never silently edited, and deletions
 *   are logged rather than actually deleting. Hence `sha256`, `superseded_by`
 *   and a soft delete with a reason.
 *
 *   §5 — a revised decision keeps the original plus who, why and when. So
 *   decisions are append-only rows, not an updatable column on the case.
 *
 *   §10 — every action is logged with previous value, new value, who, their
 *   role, timestamp and reason.
 *
 * Deliberately absent: any deadline column with a default. §12 holds every
 * day-count for attorney review, and a default here would become the policy
 * by accident.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_cases', function (Blueprint $table) {
            $table->id();                                   // the immutable internal identifier
            $table->string('reference', 20)->unique();      // DR-YYYY-NNNNNN, the only number users see

            // §6 — one service line, never a whole event.
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('filed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();

            // §3 — three independent fields. None derived from another.
            $table->unsignedTinyInteger('severity');
            $table->string('priority', 12)->default('normal');
            $table->string('taxonomy', 40);
            $table->json('secondary_taxonomy')->nullable();

            $table->string('state', 32)->index();
            $table->text('summary');

            // §6 — related-but-independent cases, and duplicate detection.
            $table->foreignId('duplicate_of')->nullable()->constrained('dispute_cases')->nullOnDelete();

            // §7 — staff-only, never user-visible.
            $table->json('internal_tags')->nullable();

            // §8 — filing pauses this one service line's held balance.
            $table->boolean('balance_paused')->default(false);

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_role', 32)->nullable();

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'priority']);
        });

        Schema::create('dispute_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();

            // §4's hierarchy: platform-generated records are primary, external
            // evidence supplements. Recorded per item so an investigator can
            // see at a glance what is verifiable and what is asserted.
            $table->string('kind', 32);
            $table->boolean('platform_generated')->default(false);

            $table->text('description')->nullable();
            $table->foreignId('uploaded_file_id')->nullable()->constrained('uploaded_files')->nullOnDelete();

            // §4 — tamper evidence. The hash is of the bytes as submitted.
            $table->string('sha256', 64)->nullable();

            // §4 — no silent edits. A correction is a new row pointing back.
            $table->foreignId('supersedes')->nullable()->constrained('dispute_evidence')->nullOnDelete();

            // §4 — deletions are logged rather than allowed to truly delete.
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawn_reason')->nullable();

            $table->timestamps();
        });

        Schema::create('dispute_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decided_by')->constrained('users')->cascadeOnDelete();
            $table->string('decided_role', 32);

            // §5 — two axes, kept separate. A financial outcome is absent on
            // a housekeeping closure, which is why it is nullable.
            $table->string('financial_outcome', 32)->nullable();
            $table->string('resolution_type', 32);

            $table->text('reasoning');
            $table->decimal('amount_to_client', 12, 2)->nullable();
            $table->decimal('amount_to_professional', 12, 2)->nullable();

            // §7 — who a fraud finding is against.
            //
            // Recorded, never inferred. "Client prevails" says which side lost;
            // "fraud confirmed" does not — the client who filed can be the one
            // who fabricated the invoice. Deriving it from the role would put a
            // fraud finding on the wrong account's history, and §7's ladder ends
            // in permanent removal.
            $table->foreignId('finding_against')->nullable()->constrained('users')->nullOnDelete();

            // §5 — a revised decision keeps the original alongside it.
            $table->foreignId('revises')->nullable()->constrained('dispute_decisions')->nullOnDelete();
            $table->string('revision_reason')->nullable();

            $table->timestamps();
        });

        Schema::create('dispute_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->nullable();

            // §10 — previous value, new value, who, role, when, why.
            $table->string('action', 40);
            $table->string('field', 40)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();

            // §7 — a case is visible to its parties; internal notes are not.
            $table->boolean('visible_to_parties')->default(true);

            $table->timestamp('created_at')->nullable()->index();
        });

        // §2 Step 4 — the single post-decision step, and the only place an
        // outside party appears. Kept as its own table because what happens
        // here is not a platform decision: an outside provider or a court
        // reaches its own conclusion and GigResource records it (§5, §8).
        Schema::create('dispute_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at');

            $table->string('provider')->nullable();     // named once one is chosen (§12)
            $table->string('external_reference')->nullable();
            $table->text('outcome_summary')->nullable();
            $table->timestamp('concluded_at')->nullable();

            // §8 — a processor acting under its own rules is not a platform
            // decision, and must not be recorded as one.
            $table->boolean('payment_provider_initiated')->default(false);

            $table->timestamps();
        });

        Schema::create('dispute_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32);

            // §7 — the conflict-of-interest disclosure, captured at the point
            // of assignment rather than as a separate later step.
            $table->boolean('conflict_disclosed')->default(false);
            $table->string('conflict_detail')->nullable();

            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_assignments');
        Schema::dropIfExists('dispute_escalations');
        Schema::dropIfExists('dispute_events');
        Schema::dropIfExists('dispute_decisions');
        Schema::dropIfExists('dispute_evidence');
        Schema::dropIfExists('dispute_cases');
    }
};
