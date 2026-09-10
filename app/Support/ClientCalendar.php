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
}
