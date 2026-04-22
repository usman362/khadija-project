<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Event;
use App\Notifications\ProposalCancelled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::query()->with(['event:id,title', 'client:id,name', 'supplier:id,name'])->latest();

        if (! $request->user()->isAdmin()) {
            if ($request->user()->hasRole(RoleName::CLIENT->value)) {
                $query->where('client_id', $request->user()->id);
            } else {
                $query->where('supplier_id', $request->user()->id);
            }
        }

        if ($request->filled('source') && in_array($request->string('source')->toString(), ['user', 'ai', 'system'], true)) {
            $query->where('source', $request->string('source')->toString());
        }

        return view('dashboard.bookings.index', [
            'bookings' => $query->paginate(12)->withQueryString(),
            'events' => Event::query()->where('is_published', true)->latest()->get(['id', 'title']),
            'selectedSource' => $request->string('source')->toString() ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);

        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $event = Event::query()->findOrFail($validated['event_id']);

        if (! $event->is_published) {
            return back()->withErrors(['event_id' => 'Only published events can be booked.']);
        }

        $booking = Booking::query()->create([
            'event_id' => $event->id,
            'client_id' => $request->user()->id,
            'supplier_id' => $event->supplier_id,
            'created_by' => $request->user()->id,
            'status' => 'requested',
            'notes' => $validated['notes'] ?? null,
            'booked_at' => now(),
            'source' => 'user',
        ]);

        AgreementLog::query()->create([
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
            'from_status' => null,
            'to_status' => 'requested',
            'changed_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Booking created successfully.');
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['required', 'in:requested,confirmed,cancelled,completed'],
        ]);

        $user     = $request->user();
        $previous = $booking->status;
        $next     = $validated['status'];

        // Admin may bypass the graph for non-admin transitions via forceCancel
        // below; this endpoint still respects the state-machine to keep the
        // audit trail clean (no silent `requested`→`completed` skips).
        if ($previous !== $next && ! $booking->canActorTransition($user, $next)) {
            return back()->withErrors([
                'status' => "Invalid transition: can't move booking from {$previous} to {$next}.",
            ]);
        }

        $booking->update(['status' => $next]);

        if ($previous !== $next) {
            AgreementLog::query()->create([
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
                'from_status' => $previous,
                'to_status' => $next,
                'changed_by' => $user->id,
            ]);
        }

        return back()->with('status', 'Booking status updated.');
    }

    /**
     * Admin-only moderation: force-cancel a booking regardless of its current
     * status (terminal bookings included — useful to void a completed booking
     * that turned out fraudulent). Notifies both participants.
     */
    public function forceCancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        if ($booking->status === 'cancelled') {
            return back()->with('status', 'Booking is already cancelled.');
        }

        $previous = $booking->status;
        $booking->update(['status' => 'cancelled']);

        AgreementLog::query()->create([
            'subject_type' => 'booking',
            'subject_id'   => $booking->id,
            'from_status'  => $previous,
            'to_status'    => 'cancelled',
            'changed_by'   => $user->id,
        ]);

        // Let both sides know an admin intervened.
        $booking->loadMissing(['client', 'supplier']);
        if ($booking->client)   $booking->client->notify(new ProposalCancelled($booking, $user));
        if ($booking->supplier) $booking->supplier->notify(new ProposalCancelled($booking, $user));

        return back()->with('status', 'Booking force-cancelled.');
    }
}
