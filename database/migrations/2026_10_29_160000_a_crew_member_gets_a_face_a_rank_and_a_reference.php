<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a crew member needs before a client ever sees them.
 *
 * Sir Peter, 29 October, settling the staff question: named team members,
 * no logins, "built properly, with the flexibility to add accounts later".
 * Three of his requirements land on the record itself.
 *
 *   photo         "The Client sees the designated worker's photo, name, role
 *                 and contact phone number." A name alone is not much use to
 *                 somebody looking around a venue for a stranger.
 *
 *   is_supervisor His default supervisor, and his point 9: supervisor status
 *                 is kept SEPARATE from on-site contact status. They answer
 *                 different questions. A supervisor is a rank the person
 *                 holds; a point of contact is a job for one event. The
 *                 supervisor is often not the one the client rings.
 *
 *   public_id     "Give each staff member a unique internal ID so we can
 *                 introduce individual staff logins later without rebuilding
 *                 the staff management system." The row id already exists,
 *                 but it is sequential and it is ours; a reference that can
 *                 be shown, searched and later attached to an account is the
 *                 thing that makes the upgrade cheap.
 *
 * And one on the booking: supervisor_id, the on-site supervisor for THIS
 * event, which his point 3 asks to be selectable per event rather than
 * fixed. It sits beside point_of_contact_id rather than replacing it,
 * because keeping them apart is the requirement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('public_id', 24)->nullable()->unique()->after('id');
            $table->string('photo')->nullable()->after('name');
            $table->boolean('is_supervisor')->default(false)->after('role');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Same nullOnDelete as the point of contact: a crew member
            // leaving must not take the booking with them.
            $table->foreignId('supervisor_id')->nullable()->after('point_of_contact_id')
                ->constrained('staff_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'photo', 'is_supervisor']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
        });
    }
};
