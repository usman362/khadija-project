<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R33 — event expiration, the free grace reopen, and the paid extension.
 *
 * One row per reactivation of a listing, free or paid. The paid-extension
 * count the cap is measured against is COUNTED from these rows rather than
 * kept as a column on the event: a counter and a payment history are two
 * copies of the same fact, and the day they disagree is the day a client is
 * either charged for a fourth extension or refused a second.
 *
 * `is_grace` marks the one free 24-hour reopen. §2 says it does not count
 * toward the cap, which is exactly why it has to be distinguishable from a
 * paid one in the same table.
 *
 * The event columns here are only what cannot be derived. Expiry itself is
 * NOT stored — a listing is expired when its deadline has passed and it has
 * not been awarded or closed, and a stored flag would need a scheduled job to
 * stay true and would be wrong in the window before that job ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 0 for a grace reopen: §2 gives it no length of its own, it just
            // puts the listing back until the client sets a real deadline.
            $table->unsignedSmallInteger('days')->default(0);
            $table->boolean('is_grace')->default(false);

            $table->decimal('amount', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_session_id')->nullable();
            $table->string('gateway_payment_id')->nullable();

            // §2 — a failed payment leaves the event Expired and grants
            // nothing. Only `completed` moves the deadline.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('failure_reason')->nullable();

            // What the deadline was, and what it became. Kept so a dispute
            // about "my listing closed early" has an answer.
            $table->timestamp('previous_deadline')->nullable();
            $table->timestamp('new_deadline')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::table('events', function (Blueprint $table) {
            // §1 — Close Event is a decision the client makes, so it is
            // recorded. Expiry is derived; closure is not.
            $table->timestamp('closed_at')->nullable()->after('published_at');

            // §6 and the search ranking in §2. A reactivated listing gets the
            // lighter "Event Reopened" notice and sits below the same day's
            // new listings — neither is answerable without knowing when it was
            // last reopened.
            $table->timestamp('reopened_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'reopened_at']);
        });

        Schema::dropIfExists('event_extensions');
    }
};
