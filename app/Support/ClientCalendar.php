<?php

namespace App\Support;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * A client's events laid out as a day, a week or a month.
 *
 * Two screens draw a client calendar — the dashboard and My Events — and the
 * colour an entry wears has to mean the same thing on both. The stage table
 * lives here for that reason; the dashboard reads it too.
 */
final class ClientCalendar
{
    /**
     * The colour says the stage, and the legend lists exactly these.
     * Event::stage() decides the stage; this only names and colours it.
     */
    public const STAGES = [
        'confirmed' => ['Booked', '#10b981'],
        'open'      => ['Open for proposals', '#f59e0b'],
        'draft'     => ['Draft — not sent yet', '#9ca3af'],
        'completed' => ['Completed', '#6366f1'],
        'cancelled' => ['Cancelled', '#ef4444'],
    ];

    public const VIEWS = ['day', 'week', 'month'];

    /**
     * @return array{view:string, anchor:Carbon, first:Carbon, last:Carbon, title:string,
     *               prev:Carbon, next:Carbon, byDate:Collection, stagesShown:Collection}
     */
    public static function build(User $client, ?string $view, ?string $anchor): array
    {
        $view = in_array($view, self::VIEWS, true) ? $view : 'month';
        $now  = Carbon::now();

        // A bad date in the address is not a broken page; it is today.
        try {
            $at = $anchor ? Carbon::createFromFormat('Y-m-d', $anchor)->startOfDay() : $now->copy()->startOfDay();
        } catch (\Throwable) {
            $at = $now->copy()->startOfDay();
        }

        if ($view === 'day') {
            $first = $at->copy()->startOfDay();
            $last  = $at->copy()->endOfDay();
            $title = $at->isToday() ? 'Today · ' . $at->format('D, M j') : $at->format('l, M j, Y');
            $prev  = $at->copy()->subDay();
            $next  = $at->copy()->addDay();
        } elseif ($view === 'week') {
            $first = $at->copy()->startOfWeek(Carbon::SUNDAY);
            $last  = $at->copy()->endOfWeek(Carbon::SATURDAY);
            $title = $first->format('M j') . ' – ' . $last->format('M j, Y');
            $prev  = $at->copy()->subWeek();
            $next  = $at->copy()->addWeek();
        } else {
            $first = $at->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
            $last  = $at->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
            $title = $at->format('F Y');
            $prev  = $at->copy()->startOfMonth()->subMonth();
            $next  = $at->copy()->startOfMonth()->addMonth();
        }

        $byDate = Event::where('client_id', $client->id)
            ->whereBetween('starts_at', [$first->copy()->startOfDay(), $last->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status', 'is_published'])
            ->groupBy(fn ($e) => $e->starts_at->format('Y-m-d'));

        // Only the stages actually on screen. A legend listing states this
        // range does not contain is a legend for someone else's calendar.
        $stagesShown = $byDate->flatten()
            ->map(fn ($e) => $e->stage())
            ->unique()
            ->filter(fn ($s) => isset(self::STAGES[$s]))
            ->values();

        return compact('view', 'first', 'last', 'title', 'prev', 'next', 'byDate', 'stagesShown') + ['anchor' => $at];
    }

    /** Height of one hour in the day and week views, in pixels. */
    public const HOUR_PX = 48;

    /** The hours shown when nothing falls outside them. */
    public const DAY_STARTS = 7;
    public const DAY_ENDS   = 21;

    /**
     * The day and week views as a time grid: hours down the side, each event
     * placed at its start and as tall as it lasts.
     *
     * Both were a row of boxes like the month, so a four-hour reception and a
     * ten-minute call were the same small pill at the top of the day, and the
     * time was nowhere on screen. Ali asked for Today "line by line", the way
     * calendars are drawn.
     *
     * Events that overlap share the column side by side rather than covering
     * each other. An event with no end, or an end no later than its start,
     * is drawn an hour long — long enough to read, and no claim about a time
     * nobody gave.
     *
     * @param  array  $c  what build() returned, for a day or week view
     */
    public static function timeGrid(array $c): array
    {
        $days = $c['view'] === 'day'
            ? [$c['anchor']->copy()]
            : array_map(fn ($i) => $c['first']->copy()->addDays($i), range(0, 6));

        // Widen the hours to whatever is on screen, never narrow past the day.
        $from = self::DAY_STARTS;
        $to   = self::DAY_ENDS;
        foreach ($days as $d) {
            foreach ($c['byDate']->get($d->format('Y-m-d'), collect()) as $e) {
                $from = min($from, (int) $e->starts_at->format('G'));
                $end  = self::endOf($e);
                $to   = max($to, $end->isSameDay($e->starts_at) ? (int) ceil(($end->hour * 60 + $end->minute) / 60) : 24);
            }
        }
        $to = min(24, max($to, $from + 1));
        $span = ($to - $from) * 60;

        $cols = [];
        foreach ($days as $d) {
            $items = [];
            foreach ($c['byDate']->get($d->format('Y-m-d'), collect()) as $e) {
                $start = $e->starts_at->hour * 60 + $e->starts_at->minute - $from * 60;
                $end   = self::endOf($e);
                $stop  = ($end->isSameDay($e->starts_at) ? $end->hour * 60 + $end->minute : 24 * 60) - $from * 60;

                $start = max(0, $start);
                $stop  = min($span, max($stop, $start + 30));   // at least half an hour tall

                $items[] = ['event' => $e, 'start' => $start, 'stop' => $stop, 'ends' => $end];
            }

            usort($items, fn ($a, $b) => $a['start'] <=> $b['start']);

            // Side by side: each event takes the first lane free by its start.
            $laneEnds = [];
            foreach ($items as &$it) {
                $lane = null;
                foreach ($laneEnds as $i => $busyUntil) {
                    if ($busyUntil <= $it['start']) { $lane = $i; break; }
                }
                $lane ??= count($laneEnds);
                $laneEnds[$lane] = $it['stop'];
                $it['lane'] = $lane;
            }
            unset($it);

            $cols[] = ['date' => $d, 'items' => $items, 'lanes' => max(1, count($laneEnds))];
        }

        $now = Carbon::now();
        $nowMin = $now->hour * 60 + $now->minute - $from * 60;

        return [
            'from'   => $from,
            'to'     => $to,
            'px'     => self::HOUR_PX / 60,
            'height' => ($to - $from) * self::HOUR_PX,
            'cols'   => $cols,
            // Where "now" sits, if today is on screen and inside the hours.
            'nowTop' => $nowMin >= 0 && $nowMin <= $span ? round($nowMin * self::HOUR_PX / 60) : null,
            // Open scrolled to the first event, or to the morning.
            'scrollTo' => max(0, (int) round((collect($cols)->flatMap(fn ($col) => array_column($col['items'], 'start'))->min()
                ?? (8 - $from) * 60) * self::HOUR_PX / 60) - 24),
        ];
    }

    private static function endOf(Event $e): Carbon
    {
        return $e->ends_at && $e->ends_at->gt($e->starts_at) ? $e->ends_at : $e->starts_at->copy()->addHour();
    }
}
