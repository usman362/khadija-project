<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Direct Offer Received.
 *
 * A client sends a targeted, NON-bidding Direct Offer to a specific pro
 * (an Event with source 'direct_offer', supplier_id = this pro, unpublished).
 * This page shows the real offer and lets the pro Accept (→ confirmed booking)
 * or Decline (→ cancelled). Falls back to a representative sample only when the
 * pro has no real offers yet, so the layout still demos.
 */
class ProfessionalDirectOfferController extends Controller
{
    public function show(Request $request, ?string $id = null): View
    {
        $query = Event::where('source', 'direct_offer')
            ->where('supplier_id', $request->user()->id)
            ->with(['client:id,name', 'categories:id,name']);

        $event = is_numeric($id)
            ? (clone $query)->find((int) $id)
            : (clone $query)->whereIn('status', ['pending', 'confirmed'])->latest()->first();

        return view('professional.direct-offers.show', [
            'offer' => $event ? $this->mapEvent($event) : $this->sampleOffer($id),
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
                'notes'      => 'Accepted direct offer (event #' . $event->id . ')',
            ]
        );

        return back()->with('status', 'Offer accepted — a confirmed booking has been created under Bookings.');
    }

    /** Decline a direct offer → cancelled. */
    public function decline(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->source === 'direct_offer'
            && $event->supplier_id === $request->user()->id, 403);

        $event->update(['status' => 'cancelled']);

        return back()->with('status', 'Direct offer declined.');
    }

    /** Build the view payload from a real Direct Offer event. */
    private function mapEvent(Event $event): array
    {
        $services = $event->categories->pluck('name')->all();
        $status = match ($event->status) {
            'confirmed' => 'Accepted',
            'cancelled' => 'Declined',
            default     => 'Awaiting Your Response',
        };

        return [
            'id'            => 'DO-' . $event->id,
            'event_id'      => $event->id,
            'status'        => $status,
            'is_open'       => $event->status === 'pending',
            'title'         => $event->title,
            'request_type'  => count($services) >= 2 ? 'MSR' : 'SSR',
            'request_label' => count($services) >= 2 ? 'Multiple Service Request' : 'Single Service Request',
            'received_at'   => $event->created_at?->format('M j, Y \a\t g:i A'),
            'response_deadline' => $event->starts_at?->copy()->subDays(3)->format('M j, Y') ?? '—',
            'days_remaining'    => $event->starts_at ? max(0, (int) round(now()->diffInDays($event->starts_at, false))) : 0,
            'offer_min'     => (int) ($event->budget ?? 0),
            'offer_max'     => (int) ($event->budget ?? 0),
            'budget_note'   => $event->budget ? 'Client budget' : 'Budget not specified',

            'client' => [
                'name'      => $event->client?->name ?? 'Client',
                'verified'  => true,
                'tier'      => 'Client',
                'completed' => 0,
            ],

            'overview' => [
                'Priority / Urgency' => 'Normal',
            ],

            'event' => array_filter([
                'Event Name'  => $event->title,
                'Event Date'  => $event->starts_at?->format('M j, Y'),
                'Guest Count' => $event->guest_count ? $event->guest_count . ' Guests' : null,
                'Venue'       => $event->location,
            ]),

            'services'      => $services ?: ['Service details to follow'],
            'service_notes' => $event->description ?: 'No additional notes provided.',
            'equipment'     => 'As required for the services above.',
            'quantity'      => $event->guest_count ? $event->guest_count . ' guests' : '—',
            'venue_notes'   => [],
            'attachments'   => [],
            'client_note'   => $event->description ?: 'The client is inviting you directly for this event.',

            'planning' => [
                'target_margin'  => '—',
                'staff'          => 'Available',
                'conflicts'      => 'None Detected',
                'subcontractors' => [],
            ],
        ];
    }

    private function sampleOffer(?string $id): array
    {
        return [
            'id'            => $id ?: 'DO-1058',
            'event_id'      => null,
            'status'        => 'Awaiting Your Response',
            'is_open'       => false,
            'title'         => 'Luxury Wedding (Floral & Decor Services)',
            'request_type'  => 'MSR',
            'request_label' => 'Multiple Service Request',
            'received_at'   => 'May 6, 2025 at 10:15 AM',
            'response_deadline' => 'May 10, 2025',
            'days_remaining'    => 4,
            'offer_min'     => 7000,
            'offer_max'     => 8500,
            'budget_note'   => 'Flexible Budget',

            'client' => [
                'name'      => 'Sarah Johnson',
                'verified'  => true,
                'tier'      => 'Premium Member',
                'completed' => 4,
            ],

            'overview' => [
                'Priority / Urgency' => 'Normal',
            ],

            'event' => [
                'Event Name'    => 'Luxury Wedding Reception',
                'Event Type'    => 'Wedding Ceremony & Reception',
                'Event Date'    => 'June 15, 2025',
                'Event Time'    => '5:00 PM – 11:00 PM',
                'Guest Count'   => '150 Guests',
                'Venue'         => 'The Grand Garden Estate, 1234 Garden Lane, Chicago, IL 60601',
            ],

            'services' => [
                'Floral & Decor Services (Primary)',
                'Ceremony Setup & Decor',
                'Reception Decor & Styling',
                'Uplighting (Wireless LED)',
                'Photography',
                'Delivery, setup, breakdown & pickup',
            ],

            'service_notes' => 'We need floral arrangements for 10 tables, ceremony arch, sweetheart table, and entry table.',
            'equipment'     => 'Please provide all decor items, floral supplies, lighting, and installation tools.',
            'quantity'      => 'Medium (100 – 200 Guests)',
            'venue_notes'   => ['Loading dock clearance required (12ft max).'],

            'attachments' => [
                'Inspiration Board.pdf', 'Venue Layout.pdf', 'Timeline & Schedule.pdf',
            ],

            'client_note' => 'We loved your work from Sarah & Michael\'s wedding last year and would love to work with you again!',

            'planning' => [
                'target_margin'   => '30% – 40%',
                'staff'           => 'Available',
                'conflicts'       => 'None Detected',
                'subcontractors'  => [],
            ],
        ];
    }
}
