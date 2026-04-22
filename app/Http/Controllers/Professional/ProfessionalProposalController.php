<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Notifications\BookingCompleted;
use App\Notifications\ProposalCancelled;
use App\Notifications\ProposalReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Stats
        $stats = [
            'all' => Booking::where('supplier_id', $user->id)->count(),
            'pending' => Booking::where('supplier_id', $user->id)->where('status', 'requested')->count(),
            'accepted' => Booking::where('supplier_id', $user->id)->where('status', 'confirmed')->count(),
            'in_progress' => Booking::where('supplier_id', $user->id)->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))->count(),
            'completed' => Booking::where('supplier_id', $user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::where('supplier_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        // Build query with filters
        $query = Booking::where('supplier_id', $user->id)
            ->with(['event:id,title,starts_at,ends_at', 'event.categories:id,name', 'supplier:id,name', 'client:id,name'])
            ->latest();

        // Status tab filter
        $tab = $request->string('tab')->toString() ?: 'all';
        switch ($tab) {
            case 'pending':
                $query->where('status', 'requested');
                break;
            case 'accepted':
                $query->where('status', 'confirmed');
                break;
            case 'in_progress':
                $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()));
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
                $q->whereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$search}%"));
                $q->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->paginate(12)->withQueryString();

        return view('professional.proposals.index', compact('stats', 'bookings', 'tab'));
    }

    /**
     * Professional sends a proposal (booking request) to a published event.
     */
    public function sendProposal(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        // Validate event is published and open for proposals
        if ($event->status !== 'published') {
            return back()->with('error', 'This event is no longer accepting proposals.');
        }

        // Prevent duplicate proposals
        $existingProposal = Booking::where('event_id', $event->id)
            ->where('supplier_id', $user->id)
            ->whereIn('status', ['requested', 'confirmed'])
            ->first();

        if ($existingProposal) {
            return back()->with('error', 'You have already submitted a proposal for this event.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Create a booking (proposal) from professional side
        $booking = Booking::create([
            'event_id' => $event->id,
            'client_id' => $event->client_id,
            'supplier_id' => $user->id,
            'created_by' => $user->id,
            'status' => 'requested',
            'notes' => $validated['notes'] ?? null,
            'booked_at' => now(),
            'source' => 'professional_proposal',
        ]);

        // Log the proposal
        AgreementLog::create([
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
            'from_status' => null,
            'to_status' => 'requested',
            'changed_by' => $user->id,
        ]);

        // Notify the client that a new proposal landed on their event.
        if ($client = User::find($event->client_id)) {
            $client->notify(new ProposalReceived($booking));
        }

        return redirect()
            ->route('professional.proposals.index')
            ->with('status', 'Proposal sent successfully! The client will review your request.');
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

        // State-machine guard: a supplier may cancel a request/confirmed job
        // and mark a confirmed job `completed`, but never accept their own
        // proposal — that's a client-only transition.
        if ($previous !== $next && ! $booking->canActorTransition($user, $next)) {
            return back()->withErrors([
                'status' => "Invalid transition: you can't move this booking from {$previous} to {$next}.",
            ]);
        }

        $booking->update(['status' => $next]);

        if ($previous !== $next) {
            AgreementLog::create([
                'subject_type' => 'booking',
                'subject_id'   => $booking->id,
                'from_status'  => $previous,
                'to_status'    => $next,
                'changed_by'   => $user->id,
            ]);

            // Notify the affected party. Supplier-driven transitions here are:
            //   • confirmed → completed  → notify client (review prompt)
            //   • any       → cancelled  → notify client (the other side)
            if ($next === 'completed' && $booking->client) {
                $booking->client->notify(new BookingCompleted($booking));
            } elseif ($next === 'cancelled' && $booking->client) {
                $booking->client->notify(new ProposalCancelled($booking, $user));
            }
        }

        return back()->with('status', 'Booking status updated.');
    }
}
