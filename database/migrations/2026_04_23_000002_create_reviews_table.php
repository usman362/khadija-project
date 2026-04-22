<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews are written by one booking participant about the other after the
 * booking is completed. Either side (client about supplier, or supplier
 * about client) can leave one — enforced with a unique composite key so
 * the same reviewer can't hit the same booking twice.
 *
 * The reviewee can post ONE public response per review (like Yelp / Google).
 * Reviews are kept as historical records even if a user is soft-deleted —
 * foreign keys use `nullOnDelete` to avoid cascading review removal.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Who's reviewing whom, and on which booking.
            // Nullable on delete so reviews survive as historical records
            // even if the user is later soft-/hard-deleted.
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            // The review content itself.
            $table->unsignedTinyInteger('rating'); // 1..5, validated app-side
            $table->string('title')->nullable();   // optional one-liner
            $table->text('comment');

            // Reviewee's optional public reply — shown under the review.
            $table->text('response')->nullable();
            $table->timestamp('response_at')->nullable();

            // Moderation: hide review if it violates policy. Default visible.
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();

            // One review per (reviewer, reviewee, booking) triple — each
            // direction of each booking gets exactly one review.
            $table->unique(['reviewer_id', 'reviewee_id', 'booking_id'], 'reviews_unique_triple');

            // Hot lookup: "show me all reviews about user X, newest first".
            $table->index(['reviewee_id', 'is_hidden', 'created_at'], 'reviews_reviewee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
