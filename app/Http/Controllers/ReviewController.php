<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Handles visitor-side review CRUD: submitting a review on a completed
 * booking, deleting your own review, and posting the reviewee's response.
 *
 * Authorization rules:
 *  - Only a participant of the booking can leave a review (client OR supplier
 *    — each can review the other).
 *  - Booking must be `completed`. No reviews on open/cancelled work.
 *  - One review per (reviewer, reviewee, booking) triple — enforced by the
 *    unique index, but we also pre-check so we can show a clean error.
 *  - Only the reviewee can post the public response.
 */
class ReviewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'title'      => ['nullable', 'string', 'max:150'],
            'comment'    => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);
        $user    = $request->user();

        // Must be a participant.
        $isClient   = $booking->client_id   === $user->id;
        $isSupplier = $booking->supplier_id === $user->id;
        abort_unless($isClient || $isSupplier, 403, 'You were not part of this booking.');

        // Must be completed.
        if ($booking->status !== 'completed') {
            throw ValidationException::withMessages([
                'booking_id' => 'Reviews can only be left on completed bookings.',
            ]);
        }

        // Reviewee = the other party.
        $revieweeId = $isClient ? $booking->supplier_id : $booking->client_id;

        // Reject duplicate (defence-in-depth; unique index also enforces).
        $existing = Review::where('reviewer_id', $user->id)
            ->where('reviewee_id', $revieweeId)
            ->where('booking_id', $booking->id)
            ->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'booking_id' => 'You have already reviewed this booking.',
            ]);
        }

        Review::create([
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'booking_id'  => $booking->id,
            'rating'      => $data['rating'],
            'title'       => $data['title'] ?? null,
            'comment'     => $data['comment'],
        ]);

        return back()->with('status', 'Review submitted — thanks for your feedback!');
    }

    /** Delete your own review. Reviewees can't delete reviews about them. */
    public function destroy(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->reviewer_id === $request->user()->id, 403);
        $review->delete();

        return back()->with('status', 'Review deleted.');
    }

    /**
     * Reviewee posts a one-time public response under a review.
     * Updating the response later is allowed; clearing it sets both
     * fields back to null.
     */
    public function respond(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->reviewee_id === $request->user()->id, 403);

        $data = $request->validate([
            'response' => ['nullable', 'string', 'max:1500'],
        ]);

        $review->update([
            'response'    => $data['response'] ?? null,
            'response_at' => !empty($data['response']) ? now() : null,
        ]);

        return back()->with('status', 'Response saved.');
    }
}
