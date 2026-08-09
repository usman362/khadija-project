<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\Availability;
use App\Support\ProfessionalNumbers;
use App\Support\ResponseStats;
use App\Support\ServiceArea;
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

            // ── The target-mockup sections (checklist rows 165–236) ──

            // Rows 210 and 167. Both draw on portfolioImageItems(), which is
            // already featured-first and already copes with the two shapes
            // the column holds. Row 167 asked for a selection mechanism and
            // told us not to invent one: featured-first, then the order the
            // professional saved, is professional-curated and is what every
            // other surface on the site already shows.
            'coverPhotos'      => $profile->portfolioHeroUrls(4),
            'highlights'       => $profile->portfolioHeroUrls(4),
            'galleryPhotos'    => $profile->portfolioHeroUrls(60),
            'portfolioCount'   => $profile->portfolioImageItems()->count(),

            'packages'         => $this->packages($user),
            'availability'     => [
                'windows' => Availability::windows($user),
                'busy'    => Availability::busyDates($user),
            ],
            'numbers'          => ProfessionalNumbers::for($user),
            'yearsAreStated'   => ProfessionalNumbers::yearsAreStated($user),
            'serviceArea'      => $this->serviceArea($profile),
            'acceptedOn'       => $this->acceptedOn(),
            'howItWorks'       => self::HOW_IT_WORKS,
        ]);
    }

    /**
     * Checklist row 234 — the six-step booking explainer.
     *
     * Static and platform-wide, not per-professional, exactly as the row
     * asks: "confirm it's shared, not individually configured, so it can't
     * drift out of sync with the platform's real booking flow." A professional
     * who could edit these steps could describe a process GigResource does not
     * run.
     */
    public const HOW_IT_WORKS = [
        ['Request',   'Tell them what you need and when.'],
        ['Quote',     'They reply with a price and what it covers.'],
        ['Agreement', 'You both sign, and the deposit is taken.'],
        ['Planning',  'Messages, files and the timeline live on GigResource.'],
        ['Event day', 'They deliver the work you agreed.'],
        ['Complete',  'You confirm, the balance is released, and you can leave a review.'],
    ];

    /**
     * Checklist row 166 — this professional's own packages (R51).
     *
     * The data source is the Package Builder, confirmed rather than assumed:
     * these are the same rows the professional manages under
     * /professional/packages, filtered to the publicly browsable state.
     *
     * The target mockup tags one card "Most Popular". That tag is NOT built,
     * and the row is why: it says the rule has to be defined and must not
     * default to list order. Nothing on the platform records which package a
     * booking came from — `bookings` has no package_id — so "most booked"
     * cannot be counted today. Either a booking learns which package it came
     * from, or the Owner says the professional picks. Flagged, not guessed.
     */
    private function packages(User $user)
    {
        return Package::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();
    }

    /** Checklist row 235 — where this professional works. */
    private function serviceArea(UserProfile $profile): array
    {
        $state = $profile->state;

        return [
            'headline' => ServiceArea::describe($profile->city, $state, $profile->country),
            'state'    => $state,
            'state_name' => $state ? (config('geo.us_states', [])[$state] ?? $state) : null,

            // R38 — a professional works with clients in their own state, so
            // the coverage line is the state, not a radius. Saying "within 50
            // miles" would describe a marketplace this one is not.
            'note'     => $state
                ? 'Takes bookings from clients in this state.'
                : 'This professional has not set a service area yet.',
        ];
    }

    /**
     * Checklist row 165 — what this professional accepts on GigResource.
     *
     * Three of the four lines in the target. The fourth, "Live Event
     * Upgrades", is NOT here and must not be added: it belongs to Rule R41's
     * reserved stub, pulled 2026-08-03 on the Owner's explicit instruction
     * that nothing from that discussion returns to the product or the
     * checklists "unless and until the Owner gives final approval". Row 165
     * and Open Decisions row 37 both say hold it. The other three carry no
     * conflict.
     */
    private function acceptedOn(): array
    {
        return [
            ['Direct offers',      'Yes',      'A client can hire them directly, without posting to the board.'],
            ['Emergency requests', 'Yes',      'They can be reached for short-notice work.'],
            ['Payment protection', 'Included', 'Money is held by the payment processor until the work is confirmed.'],
        ];
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
