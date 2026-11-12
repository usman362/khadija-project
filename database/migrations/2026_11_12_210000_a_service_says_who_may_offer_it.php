<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who may offer a service, from Sir Peter's Master Service Classification
 * (8 November): 213 professional only, 28 either, 15 influencer only.
 *
 * Until now every row in the tree was implicitly a professional's. The
 * platform has influencers, it has an affiliate programme, and it has no way
 * at all to say that Social Media Takeovers is not something a caterer offers
 * or that Valet Parking is not something an influencer does.
 *
 * A column on the row rather than a table of exceptions, because this is a
 * property of the service itself and there are exactly three answers. It
 * defaults to `professional`, which is what all 241 existing rows are unless
 * the classification says otherwise, so nothing changes until something sets
 * it.
 *
 * The word is `eligibility` and not `role`: a row of kind `service` is not a
 * role, it is a thing that can be done, and this says who is allowed to say
 * they do it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('eligibility', 16)->default('professional')->after('kind')->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['eligibility']);
            $table->dropColumn('eligibility');
        });
    }
};
