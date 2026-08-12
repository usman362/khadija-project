<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The forms audit's ten missing forms — checklist rows 183, 184, 185, 205,
 * 233, 237, 239, 243, 245, 246.
 *
 * One table, because the ten rows are ten instances of one problem: a
 * workflow document names a form and nobody ever wrote down the fields. Ten
 * tables would be ten migrations, ten models and ten more places to forget
 * that a certification stores the wording it showed.
 *
 * The answers live in a JSON payload against a form key, and the KEY's
 * definition in FormRegistry is the field-level spec those rows asked for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();     // FS-YYYY-NNNNNN
            $table->string('form_key', 40)->index();

            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('submitted_role', 20)->nullable();

            // What it is about, when it is about something. A change order
            // and a correction request both hang off a booking; a testimonial
            // hangs off nothing.
            $table->nullableMorphs('subject');

            $table->json('payload');

            // The other party on a dual-approval form (the Change Order).
            $table->foreignId('counterparty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_status', 20)->nullable();   // pending|accepted|declined
            $table->text('approval_note')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->string('status', 20)->default('submitted');  // submitted|actioned|withdrawn
            $table->text('resolution_note')->nullable();

            // The wording actually shown, stored with the record — a
            // certification that only points at a config file is a signature
            // on a document that can be edited afterwards.
            $table->text('certification_text')->nullable();

            $table->timestamps();

            $table->index(['form_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
