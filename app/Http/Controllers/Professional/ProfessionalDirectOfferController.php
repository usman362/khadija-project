<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\RequestAttachment;
use App\Models\Review;
use App\Support\Commission;
use App\Support\VerifiedBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Direct Request Details.
 *
 * A client sends a targeted, NON-bidding Direct Request to one professional
 * (an Event with source 'direct_offer', supplier_id = this pro, unpublished).
 * This page shows that request and lets the professional accept it, which
 * creates a confirmed booking, or decline it.
 *
 * Khadijah's 28 October screen calls the response "Send a Quick Bid". There is
 * no bidding on a direct request on this platform and there is not meant to
 * be: a Direct Request goes to one professional and is accepted or declined,
 * which is what keeps it different from a board post. The panel keeps her name
 * and her shape; the buttons do what the platform does.
 *
 * What her screen shows that nothing here records, and so is not invented:
 *   parking, indoor/outdoor, a venue TYPE rather than a venue, a per-service
 *   budget breakdown, and per-service quantities. Each says it is not stated
 *   rather than carrying a figure nobody gave.
 */
class ProfessionalDirectOfferController extends Controller
{
    public function show(Request $request, ?string $id = null): View
    {
        $user = $request->user();

        $query = Event::where('source', 'direct_offer')
            ->where('supplier_id', $user->id)
            ->with(['client:id,name,created_at', 'client.profile', 'categories:id,name,parent_id', 'categories.parent:id,name']);

        /*
         * An open request first, because that is the one needing an answer.
         * Failing that, the most recent of any state: a professional who has
         * just declined something should still be able to read what they
         * declined rather than be told they have nothing.
         */
        $event = is_numeric($id)
            ? (clone $query)->find((int) $id)
            : ((clone $query)->whereIn('status', ['pending', 'confirmed'])->latest()->first()
                ?: (clone $query)->latest()->first());

        /*
         * No invented request.
         *
         * This page used to fall back to a made-up one, complete with a client
         * name, a budget and a deadline, so a professional with nothing
         * waiting was shown an offer that did not exist and could act on it.
         */
        if (! $event) {
            return view('professional.direct-offers.show', ['offer' => null, 'ladder' => []]);
        }

        return view('professional.direct-offers.show', [
            'offer'  => $this->mapEvent($event, $user),
            // Rule R61: no tier gate on who receives a request, so the ladder
            // carries the membership argument instead — this request's own
            // amount at each published commission rate.
            'ladder' => Commission::ladderFor(
                (float) ($event->budget_max ?: $event->budget ?: 0), $user,
            ),
        ]);
    }

    /** Accept a direct offer → confirmed booking (targeted, no bidding). */
    public function accept(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->source === 'direct_offer'
            && $event->supplier_id === $request->user()->id, 403);

        $event->update(['status' => 'confirmed']);

        Booking::firstOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $event->supplier_id],
            [
                'client_id'  => $event->client_id,
                'created_by' => $request->user()->id,
                'status'     => 'confirmed',
                'price'      => $event->budget,
                'currency'   => 'USD',
                'booked_at'  => now(),
                'source'     => 'direct_offer',
                'notes'      => 'Accepted Direct Request (event #' . $event->id . ')',
            ]
        );

        return back()->with('status', 'Offer accepted: a confirmed booking has been created under Bookings.');
    }

    /** Decline a direct offer → cancelled. */
    public function decline(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->source === 'direct_offer'
            && $event->supplier_id === $request->user()->id, 403);

        $event->update(['status' => 'cancelled']);

        return back()->with('status', 'Direct Request declined.');
    }

    /** Build the view payload from a real Direct Request event. */
    private function mapEvent(Event $event, \App\Models\User $pro): array
    {
        $client   = $event->client;
        $services = $event->categories;

        [$status, $statusTone, $statusNote] = match ($event->status) {
            'confirmed' => ['Accepted', 'ok', 'You accepted this request. It is a booking now.'],
            'cancelled' => ['Declined', 'bad', 'You declined this request.'],
            default     => ['Open', 'warn', 'This client has not yet had an answer from you.'],
        };

        /*
         * When an answer is due. The client sets a deadline on the request;
         * without one there is no date to count down to, and inventing one
         * from the event date would put a professional under a clock nobody
         * set.
         */
        $deadline = $event->proposal_deadline;
        $daysLeft = $deadline ? (int) ceil(now()->diffInHours($deadline) / 24) : null;

        // What this client is like to work with, from what is recorded.
        $reviews = $client
            ? Review::where('reviewee_id', $client->id)->where('is_hidden', false)->get()
            : collect();

        $past = $client
            ? Booking::where('client_id', $client->id)->where('status', 'completed')->count()
            : 0;

        return [
            'id'            => 'DR-' . $event->id,
            'event_id'      => $event->id,
            'event'         => $event,
            'status'        => $status,
            'status_tone'   => $statusTone,
            'status_note'   => $statusNote,
            'is_open'       => $event->status === 'pending',

            'title'         => 'Direct Request: ' . ($client?->name ?: 'Client'),
            'subtitle'      => collect([
                $event->organization_type === 'individual' ? 'Private Event' : ucfirst((string) $event->organization_type),
                $event->event_type,
            ])->filter()->implode(" • "),

            'where'         => trim(($event->location ?: $event->city ?: '')
                . ($event->location_zip ? ' (' . $event->location_zip . ')' : '')) ?: null,
            'when'          => $event->starts_at,
            'ends'          => $event->ends_at,
            'guests'        => $event->guest_count,
            'received_at'   => $event->created_at,

            'deadline'      => $deadline,
            'days_left'     => $daysLeft,

            'client' => [
                'name'      => $client?->name ?: 'Client',
                'initials'  => \Illuminate\Support\Str::of($client?->name ?: 'C')->explode(' ')
                    ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
                    ->take(2)->implode(''),
                // OA-165: check-marks are off across the site until a
                // verification feature is defined, so the badge is withheld
                // rather than shown from a column nobody checked.
                'verified'  => VerifiedBadge::shown() && VerifiedBadge::licenceVerified($client?->profile),
                'since'     => $client?->created_at,
                'past'      => $past,
                'rating'    => $reviews->count() ? round($reviews->avg('rating'), 1) : null,
                'reviews'   => $reviews->count(),
            ],

            // One figure for everything, which is what the client gave. The
            // design breaks it down per service; nothing records that.
            'budget_min'    => $event->budget_min ? (float) $event->budget_min : null,
            'budget_max'    => $event->budget_max ? (float) $event->budget_max : ($event->budget ? (float) $event->budget : null),

            'summary'       => $event->description,
            'chips'         => collect([$event->event_type])
                ->merge($services->pluck('name'))
                ->filter()->unique()->values()->all(),

            'services'      => $services->map(fn ($c) => [
                'name'   => $c->name,
                'group'  => $c->parent?->name,
                'detail' => null,  // nothing records a per-service brief
            ])->all(),

            'details' => [
                'Event Type'    => $event->event_type,
                'Event Date'    => $event->starts_at?->format('l, M j, Y'),
                'Event Time'    => $event->timeLabel(),
                'Guest Count'   => $event->guest_count ? 'Approximately ' . number_format($event->guest_count) . ' guests' : null,
                'Location'      => trim(($event->location ?: '') . ' ' . ($event->location_zip ?: '')) ?: null,
                'Venue'         => $event->venue,
                'Format'        => $event->event_format === 'virtual' ? 'Virtual' : null,
            ],

            'attachments'   => RequestAttachment::where('event_id', $event->id)->get(),
            'client_note'   => $event->schedule_note,
        ];
    }

}
