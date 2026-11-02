<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a professional charges, per service they offer.
 *
 * They can already say WHICH services they do: that is the category_user
 * pivot. There has never been anywhere to say what any of them costs, and
 * three separate things are waiting on it.
 *
 * Create a Package asks them to "choose 2 or more of your existing services"
 * and shows a price beside each one, from nowhere. Quick Bid is meant to
 * answer a request without writing a proposal, which needs a number to
 * answer with. And Sir Peter's rough budget estimator was parked with the
 * note that a price range per service is a field on the professional's
 * profile and nobody has one. This is that field.
 *
 * On the pivot, because a price is an attribute of the offering rather than
 * of the person or of the service. Safe there: syncWithinKind detaches only
 * what was removed and uses syncWithoutDetaching for the rest, which leaves
 * existing rows alone, so re-saving the service list does not clear prices.
 * Dropping a service does drop its price, which is the right answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            // Nullable throughout: a professional who has not priced a service
            // yet has not said it is free.
            $table->decimal('price', 10, 2)->nullable()->after('category_id');

            // The same three the packages table uses, so a price means the
            // same thing wherever it is shown.
            $table->string('price_unit', 12)->default('from')->after('price');

            $table->unsignedSmallInteger('duration_hours')->nullable()->after('price_unit');
            $table->decimal('extra_hour_price', 10, 2)->nullable()->after('duration_hours');
        });
    }

    public function down(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_unit', 'duration_hours', 'extra_hour_price']);
        });
    }
};
