<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The three facts a virtual or hybrid event has that a physical one does not.
 *
 * The Virtual & Hybrid brief already asked for the platform — the form had Zoom,
 * Teams, Hopin and the rest as radio buttons — but nothing validated or stored
 * it, and the radios carried no value, so the browser submitted "on" and the
 * controller dropped it anyway. A client picked their platform and the answer
 * went nowhere. These give those answers somewhere to live, and the event-day
 * screen something true to show.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // virtual | hybrid. Null for an ordinary in-person event.
            $table->string('event_format', 12)->nullable()->after('event_type');
            $table->string('platform', 60)->nullable()->after('event_format');
            // Only ever what the client pastes in. Nothing here creates a
            // meeting; there is no Zoom integration and the screen says so.
            $table->string('meeting_url', 500)->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn(['event_format', 'platform', 'meeting_url']));
    }
};
