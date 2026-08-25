<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files on a request.
 *
 * The BR wizard had a Files step that said "Attachments aren't available yet"
 * — honest at the time, because there was nowhere to put a file, but a step in
 * an eight-step wizard that does nothing is still a step that does nothing.
 *
 * `event_id` is nullable on purpose. The wizard keeps its state in the session
 * so an abandoned request leaves no half-built Event behind, which means the
 * client uploads a floor plan before the row it belongs to exists. Uploads are
 * held against `draft_key` — the wizard's own token — and adopted by the event
 * the moment it is saved or published.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();

            // Set while the request is still only a wizard session; cleared
            // once the file belongs to a real event.
            $table->string('draft_key', 64)->nullable();

            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 128);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'draft_key']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
    }
};
