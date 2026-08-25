<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attachments in chat never worked. This is why.
 *
 * A file is uploaded the moment it is picked and held until the message is
 * sent — that is the shape the API has: upload returns an id, send takes
 * `attachment_ids`. So between the two there is an attachment with no message.
 *
 * `message_id` was NOT NULL with a foreign key, and the upload controller
 * inserted `0` into it with a comment calling it temporary, then updated it to
 * null on the next line. MySQL never got that far: 0 is not a message, the
 * foreign key rejected the INSERT, and every single upload 500'd. The paperclip
 * opened a file dialog and nothing else ever happened.
 *
 * Nullable is what the flow actually needs. `nullOnDelete` too: deleting a
 * message should not silently take a file the client may still be looking at
 * — the row is kept and cleaned up deliberately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
        });

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->foreignId('message_id')->nullable()->change();
        });

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->foreign('message_id')->references('id')->on('messages')->nullOnDelete();
        });

        // An orphan needs an owner, or nothing can decide who may read it
        // before it is attached to a message.
        if (! Schema::hasColumn('message_attachments', 'uploaded_by')) {
            Schema::table('message_attachments', function (Blueprint $table) {
                $table->foreignId('uploaded_by')->nullable()->after('message_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('message_attachments', 'uploaded_by')) {
                $table->dropConstrainedForeignId('uploaded_by');
            }
        });

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
        });

        // Rows with no message cannot survive the column becoming NOT NULL.
        \Illuminate\Support\Facades\DB::table('message_attachments')->whereNull('message_id')->delete();

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->foreignId('message_id')->nullable(false)->change();
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
        });
    }
};
