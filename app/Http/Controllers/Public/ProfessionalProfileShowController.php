<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
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
 *
 * Route: GET /pro/{user}
 */
class ProfessionalProfileShowController extends Controller
{
    public function show(Request $request, User $user): View
    {
        // Gate: only show profiles for users who actually are suppliers.
        // Keeps random non-pro account IDs from being crawled as "pros".
        abort_unless($user->hasRole(RoleName::SUPPLIER->value), 404);

        $profile = $user->getOrCreateProfile();
        $stats   = $user->reviewStats();

        $reviews = Review::visible()
            ->about($user->id)
            ->with(['reviewer:id,name,avatar'])
            ->latest()
            ->limit(10)
            ->get();

        return view('public.professional.show', [
            'pro'      => $user,
            'profile'  => $profile,
            'stats'    => $stats,
            'reviews'  => $reviews,
            'badges'   => \App\Models\UserProfile::BADGES, // ['trade_license' => 'Trade License', …]
        ]);
    }
}
