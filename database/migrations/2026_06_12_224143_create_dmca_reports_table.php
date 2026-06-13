<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DMCA takedown notices + flagged-image audit trail (Developer
     * Feedback v1.1 §1.3). Every notice records who reported, what
     * content/URL was flagged, the (optional) uploading user, and the
     * review status — the timestamp + user ID audit trail Peter asked for.
     */
    public function up(): void
    {
        Schema::create('dmca_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('content_url', 2048);          // flagged image/page URL
            $table->text('original_work');                 // description/URL of the copyrighted work
            $table->text('statement')->nullable();         // good-faith statement details
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending|reviewing|actioned|rejected
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dmca_reports');
    }
};
