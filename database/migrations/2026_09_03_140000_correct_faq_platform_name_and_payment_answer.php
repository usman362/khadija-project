<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * OA-133 (CRITICAL) — the FAQ called the platform GIGS and named the payment
 * providers.
 *
 * Two separate problems on the most-read public page:
 *
 *  1. "How does GIGS work?" — the platform is GigResource. The first question
 *     a visitor reads gave it a different name.
 *  2. "We accept all major credit and debit cards through Stripe, as well as
 *     PayPal." Naming providers publicly is a commitment, and PayPal is not
 *     connected at all. It also promised something not yet true.
 *
 * The same answer described professionals as "verified", which is the claim
 * ISSUE-1 was about: no professional on the platform has a completed
 * verification behind them today.
 *
 * Done as a migration rather than by re-running FaqSeeder. The seeder matches
 * on the question text, and the question text is one of the things being
 * corrected — re-running it would leave the old "How does GIGS work?" row in
 * place and add the new one beside it. That is exactly how 47 duplicate
 * categories arrived in August.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('faqs')
            ->where('question', 'How does GIGS work?')
            ->update([
                'question' => 'How does GigResource work?',
                'answer' => 'GigResource connects event organizers (clients) with event service '
                    .'professionals (photographers, caterers, DJs, planners and more). Create an '
                    .'account, browse professionals by service, send a request, agree the details '
                    .'through the built-in messaging, and book. You can post one request and let '
                    .'professionals respond, or send a request straight to a professional you have '
                    .'chosen.',
            ]);

        DB::table('faqs')
            ->where('question', 'What payment methods are accepted?')
            ->update([
                'answer' => 'Major credit and debit cards. Card details are entered on our payment '
                    ."provider's own secure page and are never stored on GigResource.",
            ]);
    }

    /**
     * Not reversible. Putting back a wrong platform name and a provider we are
     * not connected to is not a state worth being able to return to.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
