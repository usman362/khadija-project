<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Support\ClientStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client Portfolio — Rule R53, the client's missing third tier.
 *
 * Both personas were meant to have three surfaces: Dashboard (private),
 * Profile & Settings (private) and Portfolio (public). The professional had
 * all three; the client had two. This is the third, and it mirrors the
 * professional's /pro/{id} rather than inventing a second pattern.
 *
 * Route: GET /client-portfolio/{user}
 *
 * On the URL: the spec leaves the pattern open and warns against anything a
 * reader could confuse with the private /client/profile settings page. Neither
 * /client/{id} nor /profile/{id} clears that bar, so the path says what the
 * page is. Flagged for Peter as a recommendation, not a decision.
 *
 * Four of the twelve sections are hidden behind config/client-portfolio.php
 * because their open questions are about what may be SHOWN, not about how to
 * build them — they are finished, not stubbed, and each is one flag away.
 */
class ClientPortfolioController extends Controller
{
    public function show(Request $request, User $user): View
    {
        // Only clients have a client portfolio. Without this, any account id
        // renders as one — including professionals, who have their own page.
        abort_unless($user->hasRole(RoleName::CLIENT->value), 404);

        $sections = config('client-portfolio.sections');

        return view('public.client.portfolio', [
            'client'   => $user,
            'profile'  => $user->getOrCreateProfile(),

            // One call. Sections 2 and 7 draw the same four figures, and the
            // Client Dashboard draws them again — all three read this array,
            // so they cannot disagree.
            'stats'    => ClientStats::for($user),

            'eventTypes' => ClientStats::eventTypeCounts($user),
            'reviews'    => $this->reviews($user),
            'sections'   => $sections,

            'eventHistory' => $sections['event_history']
                ? $this->eventHistory($user)
                : collect(),

            'favourites' => $sections['favourite_professionals']
                ? $user->savedProfessionals()->with('profile')->limit(4)->get()
                : collect(),
        ]);
    }

    /**
     * Section 8 — what professionals said about this client.
     *
     * The spec asks whether the "Verified Booking" tag is rules-based or an
     * unverifiable claim. It is stronger than rules-based: `reviews.booking_id`
     * is NOT NULL, so a review without a booking behind it cannot be written
     * in the first place. The tag is a property of the schema, and no filter
     * here is doing the work — a `whereNotNull` would only look like it was.
     */
    private function reviews(User $client)
    {
        return Review::visible()
            ->about($client->id)
            ->with(['reviewer:id,name,avatar'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Section 5 — past events, at whichever detail level is configured.
     *
     * `generalised` is the default and the recommendation: a client's private
     * events are visible here to every professional on the platform, not only
     * the ones who worked them, and an event name plus a venue plus a date
     * locates a private party.
     */
    private function eventHistory(User $client)
    {
        $full = config('client-portfolio.event_history_detail') === 'full';

        return Event::where('client_id', $client->id)
            ->whereHas('bookings', fn ($b) => $b->where('status', 'completed'))
            ->with('categories:id,name')
            ->latest('starts_at')
            ->limit(20)
            ->get()
            ->map(fn (Event $event) => [
                'title'    => $full ? $event->title : ($event->categories->first()->name ?? 'Event'),
                'when'     => $event->starts_at?->format($full ? 'M j, Y' : 'M Y'),
                'where'    => $full ? $event->location : ($event->state ?: null),
                'services' => $event->categories->pluck('name')->all(),
            ]);
    }
}
