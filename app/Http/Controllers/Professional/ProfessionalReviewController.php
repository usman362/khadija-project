<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pro-facing "Reviews, Ratings & Reputation" hub.
 *
 * Centres on the professional GIVING feedback to clients after an event:
 *   - $pendingReview = a completed booking whose client this pro hasn't
 *     reviewed yet (the "Event Completed → rate the client" flow). REAL.
 *   - store() creates a real Review (reviewer = pro, reviewee = client).
 *   - $stats = this pro's own reputation (reviews received) for the score card.
 *
 * The 3-area breakdown (punctuality / communication / safety) is averaged
 * into the single Review.rating and detailed in the note (no sub-rating
 * columns yet). The "Echo Effect", Re-Shape, Vanish and Peer Mediate are
 * marketplace concepts not yet modelled — illustrative UI only.
 */
class ProfessionalReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $agg   = $user->reviewStats(); // ['count', 'average', 'histogram']
        $histo = $agg['histogram'];

        $stats = [
            'total'         => $agg['count'],
            'total_reviews' => $agg['count'],
            'avg_rating'    => $agg['average'],
            'positive'      => ($histo[5] ?? 0) + ($histo[4] ?? 0),
            'negative'      => ($histo[2] ?? 0) + ($histo[1] ?? 0),
            'five_star'     => $histo[5] ?? 0,
            'four_star'     => $histo[4] ?? 0,
            'histogram'     => $histo,
        ];

        // Reviews this pro has ALREADY given (so we can exclude them).
        $reviewedBookingIds = Review::where('reviewer_id', $user->id)->pluck('booking_id')->filter()->all();

        // Pending: latest completed booking whose client hasn't been reviewed.
        $pendingReview = Booking::where('supplier_id', $user->id)
            ->where('status', 'completed')
            ->when($reviewedBookingIds, fn ($q) => $q->whereNotIn('id', $reviewedBookingIds))
            ->with(['event:id,title,starts_at,ends_at,location', 'client:id,name,avatar'])
            ->latest()
            ->first();

        $givenCount = count($reviewedBookingIds);

        // Reviews about me (kept available for the reputation context).
        $reviews = Review::visible()
            ->about($user->id)
            ->with(['reviewer:id,name,avatar', 'booking:id,event_id', 'booking.event:id,title'])
            ->latest()
            ->take(5)
            ->get();

        return view('professional.reviews.index', compact('stats', 'pendingReview', 'givenCount', 'reviews'));
    }

    /**
     * Submit feedback about a client for a completed booking.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'booking_id'    => ['required', 'integer', 'exists:bookings,id'],
            'punctuality'   => ['required', 'integer', 'between:1,5'],
            'communication' => ['required', 'integer', 'between:1,5'],
            'safety'        => ['required', 'integer', 'between:1,5'],
            'note'          => ['nullable', 'string', 'max:300'],
        ]);

        $booking = Booking::where('id', $data['booking_id'])
            ->where('supplier_id', $user->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // Guard against duplicate reviews for the same booking by this pro.
        $already = Review::where('reviewer_id', $user->id)->where('booking_id', $booking->id)->exists();
        if ($already) {
            return back()->with('status', 'You have already reviewed this client.');
        }

        $overall = (int) round(($data['punctuality'] + $data['communication'] + $data['safety']) / 3);

        $detail = "Punctuality: {$data['punctuality']}/5 · Communication: {$data['communication']}/5 · Safety & Hospitality: {$data['safety']}/5";
        $comment = $data['note'] ? $detail . "\n\n" . $data['note'] : $detail;

        Review::create([
            'reviewer_id' => $user->id,
            'reviewee_id' => $booking->client_id,
            'booking_id'  => $booking->id,
            'rating'      => max(1, $overall),
            'comment'     => $comment,
        ]);

        return back()->with('status', 'Feedback posted — thanks for helping build a trusted community!');
    }
}
