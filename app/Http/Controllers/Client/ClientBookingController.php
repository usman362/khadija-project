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
        'all'                 => 'All',
        'upcoming'            => 'Upcoming',
        'in_progress'         => 'In Progress',
        'awaiting_completion' => 'Awaiting Completion',
        'pending'             => 'Pending',
        'completed'           => 'Completed',
        'cancelled'           => 'Cancelled',
    ];

    /** The buckets that partition every booking. `all` is their sum. */
    public const STATUS_BUCKETS = ['upcoming', 'in_progress', 'awaiting_completion', 'pending', 'completed', 'cancelled'];

    public function index(Request $request): View
    {
        $user = $request->user();

        // Every bucket counted the same way, and `all` is their SUM rather than
        // a separate query — so a booking that fits no bucket is impossible by
        // construction, not merely unlikely.
        $counts = [];
        foreach (self::STATUS_BUCKETS as $bucket) {
            $counts[$bucket] = (clone $this->base($user))->tap($this->scope($bucket))->count();
        }
        $counts['all'] = array_sum($counts);

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
     * The event is still ahead of us. Held apart from scope() so the tab and
     * the catch-all below are built from the same predicate — a second copy is
     * how the gap this fixes got in.
     */
    private function startsLater(): \Closure
    {
        return fn ($q) => $q->where('starts_at', '>', now());
    }

    /**
     * The event is running right now. An event with no end time counts as
     * running for the rest of its day rather than dropping out the instant it
     * starts.
     */
    private function runningNow(): \Closure
    {
        return fn ($q) => $q
            ->where('starts_at', '<=', now())
            ->where(fn ($w) => $w
                ->where('ends_at', '>=', now())
                ->orWhere(fn ($n) => $n->whereNull('ends_at')->where('starts_at', '>=', now()->subDay())));
    }

    /**
     * One definition of each tab, used for both the counts and the list so the
     * number on a tab always matches what opening it shows.
     *
     * Checklist row 92. Bookings have four statuses — requested, confirmed,
     * completed, cancelled — but the tiles sliced `confirmed` into Upcoming
     * and In Progress by event date and offered nowhere else for it to go. A
     * booking still badged CONFIRMED whose event had already finished fell
     * through every bucket: the page read "4 All Bookings" over tiles summing
     * to 2, and the two missing ones were exactly the confirmed pair.
     *
     * `awaiting_completion` is that missing bucket, defined as the negation of
     * the two date tabs rather than as a date test of its own, so the six
     * buckets partition the set by construction. It also carries the meaning
     * worth surfacing: the event has happened and the professional has not
     * marked it complete yet.
     */
    private function scope(string $tab): \Closure
    {
        return function ($query) use ($tab) {
            match ($tab) {
                'upcoming' => $query->where('status', 'confirmed')
                    ->whereHas('event', $this->startsLater()),
                'in_progress' => $query->where('status', 'confirmed')
                    ->whereHas('event', $this->runningNow()),
                'awaiting_completion' => $query->where('status', 'confirmed')
                    ->whereDoesntHave('event', $this->startsLater())
                    ->whereDoesntHave('event', $this->runningNow()),
                'pending'   => $query->where('status', 'requested'),
                'completed' => $query->where('status', 'completed'),
                'cancelled' => $query->where('status', 'cancelled'),
                default     => $query,
            };
        };
    }
}
