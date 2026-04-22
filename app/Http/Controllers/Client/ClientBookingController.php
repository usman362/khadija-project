<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Stats
        $stats = [
            'all' => Booking::where('client_id', $user->id)->count(),
            'upcoming' => Booking::where('client_id', $user->id)->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '>', now()))->count(),
            'in_progress' => Booking::where('client_id', $user->id)->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))->count(),
            'pending' => Booking::where('client_id', $user->id)->where('status', 'requested')->count(),
            'completed' => Booking::where('client_id', $user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::where('client_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        // Build query with filters
        $query = Booking::where('client_id', $user->id)
            ->with(['event:id,title,starts_at,ends_at', 'event.categories:id,name', 'supplier:id,name'])
            ->latest();

        // Status tab filter
        $tab = $request->string('tab')->toString() ?: 'all';
        switch ($tab) {
            case 'upcoming':
                $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '>', now()));
                break;
            case 'in_progress':
                $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()));
                break;
            case 'pending':
                $query->where('status', 'requested');
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->paginate(12)->withQueryString();

        // Which bookings on this page has the client already reviewed? Keeps
        // the "Leave a Review" CTA from appearing twice on the same card.
        $reviewedBookingIds = Review::where('reviewer_id', $user->id)
            ->whereIn('booking_id', $bookings->pluck('id'))
            ->pluck('booking_id')
            ->all();

        return view('client.bookings.index', compact('stats', 'bookings', 'tab', 'reviewedBookingIds'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['required', 'in:requested,confirmed,cancelled,completed'],
        ]);

        $previousStatus = $booking->status;
        $booking->update(['status' => $validated['status']]);

        if ($previousStatus !== $validated['status']) {
            AgreementLog::create([
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
                'from_status' => $previousStatus,
                'to_status' => $validated['status'],
                'changed_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', 'Booking status updated.');
    }
}
