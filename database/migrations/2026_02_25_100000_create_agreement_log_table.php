<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Append-only log for agreement/booking state changes. No updated_at.
     */
    public function up(): void
    {
        Schema::create('agreement_log', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 64)->default('booking');
            $table->unsignedBigInteger('subject_id');
            $table->string('from_status', 64)->nullable();
            $table->string('to_status', 64);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_log');
    }
};
