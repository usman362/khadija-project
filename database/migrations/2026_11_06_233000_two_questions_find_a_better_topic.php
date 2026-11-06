<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two of the older questions sat under Getting Started because that is
 * where "General" became. Neither is about getting started:
 *
 *   "How do I join as a professional?"  is an account question
 *   "How is my data protected?"         is a trust question
 *
 * Moved to the topics they belong to, which also brings Getting Started
 * close to the eight her file shows. Nothing is deleted and both stay
 * reachable from the sidebar and the search.
 */
return new class extends Migration
{
    private const MOVES = [
        'How do I join as a professional?' => ['Getting Started', 'Account & Settings'],
        'How is my data protected?'        => ['Getting Started', 'Trust & Safety'],
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
