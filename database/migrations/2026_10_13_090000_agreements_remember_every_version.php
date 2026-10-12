<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every version of an agreement, and the draft nobody has sent yet.
 *
 * Sir Peter's rules for editing an agreement need two things the table did
 * not have. Rule 5: "Keep previous versions, including signatures, approvals,
 * timestamps, and a record of what changed. Do not overwrite historical
 * agreements." And rule 7: "When a party has unsent draft edits, those edits
 * should not invalidate an existing signature."
 *
 * The version counter and the version-scoped signatures already existed, so a
 * signature stops counting the moment the terms move. What was missing is any
 * record of WHAT each version said, and anywhere to keep edits that have not
 * been sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finalization_id')->constrained()->cascadeOnDelete();

            // The version this row describes, matching finalizations.terms_version.
            $table->unsignedInteger('version');

            // The terms as they stood when this version was created, and the
            // fields that moved to get here. Both are kept: the snapshot
            // answers "what did I sign", the diff answers "what changed".
            $table->json('terms');
            $table->json('changed')->nullable();

            // Who sent it, and the note they sent with it.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary')->nullable();

            // Copied across at the moment the version was superseded, so the
            // history still says who had signed it and when, even though the
            // live row has moved on.
            $table->timestamp('client_signed_at')->nullable();
            $table->timestamp('supplier_signed_at')->nullable();
            $table->boolean('client_approved')->default(false);
            $table->boolean('supplier_approved')->default(false);

            $table->timestamps();

            $table->unique(['finalization_id', 'version']);
        });

        Schema::table('finalizations', function (Blueprint $table) {
            // Unsent edits. Deliberately NOT the live terms: while they sit
            // here the agreement has not changed, so nobody's signature does
            // either. Submitting is what moves them across.
            $table->json('draft_terms')->nullable()->after('change_requested_at');
            $table->foreignId('draft_by')->nullable()->after('draft_terms')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('draft_at')->nullable()->after('draft_by');
        });
    }

    public function down(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('draft_by');
            $table->dropColumn(['draft_terms', 'draft_at']);
        });

        Schema::dropIfExists('agreement_revisions');
    }
};
