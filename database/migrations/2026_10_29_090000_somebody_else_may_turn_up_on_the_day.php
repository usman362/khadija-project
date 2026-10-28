<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who the client will actually meet, and whether they were warned.
 *
 * Sir Peter, 28 October: a professional may send a worker or supervisor as
 * the main point of contact at the site, and the client should be told —
 * unless it was already said during the agreement. He then added the rule
 * that decides everything else about it: "The Professional remains
 * responsible for fulfilling the agreement, even when a staff member attends
 * instead."
 *
 * The professional half of this already existed. staff_members and shifts
 * have been there since Team & Workforce was built, and a named person could
 * already be put against an event. What was missing was the whole point of
 * his ask: the client never saw any of it, and nothing marked one of those
 * people as the one to look for on the day.
 *
 * Three columns, and each answers one question:
 *
 *   bookings.point_of_contact_id      who will be there
 *   bookings.point_of_contact_set_at  when that was decided, so a change
 *                                     made after signing can be told apart
 *                                     from the choice made before it
 *   finalizations.staff_may_attend    whether the client agreed to it up
 *                                     front, which is what makes a later
 *                                     swap expected rather than a surprise
 *
 * The staff row is NOT an account. These are names with a phone number, as
 * they have always been. Whether staff should be real users is Sir Peter's
 * decision and a far larger build; nothing here forecloses it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // nullOnDelete, not cascade: a crew member leaving must not take
            // the booking with them. It falls back to the professional, which
            // is who was responsible the whole time.
            $table->foreignId('point_of_contact_id')->nullable()->after('supplier_id')
                ->constrained('staff_members')->nullOnDelete();
            $table->timestamp('point_of_contact_set_at')->nullable()->after('point_of_contact_id');
        });

        Schema::table('finalizations', function (Blueprint $table) {
            /*
             * A term of the agreement, not a setting. It is false by default
             * on purpose: an agreement that never mentioned staff did not
             * agree to them, and reading silence as consent is exactly the
             * surprise he asked us to prevent.
             */
            $table->boolean('staff_may_attend')->default(false)->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('point_of_contact_id');
            $table->dropColumn('point_of_contact_set_at');
        });

        Schema::table('finalizations', function (Blueprint $table) {
            $table->dropColumn('staff_may_attend');
        });
    }
};
