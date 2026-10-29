<?php

namespace App\Domain;

use App\Models\AttendanceReminder;
use App\Models\Booking;
use App\Models\Finalization;
use App\Notifications\NobodyHasSaidWhoIsComing;
use Illuminate\Support\Collection;

/**
 * Chasing the answer a professional promised to come back with.
 *
 * Sir Peter, 29 October, point 8: when a professional says they will decide
 * nearer the time, "require confirmation at least 24 hours before the event,
 * send reminders, and flag missed deadlines".
 *
 * Three stages, each sent once. FIRST is early enough to be acted on without
 * rearranging anything. FINAL is the last comfortable moment. MISSED is after
 * the deadline, and it is the only one that says something has gone wrong,
 * because saying so earlier would make the first two read as complaints.
 *
 * WHO IS TOLD, and it is a decision rather than an oversight. The professional
 * only. The obligation is theirs and the screen that settles it is theirs, and
 * a message to the client saying nobody has confirmed, the day before their
 * wedding, gives them alarm and nothing to do with it. Whether the client
 * should hear about a missed deadline is Sir Peter's call; the miss is
 * recorded either way, so adding them later is one line here.
 */
final class AttendanceChase
{
    public const FIRST  = 'first';
    public const FINAL  = 'final';
    public const MISSED = 'missed';

    /**
     * How many days before the EVENT each reminder goes out.
     *
     * Counted from the event rather than from the deadline so the words match
     * what somebody reads: "your event is in a week" is a date they know.
     */
    public const LEADS = [self::FIRST => 7, self::FINAL => 2];

    /**
     * Every booking still owing an answer, with the stage it is due.
     *
     * @return Collection<int, array{booking: Booking, stage: string, confirmBy: \Illuminate\Support\Carbon}>
     */
    public static function due(): Collection
    {
        /*
         * Only agreements that left it open AND that the client has signed.
         * Before the signature there is no promise to chase, which is the
         * same line PointOfContact::settled draws.
         */
        $open = Finalization::query()
            ->where('attendance', PointOfContact::LATER)
            ->whereNotNull('client_signed_at')
            ->pluck('booking_id', 'id')
            ->filter()
            ->values();

        if ($open->isEmpty()) {
            return collect();
        }

        return Booking::query()
            ->whereIn('id', $open)
            ->whereNull('attendance_confirmed_at')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['event:id,title,starts_at', 'supplier:id,name'])
            ->get()
            ->map(fn (Booking $b) => self::stageFor($b))
            ->filter()
            ->values();
    }

    /**
     * Send what is due, once each.
     *
     * The record is written BEFORE the notice, and its unique index is what
     * makes that safe: two runs at once both try to insert, one loses, and
     * the loser sends nothing rather than both sending.
     *
     * @return int how many went out
     */
    public static function run(bool $dryRun = false): int
    {
        $sent = 0;

        foreach (self::due() as $row) {
            ['booking' => $booking, 'stage' => $stage, 'confirmBy' => $confirmBy] = $row;

            if ($dryRun) {
                $sent++;

                continue;
            }

            try {
                AttendanceReminder::create([
                    'booking_id' => $booking->id,
                    'stage'      => $stage,
                    'sent_at'    => now(),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // Already gone out, from an earlier run or a parallel one.
                continue;
            }

            $booking->supplier?->notify(new NobodyHasSaidWhoIsComing($booking, $stage, $confirmBy));
            $sent++;
        }

        return $sent;
    }

    /**
     * Which stage this booking is due, or null when none is.
     *
     * The latest stage reached that has not been sent, so a booking that was
     * added a day before its event does not receive the week-out reminder
     * first and the real one never.
     *
     * @return array{booking: Booking, stage: string, confirmBy: \Illuminate\Support\Carbon}|null
     */
    private static function stageFor(Booking $booking): ?array
    {
        $starts = $booking->event?->starts_at;

        // No date to count back from, so no deadline to miss. An invented one
        // would chase somebody over an event that may not be happening.
        if (! $starts) {
            return null;
        }

        $confirmBy = $starts->copy()->subHours(PointOfContact::CONFIRM_HOURS);
        $sent      = AttendanceReminder::where('booking_id', $booking->id)->pluck('stage')->all();

        $stage = match (true) {
            now()->greaterThan($confirmBy)                       => self::MISSED,
            now()->greaterThanOrEqualTo($starts->copy()->subDays(self::LEADS[self::FINAL])) => self::FINAL,
            now()->greaterThanOrEqualTo($starts->copy()->subDays(self::LEADS[self::FIRST])) => self::FIRST,
            default                                              => null,
        };

        if ($stage === null || in_array($stage, $sent, true)) {
            return null;
        }

        return ['booking' => $booking, 'stage' => $stage, 'confirmBy' => $confirmBy];
    }

    /** Has this booking blown its deadline? Asked by the screens. */
    public static function overdue(Booking $booking): bool
    {
        $by = PointOfContact::confirmBy($booking);

        return $by !== null && now()->greaterThan($by);
    }
}
