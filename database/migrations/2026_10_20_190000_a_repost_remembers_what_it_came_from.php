<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A repost knows which posting it came from.
 *
 * Sir Peter's event lifecycle document, and his message of 20 October: "Repost
 * Event: $2.99; create a new posting ID and response round, linked to the
 * original." The copy was being made, and the link was not.
 *
 * It matters for more than tidiness. A repost is charged as a new posting, so
 * without the link there is no way to tell a client's third attempt at the same
 * job from three separate jobs, and no way to show them what the earlier round
 * produced. The document asks for the old history to stay "read-only/linked",
 * which needs something to link through.
 *
 * Deliberately a nullable self-reference with nullOnDelete: the original may be
 * deleted one day, and that should empty the link rather than take the repost
 * with it. A repost is its own posting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('reposted_from_id')->nullable()->after('id')
                ->constrained('events')->nullOnDelete();

            $table->index('reposted_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['reposted_from_id']);
            $table->dropConstrainedForeignId('reposted_from_id');
        });
    }
};
