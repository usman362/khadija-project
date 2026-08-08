<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R52's booking panel carries a checkbox: "Include this chat's agreed
 * points in the final contract."
 *
 * On the Threads page it was `<input type="checkbox" checked>` — no name, no
 * form, no column. It was ticked the moment the page loaded and recorded
 * nothing, so a professional had every reason to believe their conversation
 * was going into the contract when it was not.
 *
 * The column makes the answer real. It defaults to FALSE rather than carrying
 * the old pre-ticked state forward: the tick was never a decision anyone made,
 * and starting everyone at "yes" would turn a display bug into a data one.
 *
 * NOTE for whoever builds contract generation: nothing reads this yet. It
 * records the professional's choice so the choice exists to be honoured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('include_in_contract')->default(false)->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('include_in_contract');
        });
    }
};
