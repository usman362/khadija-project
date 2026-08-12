<?php

namespace App\Support;

/**
 * Checklist row 197 — the Timeline Builder's second tab.
 *
 * The Gantt view draws tracks and percentage-width blocks; the operational
 * schedule is the same run-of-show read as a list of times. The spec's own
 * instruction is the important part: it must read the SAME event data. So
 * this DERIVES the table from the tracks the Gantt already renders, rather
 * than being handed a second dataset.
 *
 * A second dataset is how the two tabs end up disagreeing, and a client
 * standing in a venue reading one of them would have no way to know which.
 */
final class TimelineSchedule
{
    /**
     * Turn the Gantt's tracks into a time-ordered schedule.
     *
     * A block's `start` and `width` are percentages of the whole event
     * window, which is what the Gantt draws with. Converting them back to
     * clock times needs the window itself — the same `hours` row the Gantt
     * labels its columns with.
     *
     * @param array<int, array{0:string,1:string,2:array}> $tracks
     * @param array<int, string> $hours
     * @return array<int, array{
     *     time:string, ends:string, activity:string, track:string,
     *     colour:string, minutes:int
     * }>
     */
    public static function fromTracks(array $tracks, array $hours): array
    {
        if ($hours === []) {
            return [];
        }

        $start = self::parseHour($hours[0]);
        $end   = self::parseHour($hours[count($hours) - 1]);

        // The last label is the final column's start, so the window runs an
        // hour past it. Without this every activity drifts progressively
        // earlier the further into the evening it sits.
        $windowMinutes = max(60, ($end - $start + 60));

        $rows = [];

        foreach ($tracks as [$name, $colour, $blocks]) {
            foreach ($blocks as [$label, $offsetPct, $widthPct]) {
                $from = (int) round($start + ($offsetPct / 100) * $windowMinutes);
                $mins = (int) round(($widthPct / 100) * $windowMinutes);

                $rows[] = [
                    'time'     => self::clock($from),
                    'ends'     => self::clock($from + $mins),
                    'activity' => $label,
                    'track'    => $name,
                    'colour'   => $colour,
                    'minutes'  => $mins,
                ];
            }
        }

        usort($rows, fn ($a, $b) => self::parseClock($a['time']) <=> self::parseClock($b['time']));

        return $rows;
    }

    /** "5 PM" → minutes past midnight. */
    private static function parseHour(string $label): int
    {
        $label = trim($label);

        if (! preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i', $label, $m)) {
            return 0;
        }

        $hour = (int) $m[1] % 12;

        if (strtoupper($m[3]) === 'PM') {
            $hour += 12;
        }

        return $hour * 60 + (int) ($m[2] ?? 0);
    }

    private static function parseClock(string $clock): int
    {
        return self::parseHour($clock);
    }

    /** Minutes past midnight → "5:30 PM", wrapping past midnight. */
    private static function clock(int $minutes): string
    {
        $minutes = $minutes % (24 * 60);
        $hour    = intdiv($minutes, 60);
        $minute  = $minutes % 60;
        $suffix  = $hour >= 12 ? 'PM' : 'AM';
        $hour12  = $hour % 12 === 0 ? 12 : $hour % 12;

        return sprintf('%d:%02d %s', $hour12, $minute, $suffix);
    }
}
