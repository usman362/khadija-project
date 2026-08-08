<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R60 — Attendee Management, locked 2026-08-07.
 *
 * The guest list belongs to an EVENT. The dashboard widget it replaces was an
 * account-wide flat table with no way to tell which event a name belonged to,
 * which is Developer Checklist row 223 and the reason the rule was written: a
 * client running two weddings the same month had one undifferentiated list.
 *
 * The columns are the rule's purpose test made concrete. R60 collects the
 * detailed list "only where it feeds a real event function: RSVP tracking,
 * confirmed/cancelled/no-response counts, headcount updates, seating, dietary
 * or accessibility information". Every column below is one of those. There is
 * deliberately no address, no date of birth, and no free-text notes field —
 * an event does not need them, and R60's point is that collecting personal
 * data with nowhere to send it is the defect, not the feature.
 *
 * `events.share_attendees` is the professional-access toggle: per event,
 * client-controlled, default private. A professional booked on the event sees
 * the list only through the event record, never as an exported file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            // Exactly the three the dashboard summary counts, plus the total.
            $table->enum('rsvp_status', ['confirmed', 'cancelled', 'no_response'])->default('no_response');
            $table->string('dietary')->nullable();
            $table->string('accessibility')->nullable();
            $table->timestamps();

            // A guest list is always read for one event at a time.
            $table->index(['event_id', 'rsvp_status']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('share_attendees')->default(false)->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('share_attendees'));
        Schema::dropIfExists('event_attendees');
    }
};
