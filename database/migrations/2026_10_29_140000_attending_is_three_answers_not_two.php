<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who attends is three answers, not a yes or no.
 *
 * Sir Peter, 29 October, after deciding against staff logins: "During
 * agreement setup, let the Professional select whether they will attend
 * personally, send a staff member, or decide later." The term built the day
 * before was a checkbox, and a checkbox cannot hold the third answer.
 *
 * The third one is the point of the change. "Decide later" is what a caterer
 * honestly wants in June for a booking in September, and a checkbox forces
 * them to claim something they do not know yet. It is also the only answer
 * that creates an obligation of its own: he asks for a confirmation at least
 * 24 hours before the event, which needs a state that says "nobody has said
 * yet" rather than a false that says "I am coming".
 *
 * And the first one now means something. PROFESSIONAL is a promise to attend
 * personally, so a staff member cannot be named against it afterwards — his
 * own wording: the contact may change after signing "provided this does not
 * change an agreed personal-attendance obligation".
 *
 * The old column is backfilled, not read twice. A boolean kept beside this
 * would be a second opinion about the same question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->string('attendance', 16)->default('professional')->after('payment_terms');
        });

        // true meant "a staff member may come", which is the STAFF answer.
        // false meant the default, which is the professional themselves.
        DB::table('finalizations')->where('staff_may_attend', true)->update(['attendance' => 'staff']);

        Schema::table('finalizations', function (Blueprint $table) {
            $table->dropColumn('staff_may_attend');
        });

        Schema::table('bookings', function (Blueprint $table) {
            /*
             * When the professional said who is actually coming, for an
             * agreement that left it open. Apart from point_of_contact_set_at
             * because confirming "I am coming myself" names nobody, and that
             * is still an answer.
             */
            $table->timestamp('attendance_confirmed_at')->nullable()->after('point_of_contact_set_at');
        });
    }

    public function down(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->boolean('staff_may_attend')->default(false)->after('payment_terms');
        });

        DB::table('finalizations')->where('attendance', 'staff')->update(['staff_may_attend' => true]);

        Schema::table('finalizations', function (Blueprint $table) {
            $table->dropColumn('attendance');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('attendance_confirmed_at');
        });
    }
};
