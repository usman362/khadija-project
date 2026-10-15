<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terms, Privacy and the Platform Disclaimer become records, not working copies.
 *
 * Sir Peter, 12 October 2026, on the verification wording: "Because the Terms
 * are stored in the database, I would also require version tracking, an
 * effective date, and a record of who approved the changes before
 * publication."
 *
 * He is right that the wording can go live without a deploy, and that is
 * exactly why it needed this. A legal page that can be edited in place has no
 * answer to the only question that matters afterwards: what did it say on the
 * day somebody agreed to it, and who signed that off.
 *
 * Nothing here publishes anything. `approved_at` staying null is the point:
 * until somebody records an approval, a changed page has not been approved,
 * and the application can tell the difference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_pages', function (Blueprint $table) {
            // Starts at 1 for every page that already exists, so the first
            // edit after this becomes version 2 rather than pretending the
            // current text was always there.
            $table->unsignedInteger('version')->default(1)->after('content');

            // The day the wording takes effect, which is not the day it was
            // typed. Null means it has not been published.
            $table->date('effective_on')->nullable()->after('version');

            // Who approved it, and when. Peter's condition for publication.
            $table->foreignId('approved_by')->nullable()->after('effective_on')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // What changed and why, for the audit trail the handoff asks for.
            $table->string('change_note')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('policy_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['version', 'effective_on', 'approved_at', 'change_note']);
        });
    }
};
