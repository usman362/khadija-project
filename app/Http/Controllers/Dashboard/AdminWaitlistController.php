<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Support\ServiceArea;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Where people are signing up from that we cannot serve yet.
 *
 * Every out-of-area registration is a demand signal — that is the whole reason
 * Peter's rule lets them register at all (2026-07-30). The signal was being
 * recorded and never read: the status column and the "notify me" checkbox both
 * existed, with nowhere to look at either. This is that page.
 *
 * Deliberately read-only. Deciding to open a state is not a button.
 */
class AdminWaitlistController extends Controller
{
    public function index(Request $request): View
    {
        $state = $request->query('state');

        $base = UserProfile::query()
            ->where('service_area_status', ServiceArea::COMING_SOON);

        // One row per state: how many are waiting, how many asked to be told,
        // and when the most recent person arrived. Ordered by size, because
        // the question this page answers is "where do we open next".
        $byState = (clone $base)
            ->selectRaw('country, state, COUNT(*) as people')
            ->selectRaw('SUM(CASE WHEN expansion_opt_in = 1 THEN 1 ELSE 0 END) as notify')
            ->selectRaw('MAX(user_profiles.created_at) as latest')
            ->groupBy('country', 'state')
            ->orderByDesc('people')
            ->get()
            ->map(fn ($row) => [
                'country' => $row->country,
                'state'   => $row->state,
                'label'   => $this->placeLabel($row->country, $row->state),
                'people'  => (int) $row->people,
                'notify'  => (int) $row->notify,
                'latest'  => $row->latest,
            ]);

        // The people themselves, so a state with eleven signups can be looked
        // at rather than just counted.
        $people = (clone $base)
            ->when($state, fn ($q) => $q->where('state', $state))
            ->with('user:id,name,email,created_at')
            ->latest('user_profiles.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.admin.waitlist.index', [
            'byState'     => $byState,
            'people'      => $people,
            'state'       => $state,
            'totalWaiting' => $byState->sum('people'),
            'totalNotify'  => $byState->sum('notify'),
            'openStates'   => array_keys(config('geo.allowed_states', [])),
        ]);
    }

    /** "Ohio" · "Ontario, Canada" · "France" — whatever we actually know. */
    private function placeLabel(?string $country, ?string $state): string
    {
        $code = ServiceArea::countryCode($country);

        if ($state) {
            $name = $code === 'US'
                ? (config('geo.us_states', [])[$state] ?? $state)
                : $state;

            return $code === 'US' || $code === null
                ? $name
                : $name . ', ' . (config('geo.countries', [])[$code] ?? $code);
        }

        return $code
            ? (config('geo.countries', [])[$code] ?? $code)
            : 'Not given';
    }
}
