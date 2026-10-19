<?php

namespace App\Domain\Requests;

use App\Models\Category;
use App\Support\DisplayTimezone;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * A service cannot run outside the hours of the day it would be held on.
 *
 * Sir Peter, 18 October, with a screenshot of his own request: an event running
 * 10:00 AM to 11:00 PM, services set to end at 12:00 AM, and step 7 let him
 * carry on. "This system needs to catch that as an error and make the client not
 * skip or go forward until its fixed."
 *
 * Then, with a second screenshot: "it allowed ... the services to pick times
 * prior and after the event that went BEFORE and AFTER the initial timeframe,
 * this is an error that the system should catch each and everytime EVEN IF ITS
 * ON THE OPTIONAL OR BACKUP DATES."
 *
 * So every date the client is offering is checked, not only the preferred one.
 * A backup date carries its own hours, and accepting one whose window the
 * services do not fit would produce a booking nobody can honour.
 *
 * THE TRAP, and the reason this is not a string comparison: 12:00 AM is
 * numerically SMALLER than 11:00 PM and means the day after. Comparing the
 * times as the client typed them says midnight comes first, which passes
 * exactly the case he reported. Everything is resolved against its day first.
 *
 * It lives here rather than in the bidding request, which is its only caller
 * today, because `ServiceDetails::rules()` already asks for service times on
 * the emergency request and the direct offer as well, "so all three ask and
 * check the same way". Those two have no end-time field at all, so there is no
 * window of theirs to breach yet; the day one is added, this comes with it.
 */
final class ServiceWindow
{
    /**
     * Refuse any service that falls outside a day the request is offered on.
     *
     * @param  array<int|string, array{start?: ?string, end?: ?string}>  $serviceTimes
     * @param  array<int, mixed>  $backupDates  raw rows, normalised here
     *
     * @throws ValidationException
     */
    public static function assertServicesFit(
        ?string $date,
        ?string $startTime,
        ?string $endTime,
        array $serviceTimes,
        array $backupDates = [],
    ): void {
        if (blank($date) || blank($startTime) || $serviceTimes === []) {
            return;
        }

        $errors = [];

        foreach (self::offeredDays($date, $startTime, $endTime, $backupDates) as $day) {
            foreach (self::breaches($day, $serviceTimes) as $key => $message) {
                // First breach per field wins. A service that is wrong on three
                // dates is one thing to fix, and three messages under one box
                // reads as three problems.
                $errors[$key] ??= $message;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Every day this request is being offered on, with its own hours.
     *
     * The preferred date first, then the backups, each falling back to the main
     * hours where the client left its times blank, which is what the form
     * pre-fills.
     *
     * @return list<array{label: string, start: Carbon, end: ?Carbon}>
     */
    private static function offeredDays(
        string $date,
        string $startTime,
        ?string $endTime,
        array $backupDates,
    ): array {
        $rows = [['date' => $date, 'start' => $startTime, 'end' => $endTime]];

        foreach (EventDates::normalize(
            $backupDates,
            DisplayTimezone::parse($date)->toDateString(),
            $startTime,
            $endTime,
        ) as $backup) {
            $rows[] = $backup;
        }

        $days = [];

        foreach ($rows as $i => $row) {
            if (blank($row['start'] ?? null)) {
                continue;
            }

            $midnight = DisplayTimezone::parse($row['date'])->startOfDay();
            $start    = $midnight->copy()->setTimeFromTimeString($row['start']);
            $end      = blank($row['end'] ?? null)
                ? null
                : $midnight->copy()->setTimeFromTimeString($row['end']);

            // The same past-midnight roll the event's own hours already use.
            if ($end && $end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $days[] = [
                'label' => $i === 0 ? 'your event' : 'your backup date of ' . $start->format('M j'),
                'start' => $start,
                'end'   => $end,
            ];
        }

        return $days;
    }

    /**
     * Which services fall outside one day's hours.
     *
     * A day with no end time constrains nothing at its far edge: an open-ended
     * request is the client saying they do not know yet, and inventing a
     * boundary would reject timings that are perfectly fine.
     *
     * @return array<string, string> field key => message
     */
    private static function breaches(array $day, array $serviceTimes): array
    {
        $errors = [];

        foreach ($serviceTimes as $id => $row) {
            $from = trim((string) ($row['start'] ?? ''));
            $to   = trim((string) ($row['end'] ?? ''));

            // Nothing entered means it follows the day's own hours, so it
            // cannot breach them.
            if ($from === '' && $to === '') {
                continue;
            }

            $midnight     = $day['start']->copy()->startOfDay();
            $serviceStart = $from !== '' ? $midnight->copy()->setTimeFromTimeString($from) : $day['start']->copy();
            $serviceEnd   = $to !== '' ? $midnight->copy()->setTimeFromTimeString($to) : $day['end']?->copy();

            if ($serviceEnd && $serviceEnd->lessThanOrEqualTo($serviceStart)) {
                $serviceEnd->addDay();
            }

            $name = Category::find((int) $id)?->name ?? 'This service';

            if ($serviceStart->lessThan($day['start'])) {
                $errors["service_times.{$id}.start"] =
                    "{$name} starts before {$day['label']} does, which begins at "
                    . $day['start']->format('g:i A') . '. Change this time, or move the start of that day.';
            }

            if ($day['end'] && $serviceEnd && $serviceEnd->greaterThan($day['end'])) {
                $errors["service_times.{$id}.end"] =
                    "{$name} runs past the end of {$day['label']}, which ends at "
                    . $day['end']->format('g:i A') . '. Change this time, or extend that day.';
            }
        }

        return $errors;
    }
}
