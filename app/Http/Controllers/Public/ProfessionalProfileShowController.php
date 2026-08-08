<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ResponseStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public, unauthenticated professional profile page — the "store front"
 * that prospective clients land on when browsing pros. Pulls together:
 *
 *   - UserProfile bio / headline / hourly rate / skills
 *   - Verified-badge checklist (trade license, liability insurance, workers' comp)
 *   - Rating histogram + average (magazine-ad "Homeowner Satisfaction Results" style)
 *   - A feed of the most recent visible reviews
 *   - "Similar pros nearby" row for cross-sell / when this pro isn't a fit
 *
 * Route: GET /pro/{user}
 */
class ProfessionalProfileShowController extends Controller
{
    public function show(Request $request, User $user): View
    {
        // Gate: only show profiles for users who actually are suppliers.
        // Keeps random non-pro account IDs from being crawled as "pros".
        abort_unless($user->hasRole(RoleName::PROFESSIONAL->value), 404);

        $this->rememberView($request, $user);

        $profile = $user->getOrCreateProfile();
        $stats   = $user->reviewStats();

        $reviews = Review::visible()
            ->about($user->id)
            ->with(['reviewer:id,name,avatar'])
            ->latest()
            ->limit(10)
            ->get();

        // "Similar pros" — same city if we have one, else just other suppliers.
        // Excludes the current pro and limits to 4 cards for the horizontal row.
        $similar = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->where('users.id', '!=', $user->id)
            ->with('profile')
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->when($profile->city, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('city', $profile->city)))
            ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
            ->orderBy('reviews_count', 'desc')
            ->limit(4)
            ->get();

        $isFullyVerified = $user->isVerified();

        // These two used to be derived from the verification badges: a fully
        // verified pro got a flattering fixed pair of figures and everyone
        // else got a gentler pair, with no message data behind either.
        // Verification says a licence was checked; it says nothing about how
        // fast anyone answers. They are now measured from the messages
        // themselves, by the same code the client portfolio uses.
        //
        // The retired strings are deliberately not quoted here — a test
        // greps this file for them, and a comment naming them would keep it
        // failing forever or force the check to be loosened.
        $response = ResponseStats::for($user);

        $responseSignals = [
            'response_time' => ResponseStats::describe($response['hours']),
            'reply_rate'    => $response['rate'] === null ? '—' : $response['rate'] . '%',
            'member_since'  => $user->created_at?->format('M Y'),
        ];

        return view('public.professional.show', [
            'pro'              => $user,
            'profile'          => $profile,
            'stats'            => $stats,
            'reviews'          => $reviews,
            'similar'          => $similar,
            'responseSignals'  => $responseSignals,
            'isFullyVerified'  => $isFullyVerified,
            'badges'           => UserProfile::BADGES,
        ]);
    }

    /** How many profiles the "Recently Viewed" rail on /browse remembers. */
    public const RECENT_LIMIT = 6;

    /** Session key holding the visitor's recently-viewed professional ids. */
    public const RECENT_KEY = 'recently_viewed_pros';

    /**
     * Push this pro to the front of the visitor's recently-viewed list. The
     * /browse rail used to fill that card with whatever the first three search
     * results happened to be, which was not "recently viewed" by any reading.
     */
    private function rememberView(Request $request, User $user): void
    {
        $seen = collect($request->session()->get(self::RECENT_KEY, []))
            ->reject(fn ($id) => (int) $id === $user->id)
            ->prepend($user->id)
            ->take(self::RECENT_LIMIT)
            ->values()
            ->all();

        $request->session()->put(self::RECENT_KEY, $seen);
    }
}
