<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof that we told them.
 *
 * Washington DC's Automatic Renewal Protections Act of 2018 requires notice
 * before the first renewal and before every renewal after it. "We send those"
 * is not a defence; a record of what was sent, to whom, for which renewal, on
 * what day, is.
 *
 * It is also what stops a second notice going out for the same renewal when
 * the command runs twice in a day, or a deploy replays it: the unique key is
 * (subscription, the renewal it is about, how many days ahead), so the same
 * notice cannot be sent twice however often the job runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_renewal_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_subscription_id')->constrained()->cascadeOnDelete();

            // The renewal this notice is about, not the day it was sent — a
            // subscription renews many times and each one needs its own.
            $table->date('renews_on');

            // 30 days before, 7 days before. Two notices for one renewal are
            // two rows, and neither can repeat.
            $table->unsignedSmallInteger('days_before');

            $table->timestamp('sent_at');
            $table->string('sent_to');
            $table->timestamps();

            $table->unique(['user_subscription_id', 'renews_on', 'days_before'], 'renewal_notice_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_renewal_notices');
    }
};
