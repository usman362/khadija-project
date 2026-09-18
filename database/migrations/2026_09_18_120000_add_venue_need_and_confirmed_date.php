<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two answers the request flow now asks for and has to keep.
 *
 * `events.location_need` is the client's answer to "Do you already have a
 * venue or event location?": they have one, they need to find one, or they
 * are not sure yet. A client looking for a venue gives the towns they would
 * like it in, kept in `events.preferred_locations`.
 *
 * `bids.confirmed_date` is the day a professional says they can do, picked
 * from the client's preferred date and backup dates. It is what lets the
 * client see, on each proposal, whether it is for their date or a different
 * one, and it is what stops two services being accepted for different days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location_need', 16)->nullable()->after('location');
            $table->json('preferred_locations')->nullable()->after('location_need');
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->date('confirmed_date')->nullable()->after('available_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['location_need', 'preferred_locations']);
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn('confirmed_date');
        });
    }
};
