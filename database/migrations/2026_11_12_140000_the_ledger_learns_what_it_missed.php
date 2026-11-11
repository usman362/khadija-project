<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every booking made before the ledger was told about booking charges.
 *
 * Until 2026-11-12 nothing in this codebase wrote a BOOKING_CHARGE entry: the
 * two paths that create a booking recorded the agreement fee and stopped. That
 * is fixed going forward, which leaves every booking already made invisible to
 * `Ledger::bookingTotal()` and to anything built on it.
 *
 * Leaving them out is worse than it sounds, because the finance pages now read
 * from two places. Payments and Spending ask the ledger what has been paid,
 * and the Bookings page matches Payment rows by metadata. With no backfill,
 * one client's deposit would show on Bookings and not on Payments, which is
 * OA-146 again: four finance pages, two answers, on the screens somebody
 * checks before paying.
 *
 * The deposit is read exactly the way the Bookings page already reads it: a
 * completed Payment tagged `booking_deposit` whose metadata names the same
 * event and supplier. Nothing here invents a figure. A booking with no
 * matching payment gets its whole price as PENDING, which is true: it was
 * agreed and nothing was collected.
 *
 * Idempotent by construction. It writes only for bookings that have no booking
 * charge at all, so a second run finds nothing to do, and a booking funded
 * after today is skipped because the application already wrote its rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The deposits, keyed the way the existing code keys them. Summed
        // rather than taken singly: one event and one professional can carry
        // two services, and that is two deposits, not one.
        $deposits = [];

        foreach (DB::table('payments')->where('status', 'completed')->get() as $payment) {
            $meta = json_decode((string) $payment->metadata, true) ?: [];

            if (($meta['kind'] ?? null) !== 'booking_deposit') {
                continue;
            }

            $key = ($meta['event_id'] ?? '') . ':' . ($meta['supplier_id'] ?? '');
            $deposits[$key] ??= ['amount' => 0.0, 'payment_id' => $payment->id, 'mode' => $meta['mode'] ?? null];
            $deposits[$key]['amount'] += (float) $payment->amount;
        }

        $alreadyRecorded = DB::table('ledger_entries')
            ->where('entry_type', 'booking_charge')
            ->whereNotNull('booking_id')
            ->pluck('booking_id')
            ->flip();

        $rows = [];

        foreach (DB::table('bookings')->get() as $booking) {
            if ($alreadyRecorded->has($booking->id) || ! $booking->price) {
                continue;
            }

            $price = round((float) $booking->price, 2);
            $key = $booking->event_id . ':' . $booking->supplier_id;
            $paid = min(round((float) ($deposits[$key]['amount'] ?? 0), 2), $price);
            $owed = round($price - $paid, 2);

            // The booking's own date, not today's. A backfilled row that
            // claims to have happened during the migration would put a
            // two-month-old deposit at the top of a statement.
            $when = $booking->created_at ?: now();

            $common = [
                'event_id'      => $booking->event_id,
                'booking_id'    => $booking->id,
                'party_user_id' => $booking->client_id,
                'entry_type'    => 'booking_charge',
                'sign'          => '+',
                'currency'      => $booking->currency ?: 'USD',
                'subject_type'  => \App\Models\Booking::class,
                'subject_id'    => $booking->id,
                'occurred_at'   => $when,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if ($paid > 0) {
                $rows[] = $common + [
                    'amount'     => $paid,
                    'state'      => 'secured',
                    'payment_id' => $deposits[$key]['payment_id'] ?? null,
                    'metadata'   => json_encode([
                        'reason' => 'deposit',
                        'mode' => $deposits[$key]['mode'] ?? null,
                        'backfilled' => true,
                    ]),
                ];
            }

            if ($owed > 0) {
                $rows[] = $common + [
                    'amount'     => $owed,
                    'state'      => 'pending',
                    'payment_id' => null,
                    'metadata'   => json_encode(['reason' => 'balance', 'backfilled' => true]),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('ledger_entries')->insert($chunk);
        }
    }

    /**
     * Only what this wrote, found by its own mark.
     *
     * Deleting every booking charge would take the ones the application wrote
     * for bookings funded after today with it.
     */
    public function down(): void
    {
        DB::table('ledger_entries')
            ->where('entry_type', 'booking_charge')
            ->where('metadata', 'like', '%"backfilled":true%')
            ->delete();
    }
};
