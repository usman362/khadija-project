<?php

namespace App\Domain\Requests;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * The dates a request can be held on: the preferred one, and the backups.
 *
 * Sir Peter's Availability Match: "One event. One date for all services."
 * The client gives a preferred date and time, and up to five backup dates,
 * each with its own start and end. A professional confirms one of them on
 * their proposal. Every service on the request ends up on the same one.
 *
 * Backups were first saved as bare dates sharing the preferred times; they
 * are read either way, so a request saved then still shows its backups.
 */
final class EventDates
{
    public const MAX_BACKUPS = 5;

    /**
     * Backups as [date, start, end] rows, cleaned: no blanks, no repeats, never
     * the preferred day, in date order.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array{date: string, start: ?string, end: ?string}>
     */
    public static function normalize(array $rows, ?string $preferredDate = null, ?string $defaultStart = null, ?string $defaultEnd = null): array
    {
        $out = [];

        foreach ($rows as $row) {
            $date = is_array($row) ? ($row['date'] ?? null) : $row;
            if (blank($date)) {
                continue;
            }

            try {
                $day = Carbon::parse($date)->toDateString();
            } catch (\Throwable) {
                // A value that is not a date (sent back after a failed save)
                // is dropped here; validation has already said why.
                continue;
            }
            if ($day === $preferredDate || isset($out[$day])) {
                continue;
            }

            $out[$day] = [
                'date'  => $day,
                'start' => (is_array($row) ? ($row['start'] ?? null) : null) ?: $defaultStart,
                'end'   => (is_array($row) ? ($row['end'] ?? null) : null) ?: $defaultEnd,
            ];
        }

        ksort($out);

        return array_values($out);
    }

    /**
     * Every date the request can be held on, preferred first.
     *
     * @return array<int, array{date: string, start: ?string, end: ?string, primary: bool}>
     */
    public static function options(Event $event): array
    {
        if (! $event->starts_at) {
            return [];
        }

        $start = $event->starts_at->format('H:i');
        $end = $event->ends_at?->format('H:i');

        $rows = [['date' => $event->starts_at->toDateString(), 'start' => $start, 'end' => $end, 'primary' => true]];

        foreach (self::normalize((array) $event->backup_dates, $event->starts_at->toDateString(), $start, $end) as $b) {
            $rows[] = $b + ['primary' => false];
        }

        return $rows;
    }

    /** The option matching a day, if the request offers it. */
    public static function option(Event $event, ?string $date): ?array
    {
        if (! $date) {
            return null;
        }

        $day = Carbon::parse($date)->toDateString();

        foreach (self::options($event) as $o) {
            if ($o['date'] === $day) {
                return $o;
            }
        }

        return null;
    }

    /** "Sat, Sep 19, 2026 · 5:00 PM – 11:00 PM" for one option. */
    public static function label(array $o, bool $withDay = true): string
    {
        $d = Carbon::parse($o['date']);
        $time = $o['start'] ? Carbon::parse($o['start'])->format('g:i A') . ($o['end'] ? ' – ' . Carbon::parse($o['end'])->format('g:i A') : '') : null;

        return $d->format($withDay ? 'D, M j, Y' : 'M j, Y') . ($time ? ' · ' . $time : '');
    }
}
