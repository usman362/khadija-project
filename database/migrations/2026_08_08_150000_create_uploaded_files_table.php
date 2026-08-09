<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R54 — the audit log the pipeline writes to, and the state it keeps.
 *
 * One row per file uploaded anywhere on GigResource. The rule asks for
 * "audit logging" and "retention rules" as pipeline stages, and neither is
 * possible while the only record of a file is a path stored in some other
 * table's column, which is how every upload worked before this.
 *
 * `scan_status` is deliberately not a boolean. There are three answers, not
 * two: clean, infected, and NOT SCANNED — and the third is the one we
 * currently give, because no malware vendor has been chosen (Open Decisions
 * row 39). Collapsing it to a boolean would force us to record unscanned
 * files as clean, which is the claim that would matter in a dispute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 40)->index();

            // What the uploader called it, versus where it actually lives.
            $table->string('original_name');
            $table->string('path');
            $table->string('disk', 20);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable()->index();

            // preview/confirm happens in the browser; these are the stages
            // that leave a record.
            $table->enum('status', ['quarantined', 'approved', 'rejected', 'manual_review', 'removed'])
                ->default('quarantined')->index();
            $table->enum('scan_status', ['not_scanned', 'clean', 'infected', 'error'])
                ->default('not_scanned');
            $table->string('scanner', 60)->nullable();
            $table->string('decision_reason')->nullable();

            // R55 — the uploader's attestation about rights and permission,
            // captured at upload with the wording they were actually shown.
            $table->boolean('rights_attested')->default(false);
            $table->text('attestation_text')->nullable();

            // Removal stays available regardless of any consent claimed (R55).
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('removed_at')->nullable();
            $table->string('removal_reason')->nullable();

            $table->timestamp('retain_until')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
