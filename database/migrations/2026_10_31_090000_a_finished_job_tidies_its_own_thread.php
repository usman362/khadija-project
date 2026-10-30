<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the platform put a conversation away by itself.
 *
 * Khadijah, 30 October: conversations move to Archived "once the event is
 * completed or the booking/request is formally closed or cancelled, provided
 * there are no outstanding disputes", and people can still archive and
 * restore by hand.
 *
 * Those two rules need this column to live together. Archiving is recorded
 * per person, on conversation_participants, and that is where a hand archive
 * goes. If the sweep read only that, it would look at a conversation somebody
 * had deliberately pulled back into their inbox, see it unarchived, and put
 * it away again on the next run. The person would restore it; the sweep would
 * win. For ever.
 *
 * So the sweep marks the conversation here, once. A conversation that carries
 * a date is one the platform has already dealt with and will not touch again,
 * whatever anybody does with it afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('auto_archived_at')->nullable()->after('include_in_contract');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('auto_archived_at');
        });
    }
};
