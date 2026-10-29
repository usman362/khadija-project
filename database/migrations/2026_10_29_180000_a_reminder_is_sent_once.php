<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which attendance reminders have already gone out.
 *
 * Sir Peter, 29 October, point 8: when a professional says they will decide
 * nearer the time, "require confirmation at least 24 hours before the event,
 * send reminders, and flag missed deadlines".
 *
 * Written down before it counts as sent, and unique on the booking and the
 * stage, exactly as the subscription renewal notices are. A command that runs
 * several times a day, or that is replayed after a deploy, must not send
 * anybody the same reminder twice: a chaser that arrives four times is read
 * as a fault in the site rather than as a thing to act on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // 'first', 'final', 'missed' — see App\Domain\AttendanceChase.
            $table->string('stage', 16);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['booking_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_reminders');
    }
};
