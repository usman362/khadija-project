<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The rename of 6 November moved whole categories at once, which put four
 * questions somewhere they do not belong. Checked one by one:
 *
 *   commission            is money, not a proposal
 *   influencer referral   is an account's own programme
 *   "Can I cancel a booking?"        is a booking, not a payment
 *   "What is an Emergency Service    is a kind of request
 *    Request?"
 *
 * Only the label changes, and every one stays reachable from the sidebar,
 * the topic cards and the search.
 */
return new class extends Migration
{
    private const MOVES = [
        'How is my commission calculated?'                => ['Requests & Proposals', 'Payments & Payouts'],
        'How does the influencer referral program work?'  => ['Requests & Proposals', 'Account & Settings'],
        'Can I cancel a booking?'                         => ['Payments & Payouts',   'Events & Bookings'],
        'What is an Emergency Service Request?'           => ['Events & Bookings',    'Requests & Proposals'],
    ];

    public function up(): void
    {
        foreach (self::MOVES as $question => [$was, $now]) {
            DB::table('faqs')->where('question', $question)->where('category', $was)
                ->update(['category' => $now]);
        }
    }

    public function down(): void
    {
        foreach (self::MOVES as $question => [$was, $now]) {
            DB::table('faqs')->where('question', $question)->where('category', $now)
                ->update(['category' => $was]);
        }
    }
};
