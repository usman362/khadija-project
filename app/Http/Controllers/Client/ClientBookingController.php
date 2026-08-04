<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Notifications\ProposalAccepted;
use App\Notifications\ProposalCancelled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientBookingController extends Controller
{
    /** Tab key => [label, description]. The tabs are the only status filter. */
    public const TABS = [
        'all'         => 'All',
        'upcoming'    => 'Upcoming',
        'in_progress' => 'In Progress',
        'pending'     => 'Pending',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $counts = [
            'all'         => (clone $this->base($user))->count(),
            'upcoming'    => (clone $this->base($user))->tap($this->scope('upcoming'))->count(),
            'in_progress' => (clone $this->base($user))->tap($this->scope('in_progress'))->count(),
            'pending'     => (clone $this->base($user))->tap($this->scope('pending'))->count(),
            'completed'   => (clone $this->base($user))->tap($this->scope('completed'))->count(),
            'cancelled'   => (clone $this->base($user))->tap($this->scope('cancelled'))->count(),
        ];

        $tab = $request->string('tab')->toString();
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'all';
        }

        $query = $this->base($user)
            ->with([
                'event:id,title,starts_at,ends_at,location,venue,guest_count,event_type,description',
                'event.categories:id,name',
                'supplier:id,name,email',
                'supplier.profile:id,user_id,city,state,trade_license_verified_at,liability_insurance_verified_at,liability_insurance_expires_on',
                // The contract itself. There is a real agreements table behind
                // this, with a PDF service and a both-parties-accepted gate on
                // the download — unlike the four invented PDF rows it replaces.
                'latestAgreement',
            ])
            ->latest();

        $query->tap($this->scope($tab));

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($w) use ($q) {
                $w->whereHas('event', fn ($e) => $e->where('title', 'like', "%{$q}%"))
                  ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            });
        }

        $bookings = $query->paginate(10)->withQueryString();

        $ids = $bookings->pluck('id');

        // The review this client already left, keyed by booking — the card shows
        // the stars given rather than offering to leave a second one.
        $myReviews = Review::where('reviewer_id', $user->id)
            ->whereIn('booking_id', $ids)
            ->get()
            ->keyBy('booking_id');

        // Status history, straight out of the agreement log. This is what the
        // old five-step "milestone timeline" pretended to be: it drew Contract
        // Signed / Deposit Paid / Checked-in / Inspection / Funds Released with
        // dates derived from created_at, none of which was ever recorded.
        $history = AgreementLog::where('subject_type', 'booking')
            ->whereIn('subject_id', $ids)
            ->orderBy('created_at')
            ->get()
            ->groupBy('subject_id');

        // Deposits are Payments tagged `booking_deposit`, carrying the event and
        // supplier they were taken for — that pair is what identifies a booking.
        // Anything not matched simply has no deposit shown; nothing is guessed.
        $deposits = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->get()
            ->filter(fn ($p) => ($p->metadata['kind'] ?? null) === 'booking_deposit')
            ->keyBy(fn ($p) => ($p->metadata['event_id'] ?? '') . ':' . ($p->metadata['supplier_id'] ?? ''));

        $agreedTotal = (float) $this->base($user)->whereNotIn('status', ['cancelled'])->sum('price');
        $depositsPaid = (float) $deposits->sum('amount');

        $financial = [
            'agreed_total'  => $agreedTotal,
            'deposits_paid' => $depositsPaid,
            'outstanding'   => max(0, $agreedTotal - $depositsPaid),
        ];

        // Real rows, not a synthesised schedule: confirmed bookings whose event
        // has a start date still ahead of us.
        $nextEvents = $this->base($user)
            ->where('status', 'confirmed')
            ->whereHas('event', fn ($q) => $q->whereNotNull('starts_at')->where('starts_at', '>=', now()))
            ->with('event:id,title,starts_at')
            ->get()
            ->sortBy(fn ($b) => $b->event?->starts_at)
            ->take(4)
            ->values();

        return view('client.bookings.index', [
            'tabs'               => self::TABS,
            'tab'                => $tab,
            'counts'             => $counts,
            'bookings'           => $bookings,
            'myReviews'          => $myReviews,
            'history'            => $history,
            'deposits'           => $deposits,
            'financial'          => $financial,
            'nextEvents'         => $nextEvents,
        ]);
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

        // State-machine guard: clients may only confirm or cancel a request,
        // and may only cancel (not complete) a confirmed booking. Skipping
        // the intermediate `confirmed` state is an integrity bug — reject.
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

            // Notify the supplier. Client-driven transitions here are:
            //   • requested → confirmed  → ProposalAccepted (the gig is on!)
            //   • any       → cancelled  → ProposalCancelled
            if ($next === 'confirmed' && $booking->supplier) {
                $booking->supplier->notify(new ProposalAccepted($booking));
            } elseif ($next === 'cancelled' && $booking->supplier) {
                $booking->supplier->notify(new ProposalCancelled($booking, $user));
            }
        }

        return back()->with('status', 'Booking status updated.');
    }

    // ── internals ───────────────────────────────────────────────────

    private function base($user)
    {
        return Booking::where('client_id', $user->id);
    }

    /**
     * One definition of each tab, used for both the counts and the list so the
     * number on a tab always matches what opening it shows.
     */
    private function scope(string $tab): \Closure
    {
        return function ($query) use ($tab) {
            match ($tab) {
                'upcoming' => $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '>', now())),
                'in_progress' => $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now())),
                'pending'   => $query->where('status', 'requested'),
                'completed' => $query->where('status', 'completed'),
                'cancelled' => $query->where('status', 'cancelled'),
                default     => $query,
            };
        };
    }
}
