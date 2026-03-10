<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content'); // The AI-generated agreement text (HTML/Markdown)
            $table->json('extracted_terms')->nullable(); // Structured terms extracted from chat
            $table->string('status')->default('draft'); // draft, pending_review, client_accepted, supplier_accepted, fully_accepted, rejected, expired
            $table->timestamp('client_accepted_at')->nullable();
            $table->timestamp('supplier_accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->string('source')->default('ai'); // ai, manual
            $table->integer('version')->default(1);
            $table->text('ai_model_used')->nullable(); // Which AI model generated this
            $table->text('ai_prompt_summary')->nullable(); // Summary of what was sent to AI
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
