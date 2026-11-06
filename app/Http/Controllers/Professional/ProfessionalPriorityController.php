<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Shift;
use App\Support\Earnings;
use App\Support\PriorityActions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Priority Actions, as Khadijah's 28 October screen has it: one list of the
 * individual things waiting on this professional, filtered by tabs, with a
 * right hand column for today's schedule, the deadlines coming, and how the
 * last thirty days went.
 *
 * The page used to show four counters. A counter reading "3" tells somebody
 * three contracts need signing without telling them which three, so the list
 * is built from the records themselves in App\Support\PriorityActions.
 *
 * Route: GET /professional/priority-actions
 */
class ProfessionalPriorityController extends Controller
{
    public function index(Request $request): View
    {
        $me  = $request->user();
        $tab  = (string) $request->query('tab', 'all');
        $span = $request->query('span') === 'week' ? 'week' : 'today';

        $all  = PriorityActions::forProfessional($me);
        $tabs = PriorityActions::tabs($all);

        if (! array_key_exists($tab, $tabs)) {
            $tab = 'all';
        }

        // The parentheses are required: a match expression cannot be
        // chained off directly.
        $items = (match ($tab) {
            'urgent'     => $all->where('tone', 'urgent'),
            'soon'       => $all->where('tone', 'soon'),
            'clients'    => $all->where('group', 'clients'),
            'payments'   => $all->where('group', 'payments'),
            'agreements' => $all->where('group', 'agreements'),
            default      => $all,
        })->values();

        return view('professional.priority.index', [
            'items'    => $items,
            'tabs'     => $tabs,
            'tab'      => $tab,
            'total'    => $all->count(),
            'span'     => $span,
            'today'    => $this->today($me, $span),
            'deadlines' => $all->filter(fn ($i) => $i['due'] !== null)->take(5)->values(),
            'snapshot' => $this->snapshot($me),
        ]);
    }

    /**
     * What is actually happening today: the jobs, and the shifts on them.
     *
     * Read in the timezone the rest of the site displays in, not the one the
     * database stores in, or a late evening job shows up on the wrong day.
     */
    private function today($me, string $span = 'today'): array
    {
        $zone  = config('app.display_timezone', config('app.timezone'));
        $start = Carbon::today($zone)->startOfDay()->utc();
        $end   = $span === 'week'
            ? Carbon::today($zone)->addDays(7)->endOfDay()->utc()
            : Carbon::today($zone)->endOfDay()->utc();

        $gigs = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereHas('event', fn ($q) => $q->whereBetween('starts_at', [$start, $end]))
            ->with('event:id,title,starts_at,location')
            ->get()
            ->map(fn (Booking $b) => [
                'at'    => $b->event?->starts_at,
                'title' => $b->event?->title,
                'where' => $b->event?->location,
            ]);

        $shifts = Shift::where('supplier_id', $me->id)
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(fn (Shift $s) => [
                'at'    => $s->starts_at,
                'title' => $s->role ?: 'Shift',
                'where' => $s->location,
            ]);

        return $gigs->concat($shifts)
            ->filter(fn ($row) => $row['at'] !== null)
            ->sortBy(fn ($row) => $row['at']->timestamp)
            ->values()
            ->all();
    }

    /**
     * The last thirty days, in figures this professional's own records can
     * answer for. No targets and no comparisons against other accounts.
     */
    private function snapshot($me): array
    {
        $since = now()->subDays(30);
        $money = Earnings::forProfessional($me);

        $before = now()->subDays(60);

        /*
         * Each figure beside the one before it.
         *
         * Khadijah's snapshot carries an arrow and a percentage on every row.
         * This is the only honest version of that: this professional's own
         * last thirty days against their own thirty before. Null where there
         * was nothing to compare against, because everything is not an
         * infinite improvement on nothing.
         */
        $change = function (int $now, int $was): ?int {
            if ($was === 0) {
                return null;
            }

            return (int) round(($now - $was) / $was * 100);
        };

        $requests = Booking::where('supplier_id', $me->id)->where('created_at', '>=', $since)->count();
        $wasRequests = Booking::where('supplier_id', $me->id)
            ->whereBetween('created_at', [$before, $since])->count();

        $bids = Bid::where('supplier_id', $me->id)->where('created_at', '>=', $since)->count();
        $wasBids = Bid::where('supplier_id', $me->id)->whereBetween('created_at', [$before, $since])->count();

        $won = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->where('updated_at', '>=', $since)->count();
        $wasWon = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereBetween('updated_at', [$before, $since])->count();

        // An average only means something once there is more than one bid.
        $average = Bid::where('supplier_id', $me->id)
            ->where('created_at', '>=', $since)->avg('amount');

        return [
            'requests' => $requests,
            'bids'     => $bids,
            'won'      => $won,
            'earned'   => (float) ($money['earned'] ?? 0),
            'average'  => $average ? (float) $average : null,
            'change'   => [
                'requests' => $change($requests, $wasRequests),
                'bids'     => $change($bids, $wasBids),
                'won'      => $change($won, $wasWon),
            ],
        ];
    }
}
