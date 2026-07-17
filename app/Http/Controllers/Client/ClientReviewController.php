<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client-side Reviews. Surfaces the feedback the client has written about
 * the professionals they hired, plus aggregate reputation stats and a
 * list of bookings still awaiting a review.
 *
 * Route: GET /client/reviews
 */
class ClientReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Reviews authored BY this client (reviewer_id).
        $base = Review::where('reviewer_id', $user->id);

        $all      = (clone $base)->count();
        $avg      = round((float) (clone $base)->avg('rating'), 1);
        $positive = (clone $base)->where('rating', '>=', 4)->count();
        $negative = (clone $base)->where('rating', '<=', 2)->count();

        // Rating-distribution buckets (5★ → 1★) for the filter chips + the
        // right-rail reputation breakdown.
        $dist = [];
        for ($star = 5; $star >= 1; $star--) {
            $dist[$star] = (clone $base)->where('rating', $star)->count();
        }

        $stats = [
            'total'    => $all,
            'avg'      => $avg ?: 0,
            'positive' => $positive,
            'negative' => $negative,
            'positive_pct' => $all > 0 ? round(($positive / $all) * 100, 1) : 0,
            'negative_pct' => $all > 0 ? round(($negative / $all) * 100, 1) : 0,
            'dist'     => $dist,
        ];

        // Filters: star rating + search.
        $star = (int) $request->query('star', 0);
        $query = (clone $base)
            ->with(['reviewee:id,name,avatar', 'reviewee.profile:id,user_id,headline', 'booking.event:id,title,starts_at'])
            ->latest();

        if ($star >= 1 && $star <= 5) {
            $query->where('rating', $star);
        }
        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('comment', 'like', "%{$s}%")
                ->orWhereHas('reviewee', fn ($rq) => $rq->where('name', 'like', "%{$s}%")));
        }

        $reviews = $query->paginate(8)->withQueryString();

        // Bookings completed but not yet reviewed → "Pending Review Requests".
        $reviewedBookingIds = (clone $base)->pluck('booking_id')->filter()->all();
        $pendingReviews = Booking::where('client_id', $user->id)
            ->where('status', 'completed')
            ->when(count($reviewedBookingIds), fn ($q) => $q->whereNotIn('id', $reviewedBookingIds))
            ->with(['event:id,title,starts_at', 'supplier:id,name,avatar'])
            ->latest()
            ->take(4)
            ->get();

        return view('client.reviews.index', compact('stats', 'reviews', 'star', 'pendingReviews'));
    }

    /**
     * Leave a review for the professional on a completed booking. One review
     * per client per booking (enforced by the unique triple + firstOrCreate).
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless(
            $booking->client_id === $request->user()->id && $booking->status === 'completed',
            Response::HTTP_FORBIDDEN
        );

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:2000'],
            'title'   => ['nullable', 'string', 'max:120'],
        ]);

        Review::firstOrCreate(
            [
                'reviewer_id' => $request->user()->id,
                'reviewee_id' => $booking->supplier_id,
                'booking_id'  => $booking->id,
            ],
            [
                'rating'  => $data['rating'],
                'title'   => $data['title'] ?? null,
                'comment' => $data['comment'],
            ]
        );

        return back()->with('status', 'Thanks — your review has been posted.');
    }
}
