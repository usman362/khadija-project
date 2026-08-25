<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\UserSubscription;
use App\Support\ClientStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // The figures this page shares with the public Client Portfolio come
        // from ClientStats, so the two screens cannot disagree about the same
        // account — Rule R53's single-source-of-truth requirement, and the
        // defect already found between Earnings and Transactions.
        $public = ClientStats::for($user);

        $stats = [
            'total_events' => $public['total_events'],
            'open_events' => Event::where('client_id', $user->id)->whereIn('status', ['pending', 'published', 'confirmed'])->count(),
            'upcoming_events' => Event::where('client_id', $user->id)->where('starts_at', '>', now())->count(),
            'total_bookings' => Booking::where('client_id', $user->id)->count(),
            'active_bookings' => Booking::where('client_id', $user->id)->whereIn('status', ['requested', 'confirmed'])->count(),
            'completed_bookings' => $public['completed_events'],
        ];

        // Recent events
        $recentEvents = Event::where('client_id', $user->id)
            ->with(['categories:id,name', 'supplier:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Recent bookings
        $recentBookings = Booking::where('client_id', $user->id)
            ->with(['event:id,title', 'supplier:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Rule R60 — one summary line per event that has a guest list, each
        // linking back to that event's own Attendee Management. The widget
        // this replaces was an account-wide table with no event grouping at
        // all, which is Developer Checklist row 223.
        $withGuests = Event::where('client_id', $user->id)
            ->whereHas('attendees')
            ->orderByRaw('starts_at is null, starts_at asc')
            ->limit(5)
            ->get(['id', 'title']);

        $summaries = \App\Models\EventAttendee::summariesFor($withGuests->pluck('id'));

        $attendeeSummaries = $withGuests->map(fn (Event $event) => array_merge(
            ['id' => $event->id, 'title' => $event->title],
            $summaries->get($event->id, ['total' => 0, 'confirmed' => 0, 'cancelled' => 0, 'no_response' => 0]),
        ));

        /*
         * The to-do list, built from things that are actually waiting on this
         * client. It replaces four hardcoded chores ("Find and book a
         * photographer", "Book a venue"…) under tab counts — To Do (4), In
         * Progress (2) — that counted nothing at all. There is no tasks table,
         * so rather than invent one, the list is the work the account really
         * has outstanding, and every row goes somewhere that can clear it.
         */
        $todos = [];

        $draftCount = Event::where('client_id', $user->id)
            ->where('is_published', false)->whereNull('closed_at')->count();
        if ($draftCount) {
            $todos[] = [
                'title' => $draftCount . ' ' . \Illuminate\Support\Str::plural('request', $draftCount) . ' still unpublished',
                'meta'  => 'Professionals cannot see these yet',
                'url'   => route('client.events.index'),
                'level' => 'high',
            ];
        }

        // Proposals sitting on open requests, waiting to be compared.
        $awaitingProposals = \App\Models\Event::where('client_id', $user->id)
            ->whereNull('closed_at')
            ->whereHas('bids', fn ($b) => $b->whereIn('status', ['submitted', 'pending']))
            ->count();
        if ($awaitingProposals) {
            $todos[] = [
                'title' => 'Proposals waiting on ' . $awaitingProposals . ' ' . \Illuminate\Support\Str::plural('request', $awaitingProposals),
                'meta'  => 'Compare and choose a professional',
                'url'   => route('client.proposals.index'),
                'level' => 'high',
            ];
        }

        // Agreements the professional has signed and the client has not.
        $toSign = \App\Models\Agreement::whereHas('booking', fn ($q) => $q->where('client_id', $user->id))
            ->whereNull('client_accepted_at')
            ->whereIn('status', ['pending_review', 'supplier_accepted'])
            ->count();
        if ($toSign) {
            $todos[] = [
                'title' => $toSign . ' ' . \Illuminate\Support\Str::plural('agreement', $toSign) . ' awaiting your signature',
                'meta'  => 'The professional has already signed',
                'url'   => route('client.bookings.index'),
                'level' => 'high',
            ];
        }

        // Finished work this client has not reviewed.
        $reviewed = \App\Models\Review::where('reviewer_id', $user->id)->pluck('booking_id');
        $toReview = Booking::where('client_id', $user->id)
            ->where('status', 'completed')
            ->whereNotIn('id', $reviewed)
            ->count();
        if ($toReview) {
            $todos[] = [
                'title' => 'Review ' . $toReview . ' completed ' . \Illuminate\Support\Str::plural('booking', $toReview),
                'meta'  => 'Your rating builds their reputation',
                'url'   => route('client.reviews.index'),
                'level' => 'low',
            ];
        }

        // Active subscription
        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return view('client.dashboard', compact(
            'stats', 'recentEvents', 'recentBookings', 'subscription', 'attendeeSummaries', 'todos'
        ));
    }
}
