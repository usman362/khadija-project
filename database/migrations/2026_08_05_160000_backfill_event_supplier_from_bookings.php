<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Point every awarded event at the professional who won it.
 *
 * Accepting a bid created the booking but never stamped the event, so the
 * event still looked unclaimed. Two things followed from that:
 *
 *   • My Gigs counts events and the other two pages count bookings, so the
 *     same professional saw 3 jobs on Contracts and Gig Operations Hub and 1
 *     on My Gigs — the defect Sir Peter's team reported.
 *
 *   • The bidding board decides what is still open by looking for events with
 *     no supplier. An awarded job stayed on the board and other professionals
 *     went on bidding for work that was already taken.
 *
 * Booking::booted() keeps them in step from here on; this fixes the rows that
 * were already wrong.
 *
 * Events with two or more confirmed bookings are left alone and reported —
 * picking a winner between them is a guess, and R12 (one contract per service)
 * is the rule that actually governs them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $awarded = DB::table('bookings')
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('supplier_id')
            ->select('event_id', 'supplier_id')
            ->distinct()
            ->get()
            ->groupBy('event_id');

        $fixed = 0;
        $ambiguous = [];

        foreach ($awarded as $eventId => $rows) {
            if ($rows->count() > 1) {
                $ambiguous[] = $eventId;

                continue;
            }

            $fixed += DB::table('events')
                ->where('id', $eventId)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $rows->first()->supplier_id]);
        }

        if ($fixed > 0) {
            echo "  Stamped {$fixed} event(s) with the professional who won them.\n";
        }

        if ($ambiguous !== []) {
            echo '  Left alone — more than one confirmed booking, so the winner is not '
                . 'ours to choose (event ids: ' . implode(', ', $ambiguous) . ").\n";
        }
    }

    public function down(): void
    {
        // Not reversed: clearing these would put awarded jobs back on the
        // bidding board, which is the bug this migration exists to end.
    }
};
