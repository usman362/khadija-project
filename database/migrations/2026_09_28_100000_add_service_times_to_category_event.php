<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sir Peter, 27 September: "we need to have the timeline added into this as a
 * sub-step per service, so that the bidders can see when each service is to
 * start/end or a rough idea... just bc an event starts at a certain time and
 * date, the timeline might needed as a option so that services dont over lap
 * or maybe they will need to."
 *
 * A request carried one start and one end for the whole event, so a DJ and a
 * photographer on the same booking were given the same hours whatever the
 * client actually wanted. The times belong to the service, which is where the
 * services already live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('specialty_ids');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
