<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * OA-168 — a booking confirmed for a date that had already gone by when the
 * booking was made.
 *
 * Sir Peter, 23 September 2026: "Booking #58 is CONFIRMED for Mar 28, 2026 but
 * was created Jul 23, 2026 — an event date before the booking existed."
 *
 * Nothing in the platform can produce that: a booking is made before the work
 * happens, so bookings.created_at is never later than events.starts_at. The
 * rows that break it were seeded, and which end is wrong depends on the row:
 *
 *   a finished job   the work really did happen in the past, so the record is
 *                    what is late — its created_at is moved to before the
 *                    event, where a booking for that job would have been made.
 *
 *   an open booking  the client is still waiting for the work, so the date is
 *                    what is wrong — the event moves to after the record.
 *
 * Built like every other data command here: it reports first and changes
 * nothing without --force, and it only touches rows owned by a demo account.
 * A real client's event date is theirs, and a guess is worse than a wrong
 * date, so their rows are listed one by one for a human to decide.
 */
class FixImpossibleBookingDates extends Command
{
    protected $signature = 'demo:fix-dates
        {--force : actually repair the demo rows (otherwise this only reports)}';

    protected $description = 'Find bookings whose event date is before the booking was created, and repair the demo ones';

    public function handle(): int
    {
        $rows = Booking::query()
            ->with(['event:id,title,starts_at,ends_at,client_id', 'event.client:id,name,email'])
            ->whereHas('event', fn ($q) => $q->whereNotNull('starts_at')->whereColumn('events.starts_at', '<', 'bookings.created_at'))
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No booking has an event date earlier than the booking itself.');

            return self::SUCCESS;
        }

        $this->line($rows->count() . ' booking(s) are confirmed for a date that had already passed when they were made:');
        $this->newLine();

        $demo = 0;

        foreach ($rows as $booking) {
            $event = $booking->event;
            $email = (string) ($event->client->email ?? '');
            $isDemo = str_ends_with($email, '@example.test') || str_ends_with($email, '@example.com');
            $finished = in_array($booking->status, ['completed', 'cancelled', 'declined'], true);

            // Spread by the booking's own id, so the repaired rows do not all
            // land on one day and read as another batch of made-up data.
            $newEventDate = $finished ? null : $booking->created_at->copy()->addMonth()->addDays($booking->id % 40)
                ->setTime((int) $event->starts_at->format('H'), (int) $event->starts_at->format('i'));
            $newRecordDate = $finished ? $event->starts_at->copy()->subDays(21 + ($booking->id % 40)) : null;

            $this->line(sprintf(
                '  Booking #%-4d %s  %-9s  event %s  created %s  %s',
                $booking->id,
                str_pad(mb_substr((string) $event->title, 0, 28), 28),
                $booking->status,
                $event->starts_at->format('M j, Y'),
                $booking->created_at->format('M j, Y'),
                ! $isDemo
                    ? 'REAL ACCOUNT (' . $email . '), left alone'
                    : ($finished
                        ? 'record → ' . $newRecordDate->format('M j, Y')
                        : 'event → ' . $newEventDate->format('M j, Y'))
            ));

            if (! $isDemo) {
                continue;
            }

            $demo++;

            if (! $this->option('force')) {
                continue;
            }

            if ($finished) {
                // The booking record predates the work, which is the only
                // order a real one can be in.
                $booking->forceFill(['created_at' => $newRecordDate, 'booked_at' => $booking->booked_at ?: $newRecordDate])->saveQuietly();

                continue;
            }

            // The event's length is kept, so a two-day event stays two days.
            $length = $event->ends_at && $event->ends_at->gt($event->starts_at)
                ? $event->starts_at->diffInMinutes($event->ends_at)
                : null;

            $event->starts_at = $newEventDate;
            if ($length !== null) {
                $event->ends_at = $newEventDate->copy()->addMinutes($length);
            }
            $event->save();
        }

        $this->newLine();

        if (! $this->option('force')) {
            $this->warn($demo . ' demo row(s) would be repaired. Nothing was changed; run again with --force.');

            return self::SUCCESS;
        }

        $this->info($demo . ' demo row(s) repaired.');

        return self::SUCCESS;
    }
}
