<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The help topics her file draws, and the eight questions under the first.
 *
 * The page carried six categories of its own making. Hers are eight, and a
 * visitor arriving from her screen would not find them. Nothing is deleted:
 * the eighteen questions already written are moved into whichever of her
 * eight they belong to, which is a change of label, not of content.
 *
 * The eight added below are hers. Only the first carries an answer she
 * supplied; the rest are answered from what this platform actually does, each
 * traceable:
 *
 *   state rule      config/geo.php, allowed_states, seven of them
 *   posting fee     App\Domain\Money\FeeSchedule, $2.99 / $1.99 / $2.99
 *   refunds         FeeSchedule::refundPolicy()
 *   payouts         requested, never scheduled (ProfessionalEarningsController)
 *   age             the registration form's own line, "at least 18"
 *
 * One of hers has no settled policy behind it on this platform: what to do
 * when a booking goes wrong. Disputes are not built and the rules for them
 * have not been given, so its answer says where to take the problem and
 * claims nothing about how it is resolved.
 */
return new class extends Migration
{
    /** old label => her label */
    private const MOVES = [
        'General'              => 'Getting Started',
        'Billing'              => 'Payments & Payouts',
        'Bookings & Payments'  => 'Events & Bookings',
        'Account & Membership' => 'Account & Settings',
        'For Professionals'    => 'Requests & Proposals',
        // Trust & Safety is already one of hers.
    ];

    private function added(): array
    {
        $states = implode(', ', array_values(config('geo.allowed_states', [])));

        return [
            ['How do I create an account on GigResource?',
             'You can create an account as a Client, Professional, Influencer, or Affiliate. Click the "Sign Up" button, select your role, and follow the steps to complete registration. All users must be 18+ and located in one of our supported states. After your account is created, you can complete your profile and start using GigResource.'],

            ['Do I need to be in the same state as the event?',
             'You can open an account from anywhere, but GigResource operates in ' . $states . '. Posting a request, bidding and booking are limited to those states. An account outside them is kept on the list for when we open there, and you will be told which it is.'],

            ['Is there a fee to post a request?',
             'Yes. Posting a request costs $2.99, charged when it goes out to professionals; drafts are free. Extending a request that is already live is $1.99 per extension, and a repost is $2.99. Editing a request that is still live costs nothing. Every fee is charged once and is not refundable.'],

            ['How do I find and hire a professional or influencer?',
             'Two ways, and you can use either. Browse Find Professionals and send a request straight to someone you have chosen, or post one request and let professionals come to you with proposals. Agree the details in the built-in messaging, then book.'],

            ['Can I request multiple services for one event?',
             'Yes. A request covers one service or several. A multi-service request puts every service for the same event in one post, and each professional bids on the parts they offer, so you are not posting the same event four times.'],

            ['When do I receive my payout? (For Professionals)',
             'Payouts are requested, not scheduled. Once a job is complete and the client\'s payment has cleared, the amount shows as available on your Payments & Payouts page and you ask for it from there. GigResource does not set a payout date, so nothing on that page predicts one.'],

            ['What can I do if I have an issue with a booking?',
             'Start in the booking\'s own message thread: most problems are a detail that has not been agreed yet, and the thread is the record of what was. If that does not settle it, contact support with the booking in front of you and we will take it from there.'],

            ['How do I contact support?',
             'Use Contact Support at the foot of this page. It opens a form that reaches the support team with your account attached, so you do not have to explain who you are first.'],
        ];
    }

    public function up(): void
    {
        foreach (self::MOVES as $was => $now) {
            DB::table('faqs')->where('category', $was)->update(['category' => $now]);
        }

        // Hers sort first within the topic; the ones already written follow.
        DB::table('faqs')->where('category', 'Getting Started')->increment('sort_order', 100);

        foreach ($this->added() as $i => [$question, $answer]) {
            if (DB::table('faqs')->where('question', $question)->exists()) {
                continue;
            }

            DB::table('faqs')->insert([
                'question'   => $question,
                'answer'     => $answer,
                'category'   => 'Getting Started',
                'sort_order' => $i + 1,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('faqs')->whereIn('question', array_column($this->added(), 0))->delete();

        foreach (self::MOVES as $was => $now) {
            DB::table('faqs')->where('category', $now)->update(['category' => $was]);
        }
    }
};
