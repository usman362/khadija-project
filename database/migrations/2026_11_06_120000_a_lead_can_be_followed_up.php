<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What one professional has written about one lead, and when they mean to
 * chase it.
 *
 * Khadijah's Leads CRM carries Lead Notes (Internal Only), Last Contacted and
 * Next Follow-Up on every row. None of the three had anywhere to live: a lead
 * is an event somebody else posted, so there was nothing on it that belonged
 * to the professional looking at it.
 *
 * Keyed on the pair, because this is private to the professional and the
 * client must never see it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('note', 600)->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->date('next_follow_up_on')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_lead_notes');
    }
};
