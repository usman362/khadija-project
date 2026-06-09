<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * My Calendar — the professional's schedule home. Explains the calendar +
 * shows a live snapshot of their upcoming agenda, a month grid with markers,
 * and availability for the days ahead. "View Calendar" / "Go to Full
 * Calendar" deep-link to the full month view.
 *
 * REAL data: the agenda unifies the pro's assigned Shifts (staffing
 * subsystem — real role/location/start-end/status) with their booking
 * Events. The month grid marks every day that has a shift or event. The
 * availability strip derives "Fully Booked" vs "Available" from whether a
 * day already has scheduled items. Phone-sync + travel-time cards are
 * explainer UI.
 *
 * Route: GET /professional/calendar
 */
class ProfessionalCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $now   = now();
        $today = $now->copy()->startOfDay();

        // ── Build a unified schedule (shifts + booking events) ─────
        $items = collect();

        $shifts = Shift::where('supplier_id', $user->id)
            ->whereNotNull('starts_at')
            ->where('ends_at', '>=', $today)
            ->with(['event:id,title,location'])
            ->orderBy('starts_at')
            ->get();

        foreach ($shifts as $sh) {
            $title = $sh->event?->title ?: ($sh->role ? $sh->role . ' Shift' : 'Shift');
            $items->push([
                'title'    => $title,
                'sub'      => $sh->role && $sh->event ? $sh->role : ($sh->location ?: 'On-site'),
                'start'    => $sh->starts_at,
                'end'      => $sh->ends_at,
                'all_day'  => false,
                'kind'     => 'shift',
                'status'   => $sh->status,
                'color'    => $sh->status === 'open' ? '#f59e0b' : '#2563eb',
            ]);
        }

        $bookings = Booking::where('supplier_id', $user->id)
            ->whereIn('status', ['confirmed', 'requested', 'completed'])
            ->whereHas('event', fn ($q) => $q->whereNotNull('starts_at')->where('starts_at', '>=', $today))
            ->with(['event:id,title,starts_at,ends_at,location'])
            ->get();

        foreach ($bookings as $bk) {
            $ev = $bk->event;
            if (! $ev) {
                continue;
            }
            $allDay = ! $ev->ends_at || $ev->starts_at->diffInHours($ev->ends_at) >= 22;
            $items->push([
                'title'    => $ev->title,
                'sub'      => $ev->location ?: 'Event',
                'start'    => $ev->starts_at,
                'end'      => $ev->ends_at,
                'all_day'  => $allDay,
                'kind'     => 'event',
                'status'   => $bk->status,
                'color'    => $allDay ? '#10b981' : '#8b5cf6',
            ]);
        }

        $items = $items->sortBy('start')->values();

        // Today's agenda = next few upcoming items (today first, else soonest).
        $agenda = $items->take(3);

        // ── Day markers (Y-m-d => color) for the month grid ────────
        $markers = [];
        foreach ($items as $it) {
            $key = $it['start']->format('Y-m-d');
            $markers[$key] = $markers[$key] ?? [];
            if (count($markers[$key]) < 3) {
                $markers[$key][] = $it['color'];
            }
        }

        // ── Month grid (leading/trailing days for full weeks) ──────
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();
        $gridStart  = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd    = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);

        $grid = [];
        for ($d = $gridStart->copy(); $d <= $gridEnd; $d->addDay()) {
            $key = $d->format('Y-m-d');
            $grid[] = [
                'day'      => $d->day,
                'inMonth'  => $d->month === $now->month,
                'isToday'  => $d->isSameDay($today),
                'markers'  => $markers[$key] ?? [],
            ];
        }

        // ── Availability strip: next 4 days, busy if scheduled ─────
        $availability = [];
        for ($i = 1; $i <= 4; $i++) {
            $day = $today->copy()->addDays($i);
            $busy = $items->contains(fn ($it) => $it['start']->isSameDay($day));
            $availability[] = [
                'date'  => $day,
                'label' => $busy ? 'Fully Booked' : 'Available',
                'busy'  => $busy,
            ];
        }

        // ── Stats ──────────────────────────────────────────────────
        $stats = [
            'upcoming'    => $items->count(),
            'this_month'  => $items->filter(fn ($it) => $it['start']->month === $now->month && $it['start']->year === $now->year)->count(),
            'today'       => $items->filter(fn ($it) => $it['start']->isSameDay($today))->count(),
            'open_shifts' => $shifts->where('status', 'open')->count(),
        ];

        $availabilityStatus = optional($user->profile)->availability ?: 'available';
        $monthLabel = $now->format('F Y');

        return view('professional.calendar.index', compact(
            'agenda', 'grid', 'availability', 'stats', 'monthLabel', 'availabilityStatus', 'now'
        ));
    }
}
