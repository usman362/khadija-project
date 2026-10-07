<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #115: every time in this application was printed in UTC.
 *
 * config('app.timezone') is UTC, which is right for storage, and nothing
 * anywhere converted for display — so an event created at 1:06 PM in
 * Baltimore logged itself as 5:06 PM, four hours out, on the same page that
 * also said "3 minutes ago". The review flagged it as a likely timezone
 * problem on one tab. It was every timestamp on every page.
 *
 * Nullable on purpose. An account that has never been asked is not in
 * America/New_York, it is unanswered, and DisplayTimezone works that out
 * from the state on the profile rather than writing a guess into the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('primary_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
