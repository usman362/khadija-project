<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R30 — the Client Toolkit → Request/Agreement bridge.
 *
 * Deliberately NOT the same table as event_ai_artifacts. That table is the
 * client's library of saved tool results; this one is where a result has been
 * PLACED. Keeping them apart is what makes R30's rule true -- "removed tool
 * data does not delete the original tool result" -- rather than something we
 * have to remember not to break.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolkit_attachments', function (Blueprint $table) {
            $table->id();

            // Where it was placed: an Event (the request) or an Agreement.
            $table->morphs('attachable');

            // The saved result it came from. Nullable because deleting the
            // original must not delete what the client already placed -- the
            // placed copy is theirs now.
            $table->foreignId('source_artifact_id')->nullable()
                ->constrained('event_ai_artifacts')->nullOnDelete();

            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();

            // Labelled with its source tool and time, so a figure in an
            // agreement can always be traced back to what produced it.
            $table->string('tool_key', 60);
            $table->string('tool_name');
            $table->string('title');
            $table->json('payload')->nullable();

            // copy   = a snapshot; later tool edits never reach this.
            // linked = follows the source, but changes are REVIEWED, never
            //          applied silently -- this data can sit inside a contract.
            $table->string('link_mode', 8)->default('copy');

            // Hash of the source payload when it was linked. A mismatch is how
            // we know the source moved without asking the source every time.
            $table->string('source_fingerprint', 64)->nullable();
            $table->boolean('needs_review')->default(false);

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id', 'tool_key'], 'toolkit_attach_dest_tool_idx');
            $table->index(['source_artifact_id', 'link_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolkit_attachments');
    }
};
