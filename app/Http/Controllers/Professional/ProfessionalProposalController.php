<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Support\GigStats;
use App\Models\Conversation;
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
        // Same source as the Gig Operations Hub, so a professional cannot see
        // one number here and another there for the same set of gigs.
        $counted = GigStats::forProfessional($user);

        /*
         * Archived proposals are out of every count except their own tab.
         *
         * That makes All here smaller than the Gig Operations Hub's total,
         * and it should be: archiving is this list's own tidying, and a row a
         * professional has put away should not keep being counted at them.
         */
        $all      = Booking::where('supplier_id', $user->id)->whereNull('archived_at');
        $replied0 = \App\Models\Message::whereIn('booking_id', (clone $all)->pluck('id'))
            ->where('sender_id', '!=', $user->id)
            ->pluck('booking_id')
            ->unique();

        $stats = [
            'all'         => (clone $all)->count(),
            // The client has come back and it is this professional's move.
            'mine'        => (clone $all)->where('status', 'requested')->whereIn('id', $replied0)->count(),
            // Sent, and nobody has said anything back yet.
            'theirs'      => (clone $all)->where('status', 'requested')
                ->whereNotIn('id', $replied0->isEmpty() ? [0] : $replied0)->count(),
            'accepted'    => (clone $all)->whereIn('status', ['confirmed', 'in_progress', 'completed'])->count(),
            'completed'   => (clone $all)->where('status', 'completed')->count(),
            'declined'    => (clone $all)->where('status', 'cancelled')->count(),
            'withdrawn'   => (clone $all)->whereIn('event_id',
                \App\Models\Bid::where('supplier_id', $user->id)->where('status', 'withdrawn')->pluck('event_id')
            )->count(),
            'archived'    => Booking::where('supplier_id', $user->id)->whereNotNull('archived_at')->count(),
            'expired'     => (clone $all)->where('status', 'requested')
                ->whereHas('event', fn ($q) => $q
                    ->whereNotNull('proposal_deadline')
                    ->where('proposal_deadline', '<', now()))
                ->count(),
            // Kept for anything still reading the old names.
            'pending'     => $counted['pending'],
            'in_progress' => $counted['inProgress'],
            'cancelled'   => $counted['cancelled'],
        ];

        // Build query with filters
        $query = Booking::where('supplier_id', $user->id)
            // proposal_deadline and guest_count are read by the table; a column
            // left out of the select comes back null, and the page then says
            // "no closing date" about a request that has one.
            ->with(['event:id,title,starts_at,ends_at,proposal_deadline,guest_count,event_type,location',
                    'event.categories:id,name', 'supplier:id,name', 'client:id,name'])
            ->latest();

        /*
         * Khadijah's tabs, which group by what is actually owed rather than
         * by the raw status: a proposal the client has replied to needs this
         * professional, and one nobody has answered needs the client.
         */
        $replied = \App\Models\Message::whereIn(
                'booking_id',
                Booking::where('supplier_id', $user->id)->pluck('id')
            )
            ->where('sender_id', '!=', $user->id)
            ->pluck('booking_id')
            ->unique();

        $tab = $request->string('tab')->toString() ?: 'all';

        $this->narrow($request, $query, $replied, $user, $tab);

        // The footer offers a page size, so the page has to honour it.
        $perPage = (int) $request->integer('per', 6);
        $perPage = in_array($perPage, [6, 12, 24, 48], true) ? $perPage : 6;

        $bookings = $query->paginate($perPage)->withQueryString();

        return view('professional.proposals.index', array_merge(
            compact('stats', 'bookings', 'tab'),
            [
                'detail'  => $this->detailFor($user, $bookings->getCollection()),
                'replied' => $replied0,
                'search'  => (string) $request->query('search', ''),
                'types'   => Booking::where('supplier_id', $user->id)
                    ->with('event:id,event_type')
                    ->get()
                    ->map(fn (Booking $b) => $b->event?->event_type)
                    ->filter()->unique()->sort()->values()->all(),
                'type'    => (string) $request->query('type', ''),
                'period'  => (string) $request->query('period', ''),
                'perPage' => $perPage,
                // How many filters are narrowing the list, so a professional
                // can see at a glance that they are not looking at all of it.
                // It used to be the number on a "More Filters" button that
                // opened nothing.
                'applied' => collect(['type', 'period'])
                    ->filter(fn ($k) => $request->filled($k))->count(),
            ],
        ));
    }

    /**
     * What opening a row shows, for the rows on this page only.
     *
     * Khadijah's 28 October screen expands a proposal into the client's last
     * message, the services and prices behind the figure, when it closes, and
     * a warning when the dates do not add up.
     *
     * Estimated margin is on her screen and is deliberately NOT here: nothing
     * on the platform records what a job costs this professional to deliver,
     * so a margin would be a number with nothing behind it on the screen
     * somebody uses to decide whether to take the work.
     *
     * @param  \Illuminate\Support\Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function detailFor(User $user, $bookings): array
    {
        if ($bookings->isEmpty()) {
            return [];
        }

        $eventIds = $bookings->pluck('event_id')->filter()->unique()->all();

        // The bid this professional placed on each of those requests, where
        // there is one. It carries the breakdown the client was shown.
        $bids = \App\Models\Bid::where('supplier_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->get()
            ->keyBy('event_id');

        // The last thing the client said, per conversation.
        $messages = \App\Models\Message::whereIn('booking_id', $bookings->pluck('id'))
            ->where('sender_id', '!=', $user->id)
            ->latest('created_at')
            ->get()
            ->unique('booking_id')
            ->keyBy('booking_id');

        $detail = [];

        foreach ($bookings as $booking) {
            $bid   = $bids->get($booking->event_id);
            $event = $booking->event;

            /*
             * A proposal sent after the event had already happened.
             *
             * It sounds impossible and it is not: a request can sit unanswered
             * past its own date, and a professional reading the list has no
             * way to tell except by comparing two dates themselves.
             */
            $late = $event?->starts_at
                && $booking->created_at
                && $event->starts_at->lt($booking->created_at);

            /*
             * What the client has actually done, which is the honest version
             * of her "Client Activity" column. Nothing records whether a
             * proposal was opened, so the two things that are known are used:
             * whether they have written back, and whether they settled it.
             */
            $activity = match (true) {
                $booking->status === 'cancelled'                         => ['Declined', '#ef4444'],
                in_array($booking->status, ['confirmed', 'in_progress', 'completed'], true)
                                                                         => ['Accepted', '#10b981'],
                $messages->has($booking->id)                             => ['Replied', '#2563eb'],
                default                                                  => ['No reply yet', '#64748b'],
            };

            /*
             * Services and what each was quoted at. The bid breakdown is the
             * only place a per-service figure is actually written down; where
             * there is none, the services are still named but no money is
             * invented against them.
             */
            $lines = [];

            if (is_array($bid?->breakdown)) {
                foreach ($bid->breakdown as $label => $amount) {
                    $lines[] = [
                        'label'  => is_string($label) ? $label : ($amount['label'] ?? 'Item'),
                        'amount' => is_array($amount) ? ($amount['amount'] ?? null) : $amount,
                    ];
                }
            }

            $services = $event?->categories?->pluck('name')->filter()->values()->all() ?? [];

            if ($lines === []) {
                $lines = array_map(fn ($name) => ['label' => $name, 'amount' => null], $services);
            }

            /*
             * How this proposal came about. Her screen tags every row, and the
             * three cases are all recorded: an emergency request, a bid this
             * professional placed on the board, or a request sent straight to
             * them.
             */
            $source = match (true) {
                $event?->source === 'esr'         => 'Emergency Request',
                $event?->source === 'virtual_hub' => 'Virtual Event',
                $bid !== null                     => 'Bid Response',
                default                           => 'Direct Request',
            };

            /*
             * Versions. Nothing keeps a history of past proposals, but a bid
             * edited after it was first written down is a revision, and the
             * two timestamps say so. So one revision is version 2, and the
             * history panel lists only entries with a real date behind them.
             */
            $revised = $bid && $bid->created_at && $bid->updated_at
                && $bid->updated_at->gt($bid->created_at->copy()->addMinute());

            $history = [];

            if ($bid) {
                if ($revised) {
                    $history[] = ['v' => 2, 'at' => $bid->updated_at, 'amount' => $bid->amount,
                                  'note' => 'Revised proposal', 'current' => true];
                }

                $history[] = ['v' => 1, 'at' => $bid->submitted_at ?? $bid->created_at,
                              'amount' => $revised ? null : $bid->amount,
                              'note' => 'Original proposal', 'current' => ! $revised];
            } elseif ($booking->created_at) {
                $history[] = ['v' => 1, 'at' => $booking->created_at, 'amount' => $booking->price,
                              'note' => 'Original proposal', 'current' => true];
            }

            $closes = $event?->proposal_deadline;
            $past   = $event?->starts_at && $event->starts_at->isPast();

            /*
             * The one thing to do next, in the words of the person who has to
             * do it. Red where the window has gone, amber where it is about
             * to, blue where there is simply a move to make.
             */
            $next = match (true) {
                $booking->status === 'cancelled' => ['View decline reason', 'Client declined', 'red'],
                $booking->status === 'completed' => ['Ask for a review', 'The work is finished.', 'blue'],
                in_array($booking->status, ['confirmed', 'in_progress'], true)
                    => ['Open the Gig Hub', 'Booked. Agree the paperwork and the day.', 'blue'],
                $past
                    => ['Event already occurred, consider withdrawing.', null, 'red'],
                $closes && $closes->isPast()
                    => ['The closing date has passed.', 'Nothing more will happen to this one.', 'red'],
                $closes && $closes->lte(now()->addDay())
                    => ['Follow up, this proposal expires tomorrow.', null, 'amber'],
                $messages->has($booking->id)
                    => ['Review the client reply and send a revision.', null, 'blue'],
                default
                    => ['Send a follow-up', 'No reply since you submitted it.', 'blue'],
            };

            $detail[$booking->id] = [
                'activity'   => $activity,
                // What became of it, in the words the list uses.
                'last_what'  => match (true) {
                    $booking->status === 'cancelled' => 'Client declined',
                    $booking->status === 'completed' => 'Work finished',
                    in_array($booking->status, ['confirmed', 'in_progress'], true) => 'Client accepted',
                    $messages->has($booking->id)     => 'Client replied',
                    default                          => 'Proposal submitted',
                },
                // The platform's own cut, which is the one deduction that is
                // actually known. Not a margin: nothing records what the job
                // costs this professional to deliver.
                'net'        => $booking->price
                    ? \App\Support\Commission::netOf((float) $booking->price, $user)
                    : null,
                'rate'       => \App\Support\Commission::rateFor($user),
                'services'   => $services,
                'lines'      => $lines,
                'source'     => $source,
                'version'    => $revised ? 2 : 1,
                'history'    => $history,
                'next'       => $next[0],
                'next_note'  => $next[1],
                'next_tone'  => $next[2],
                'past'       => $past,
                'late'      => $late,
                'event_at'  => $event?->starts_at,
                'sent_at'   => $booking->created_at,
                'closes_at' => $closes,
                'message'   => $messages->get($booking->id),
                'breakdown' => is_array($bid?->breakdown) ? $bid->breakdown : [],
                'bid_amount' => $bid?->amount,
            ];
        }

        return $detail;
    }

    /**
     * Put proposals away, or take them back out.
     *
     * Archiving tidies this professional's own list and changes nothing the
     * client can see. It is not a delete and not a withdrawal.
     */
    public function archive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'  => ['required', 'string', 'max:2000'],
            'undo' => ['nullable', 'boolean'],
        ]);

        $ids = collect(explode(',', $data['ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter();

        $changed = Booking::where('supplier_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->update(['archived_at' => $request->boolean('undo') ? null : now()]);

        return back()->with('status', $request->boolean('undo')
            ? $changed . ' ' . \Illuminate\Support\Str::plural('proposal', $changed) . ' put back.'
            : $changed . ' ' . \Illuminate\Support\Str::plural('proposal', $changed) . ' archived.');
    }

    /**
     * Take a live offer off the table.
     *
     * Only one the client has not answered: once they have accepted or
     * declined, withdrawing is not this professional's to do.
     */
    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'string', 'max:2000']]);

        $ids = collect(explode(',', $data['ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter();

        $open = Booking::where('supplier_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->where('status', 'requested')
            ->get();

        foreach ($open as $booking) {
            $booking->update(['status' => 'cancelled']);

            \App\Models\Bid::where('supplier_id', $request->user()->id)
                ->where('event_id', $booking->event_id)
                ->whereIn('status', ['submitted', 'pending'])
                ->update(['status' => 'withdrawn']);
        }

        return back()->with('status', $open->count() === 0
            ? 'Nothing to withdraw: those have already been answered.'
            : $open->count() . ' ' . \Illuminate\Support\Str::plural('proposal', $open->count()) . ' withdrawn.');
    }

    /** The proposals as they are filtered on screen, as a file. */
    /**
     * Everything that narrows the list: the tab, and Khadijah's four
     * dropdowns, and the search box.
     *
     * It lives here because Export has to give you the list you are looking
     * at. Ali, 13 November: it gave you every proposal on the account
     * instead, whatever the screen said, and the comment above the button
     * claimed otherwise. Asking in one place is what makes the claim true.
     *
     * @param  \Illuminate\Support\Collection  $replied  Booking ids the client has written on.
     */
    private function narrow(
        Request $request,
        \Illuminate\Database\Eloquent\Builder $query,
        \Illuminate\Support\Collection $replied,
        \App\Models\User $user,
        string $tab,
    ): void {
        $withdrawnEvents = \App\Models\Bid::where('supplier_id', $user->id)
            ->where('status', 'withdrawn')
            ->pluck('event_id');

        match ($tab) {
            'mine'      => $query->where('status', 'requested')->whereIn('id', $replied),
            'theirs'    => $query->where('status', 'requested')->whereNotIn('id', $replied->isEmpty() ? [0] : $replied),
            'accepted'  => $query->whereIn('status', ['confirmed', 'in_progress', 'completed']),
            // Finished, which Accepted counts as well. It is asked for on
            // its own because the dropdown that used to ask for it is gone.
            'completed' => $query->where('status', 'completed'),
            'declined'  => $query->where('status', 'cancelled'),
            // Still open on this side, but the client stopped taking
            // proposals, so nothing more is going to happen to it.
            'expired'   => $query->where('status', 'requested')
                ->whereHas('event', fn ($q) => $q
                    ->whereNotNull('proposal_deadline')
                    ->where('proposal_deadline', '<', now())),
            // Taken off the table by this professional, which is a different
            // thing from the client declining it.
            'withdrawn' => $query->whereIn('event_id', $withdrawnEvents->isEmpty() ? [0] : $withdrawnEvents),
            default     => null,
        };

        // Archived rows are out of the list but never out of the record, so
        // they are hidden everywhere except the tab that asks for them.
        if ($tab !== 'archived') {
            $query->whereNull('archived_at');
        } else {
            $query->whereNotNull('archived_at');
        }

        /*
         * What is left of Khadijah's four dropdowns: the event type and how
         * far back to look.
         *
         * The other two, proposal status and client activity, said nothing
         * the tabs above them do not already say, in two more vocabularies
         * for the same question. Removed 13 November, and the one thing only
         * they offered, finished work, is a tab of its own now.
         */
        // "All Time" is the default, so a period only ever narrows the list.
        $days = match ($request->string('period')->toString()) {
            '7'   => 7,
            '30'  => 30,
            '90'  => 90,
            '365' => 365,
            default => null,
        };

        if ($days) {
            $query->where('bookings.created_at', '>=', now()->subDays($days));
        }

        // Search
        if ($request->filled('type')) {
            $wanted = $request->string('type')->toString();
            $query->whereHas('event', fn ($e) => $e->where('event_type', $wanted));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                // Event name, client, or location: the three the box offers.
                $q->whereHas('event', fn ($eq) => $eq
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%"));
                $q->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        $query = Booking::where('supplier_id', $user->id)
            ->with(['event:id,title,starts_at,proposal_deadline', 'client:id,name'])
            ->latest();

        $replied = \App\Models\Message::whereIn(
                'booking_id',
                Booking::where('supplier_id', $user->id)->pluck('id')
            )
            ->where('sender_id', '!=', $user->id)
            ->pluck('booking_id')
            ->unique();

        $this->narrow($request, $query, $replied, $user, $request->string('tab')->toString() ?: 'all');

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows, $user) {
            $out = fopen('php://output', 'wb');

            fputcsv($out, ['Event', 'Client', 'Status', 'Your proposal',
                           'Yours after fees', 'Event date', 'Closes', 'Sent']);

            foreach ($rows as $b) {
                fputcsv($out, [
                    $b->event?->title,
                    $b->client?->name,
                    $b->status,
                    $b->price,
                    $b->price ? \App\Support\Commission::netOf((float) $b->price, $user) : null,
                    $b->event?->starts_at?->format('Y-m-d'),
                    $b->event?->proposal_deadline?->format('Y-m-d'),
                    $b->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, 'proposals-' . \App\Support\DisplayTimezone::today($user)->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
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

        // Thirty responses a day — Khadijah's sheet, 29 Aug. A proposal is a
        // response to a client's request, so it counts against the same
        // allowance as a bid. The duplicate check above already stops a second
        // proposal on one event, so this only ever sees new ones.
        \App\Support\UserLimit::hit('pro-responses', $user, null, 'notes');

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

    /**
     * Open the conversation for a proposal so the professional can negotiate
     * instead of only accepting or declining it.
     *
     * The universal rule across the request routes is Accept / Decline /
     * Reply — "back and forth, unlimited, until deal or no deal". This page
     * had the first two only, so a client's direct offer was take-it-or-
     * leave-it. Reuses the existing booking conversation rather than a second
     * messaging surface; finds or creates it exactly like ConversationController.
     */
    public function reply(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);

        $user = $request->user();

        $conversation = Conversation::ofType('booking')
            ->where('booking_id', $booking->id)
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'type'       => 'booking',
                'booking_id' => $booking->id,
                'event_id'   => $booking->event_id,
                'created_by' => $user->id,
            ]);
        }

        // Both sides must be in the thread, however it was created.
        $conversation->addParticipant($user);
        if ($booking->client) {
            $conversation->addParticipant($booking->client);
        }

        return redirect()
            ->route('professional.chat.show', $conversation)
            ->with('status', 'You can negotiate this proposal here: the client is in the thread.');
    }
}
