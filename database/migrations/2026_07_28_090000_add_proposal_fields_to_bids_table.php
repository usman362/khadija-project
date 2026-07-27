<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a bid actually contains once it is a proposal rather than a number.
 *
 * A bid was an amount and a note. The client's Compare screen asks people to
 * judge "the full scope, terms and qualifications, not only price" — but there
 * was nowhere for a professional to put any of that. These are the fields the
 * Submit Your Bid wizard collects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            // Step 1 — an itemised breakdown behind the headline amount, and the
            // explanation the wizard requires when the bid sits above budget.
            $table->json('breakdown')->nullable()->after('amount');
            $table->text('above_budget_reason')->nullable()->after('breakdown');

            // Step 2 — the professional confirming they are free on the date.
            $table->boolean('available_confirmed')->default(false)->after('above_budget_reason');
            $table->text('availability_note')->nullable()->after('available_confirmed');

            // Steps 3 and 4 — how they will deliver it, and on what terms.
            $table->text('plan')->nullable()->after('availability_note');
            $table->text('terms')->nullable()->after('plan');

            // A draft bid is not visible to the client. Nothing enforced this
            // before because there was no way to save an unfinished bid.
            $table->timestamp('submitted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn([
                'breakdown', 'above_budget_reason', 'available_confirmed',
                'availability_note', 'plan', 'terms', 'submitted_at',
            ]);
        });
    }
};
