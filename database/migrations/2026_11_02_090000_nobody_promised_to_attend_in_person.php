<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The default answer was a promise nobody made.
 *
 * `attendance` arrived on 29 October with a default of 'professional', and
 * PointOfContact's own note says why that is wrong: "PROFESSIONAL is a
 * promise, not a default. It is the answer that stops a staff member being
 * sent." Three things made the default reachable rather than theoretical:
 *
 *   - `Award::openFinalization` creates every agreement without the field, so
 *     each one is born saying the professional will attend personally.
 *   - Both controllers validate it as `nullable`, so an agreement can be
 *     edited and signed without the question ever being put.
 *   - The 29 October backfill read the old `staff_may_attend` boolean, which
 *     had itself only just been added with a default of false. Nobody had
 *     answered it either, so every existing agreement landed on the promise.
 *
 * What that costs: `assertNotPromisedInPerson` refuses to let a professional
 * name a staff member against an agreement that says they come in person. So
 * a column default would have locked every professional out of sending a crew
 * member, on every booking signed before the question existed, and the
 * contract would have printed the personal-attendance clause to the client as
 * though it had been agreed.
 *
 * LATER is the honest un-answered state, and it is the one the system already
 * handles: `AttendanceChase` asks the professional to settle it once the
 * client has signed, and the contract prints "has not yet said whether they
 * will attend personally". Nothing is chased before the signature, so a
 * newly-opened agreement sits quiet while its terms are still being argued.
 *
 * Only rows that CANNOT have been answered are corrected: the column did not
 * exist before 29 October, so anything older than that carries the default
 * rather than a choice. A professional who has since chosen 'professional'
 * keeps it.
 */
return new class extends Migration
{
    private const ANSWER_EXISTED_FROM = '2026-10-29';

    public function up(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->string('attendance', 16)->default('later')->change();
        });

        DB::table('finalizations')
            ->where('attendance', 'professional')
            ->where('created_at', '<', self::ANSWER_EXISTED_FROM)
            ->update(['attendance' => 'later']);
    }

    /**
     * The rows are deliberately not put back. Reversing the default is one
     * thing; re-asserting a personal-attendance promise over agreements whose
     * professionals never made one is another, and it is the bug.
     */
    public function down(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->string('attendance', 16)->default('professional')->change();
        });
    }
};
