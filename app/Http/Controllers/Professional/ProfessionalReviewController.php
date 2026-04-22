<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pro-facing reviews dashboard. Shows:
 *   - Roll-up stats (total, average, histogram)
 *   - Paginated list of reviews about me, newest first
 *   - Ability to respond publicly to each review (route handled by ReviewController)
 */
class ProfessionalReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $agg   = $user->reviewStats(); // ['count', 'average', 'histogram']
        $histo = $agg['histogram'];

        // Legacy keys kept so the existing blade stat cards don't need a refactor.
        $stats = [
            'total'        => $agg['count'],
            'total_reviews'=> $agg['count'],
            'avg_rating'   => $agg['average'],
            'positive'     => ($histo[5] ?? 0) + ($histo[4] ?? 0),
            'negative'     => ($histo[2] ?? 0) + ($histo[1] ?? 0),
            'five_star'    => $histo[5] ?? 0,
            'four_star'    => $histo[4] ?? 0,
            'histogram'    => $histo,
        ];

        $reviews = Review::visible()
            ->about($user->id)
            ->with(['reviewer:id,name,avatar', 'booking:id,event_id', 'booking.event:id,title'])
            ->latest()
            ->paginate(15);

        return view('professional.reviews.index', compact('stats', 'reviews'));
    }
}
