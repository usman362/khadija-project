<?php

namespace App\Http\Controllers\Cancellations;

use App\Domain\Cancellations\CancellationPolicy;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CancellationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checklist row 155 — both directions of a cancellation.
 *
 * One controller, because the two forms are one record seen from two ends and
 * because the rule that matters most is shared: neither side's form decides
 * anything about money on its own. The client's form quotes the policy's own
 * arithmetic; the professional's form quotes nothing at all, because the
 * policy puts professional-side money out of scope with no spec written.
 */
class CancellationController extends Controller
{
    private function layout(User $user): string
    {
        return $user->isProfessionalMode() ? 'layouts.professional' : 'layouts.client';
    }

    private function role(User $user, Booking $booking): string
    {
        return $user->id === $booking->supplier_id ? 'professional' : 'client';
    }

    private function authorizeParty(User $user, Booking $booking): void
    {
        abort_unless(in_array($user->id, [$booking->client_id, $booking->supplier_id], true), 403);
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        /*
         * Mine is: one against a booking I am a party to, one against my own
         * event, or one I raised.
         *
         * This asked only the first question, so an event cancellation — which
         * has no booking by definition — never appeared in the list of the
         * person who asked for it.
         */
        $requests = CancellationRequest::query()
            ->where(function ($q) use ($user) {
                $q->whereHas('booking', fn ($b) => $b
                        ->where('client_id', $user->id)
                        ->orWhere('supplier_id', $user->id))
                    ->orWhereHas('event', fn ($e) => $e->where('client_id', $user->id))
                    ->orWhere('raised_by', $user->id);
            })
            ->with(['booking.event', 'event', 'raiser'])
            ->latest('id')
            ->paginate(15);

        return view('cancellations.index', [
            'layout'   => $this->layout($user),
            'requests' => $requests,
            // The two sides do different things here — a client cancels their
            // own booking, a professional reports what the client did — and
            // the button used to say "Report something" to both.
            'role'     => $user->isProfessionalMode() ? 'professional' : 'client',
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $role = $user->isProfessionalMode() ? 'professional' : 'client';

        $bookings = Booking::query()
            ->where($role === 'professional' ? 'supplier_id' : 'client_id', $user->id)
            ->whereIn('status', ['requested', 'confirmed'])
            ->with(['event', 'client', 'supplier', 'latestFinalization'])
            ->latest('id')
            ->get();

        // The client's form shows what they would get back. The professional's
        // does not, because there is nothing to show — see the class note.
        $quotes = $role === 'client'
            ? $bookings->mapWithKeys(fn ($b) => [$b->id => CancellationPolicy::quote($b)])
            : collect();

        /*
         * Events the client can ask to cancel.
         *
         * A request posted to the board and not yet taken up has no booking to
         * point at, so it could not be cancelled at all — the only way out was
         * to leave it open. Only the client's own, and only ones that have not
         * already finished or been cancelled.
         */
        $events = $role === 'client'
            ? \App\Models\Event::where('client_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->whereDoesntHave('cancellationRequests', fn ($q) => $q->pending())
                ->orderByDesc('id')
                ->get(['id', 'title', 'starts_at', 'status'])
            : collect();

        return view('cancellations.create', [
            'layout'   => $this->layout($user),
            'role'     => $role,
            'bookings' => $bookings,
            'events'   => $events,
            'quotes'   => $quotes,
            'kinds'    => $role === 'client'
                            ? array_intersect_key(CancellationRequest::KINDS, array_flip(CancellationRequest::CLIENT_KINDS))
                            : array_diff_key(CancellationRequest::KINDS, array_flip(CancellationRequest::CLIENT_KINDS)),
            'tiers'    => CancellationPolicy::TIERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        /*
         * Cancelling an EVENT is its own path: there is no booking, no
         * professional on the other side and no money quote — there is a
         * posted request the client wants taken down, and an administrator
         * who decides. Handled first so the booking rules below can go on
         * assuming a booking.
         */
        if ($request->input('kind') === CancellationRequest::CLIENT_CANCELS_EVENT) {
            return $this->storeEventCancellation($request);
        }

        $data = $request->validate([
            'booking_id'     => ['required', 'integer', 'exists:bookings,id'],
            'kind'           => ['required', 'in:' . implode(',', array_keys(CancellationRequest::KINDS))],
            'reason'         => ['required', 'string', 'min:15', 'max:3000'],
            'detail'         => ['nullable', 'string', 'max:5000'],
            'occurred_at'    => ['nullable', 'date'],
            'waited_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'certified'      => ['accepted'],
        ]);

        $booking = Booking::with(['event', 'latestFinalization'])->findOrFail($data['booking_id']);

        $this->authorizeParty($user, $booking);

        $role = $this->role($user, $booking);

        // Only the client cancels their own booking; only the professional
        // reports what the client did. Letting either side file the other's
        // form would put a cancellation on the record under the wrong name.
        $isClientKind = in_array($data['kind'], CancellationRequest::CLIENT_KINDS, true);

        abort_if($isClientKind && $role !== 'client', 403);
        abort_if(! $isClientKind && $role !== 'professional', 403);

        abort_unless(CancellationPolicy::cancellable($booking), 403,
            'This booking has already finished. Open a dispute instead.');

        $certification = $isClientKind
            ? 'I understand the deposit is not refundable and that the refund shown is calculated on the remaining balance only.'
            : 'I certify that this account of what happened is true and accurate to the best of my knowledge.';

        $attributes = [
            'booking_id'         => $booking->id,
            'event_id'           => $booking->event_id,
            'raised_by'          => $user->id,
            'raised_role'        => $role,
            'kind'               => $data['kind'],
            'reason'             => $data['reason'],
            'detail'             => $data['detail'] ?? null,
            'occurred_at'        => $data['occurred_at'] ?? null,
            'waited_minutes'     => $data['waited_minutes'] ?? null,
            'certified'          => true,
            'certification_text' => $certification,
        ];

        // Snapshot the quote at the moment of the request. Recomputing it on
        // display would let a later date change rewrite the figure the client
        // was actually shown — which is the exact number they would dispute.
        if ($isClientKind) {
            $quote = CancellationPolicy::quote($booking);

            $attributes += [
                'quoted_agreed'  => $quote['agreed'],
                'quoted_deposit' => $quote['deposit'],
                'quoted_balance' => $quote['balance'],
                'quoted_refund'  => $quote['refund'],
                'quoted_tier'    => $quote['tier'],
                'days_before'    => $quote['days_before'],
            ];
        }

        $cancellation = CancellationRequest::create($attributes);

        return redirect()
            ->route('cancellations.show', $cancellation)
            ->with('status', "Recorded as {$cancellation->reference}. Our team will follow up.");
    }

    /**
     * "I need to cancel this event" — recorded, and waiting on an approval.
     *
     * Nothing is cancelled here. The event stays exactly as it is until an
     * administrator approves, because a request that took itself down the
     * moment it was asked for would not be an approval at all.
     */
    private function storeEventCancellation(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'event_id'  => ['required', 'integer', 'exists:events,id'],
            'kind'      => ['required', 'in:' . CancellationRequest::CLIENT_CANCELS_EVENT],
            'reason'    => ['required', 'string', 'min:15', 'max:3000'],
            'detail'    => ['nullable', 'string', 'max:5000'],
            'certified' => ['accepted'],
        ], [
            'event_id.required' => 'Choose which event you want to cancel.',
            'reason.min'        => 'Tell us why in a sentence or two: an administrator reads this.',
        ]);

        $event = \App\Models\Event::findOrFail($data['event_id']);

        // Their own event, and only the client cancels one.
        abort_unless($event->client_id === $user->id, 403);
        abort_if($user->isProfessionalMode(), 403);

        abort_if(
            in_array($event->status, ['cancelled', 'completed'], true),
            403,
            'This event has already finished or been cancelled.',
        );

        // One open request at a time, so an administrator is never looking at
        // two asks for the same event.
        abort_if(
            $event->cancellationRequests()->pending()->exists(),
            403,
            'A cancellation for this event is already waiting for approval.',
        );

        $cancellation = CancellationRequest::create([
            'booking_id'         => null,
            'event_id'           => $event->id,
            'raised_by'          => $user->id,
            'raised_role'        => 'client',
            'kind'               => CancellationRequest::CLIENT_CANCELS_EVENT,
            'reason'             => $data['reason'],
            'detail'             => $data['detail'] ?? null,
            'certified'          => true,
            'certification_text' => 'I understand this event stays live until an administrator approves the cancellation, and that any professional already working on it will be told.',
        ]);

        return redirect()
            ->route('cancellations.show', $cancellation)
            ->with('status', "Recorded as {$cancellation->reference}. Your event stays live until an administrator approves this.");
    }

    public function show(Request $request, CancellationRequest $cancellation): View
    {
        $user = $request->user();

        /*
         * An event cancellation has no booking, and this asked the booking who
         * the parties were — so opening the one you had just raised was a 500.
         * Whoever raised it, and whoever owns the event, can see it.
         */
        if ($cancellation->booking) {
            $this->authorizeParty($user, $cancellation->booking);
        } else {
            abort_unless(
                $cancellation->raised_by === $user->id
                    || $cancellation->event?->client_id === $user->id,
                403,
            );
        }

        $cancellation->load(['booking.event', 'booking.client', 'booking.supplier', 'event', 'raiser']);

        return view('cancellations.show', [
            'layout'  => $this->layout($user),
            'request' => $cancellation,
        ]);
    }

    /** Only the person who raised it may take it back, and only before it is actioned. */
    public function withdraw(Request $request, CancellationRequest $cancellation): RedirectResponse
    {
        abort_unless($cancellation->raised_by === $request->user()->id, 403);
        abort_unless($cancellation->isPending(), 403);

        $cancellation->update(['status' => CancellationRequest::WITHDRAWN]);

        return back()->with('status', 'Withdrawn.');
    }
}
