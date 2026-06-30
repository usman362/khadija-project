<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Direct Offer Received.
 *
 * A client sends a direct offer/request (SSR / MSR / ESR) to a specific
 * professional. This page lets the pro review the full request and respond
 * (create proposal, offer pricing, ask questions, invite team, or decline).
 *
 * NOTE: the direct-offer data model (SSR/MSR/ESR) is not built yet, so the
 * offer below is a representative payload. It is intentionally a single
 * structured array so it can be swapped for a real `DirectOffer` model later
 * without touching the view.
 */
class ProfessionalDirectOfferController extends Controller
{
    public function show(Request $request, ?string $id = null): View
    {
        return view('professional.direct-offers.show', [
            'offer' => $this->sampleOffer($id),
        ]);
    }

    private function sampleOffer(?string $id): array
    {
        return [
            'id'            => $id ?: 'DO-1058',
            'status'        => 'Awaiting Your Response',
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
                'Team Collaboration' => 'Yes, I allow a team',
                'Max Additional Pros' => 'Up to 3',
            ],

            'event' => [
                'Event Name'    => 'Luxury Wedding Reception',
                'Event Type'    => 'Wedding Ceremony & Reception',
                'Event Date'    => 'June 15, 2025',
                'Event Time'    => '5:00 PM – 11:00 PM',
                'Setup Time'    => '8:00 AM',
                'Breakdown Time' => '11:30 PM',
                'Guest Count'   => '150 Guests',
                'Venue'         => 'The Grand Garden Estate, 1234 Garden Lane, Chicago, IL 60601',
                'Venue Details' => 'Indoor Venue · Ballroom, Garden, Terrace',
                'Load-in / Access' => 'Loading dock, Elevator access, Time restrictions',
                'Parking'       => 'Complimentary valet for vendors',
            ],

            'services' => [
                'Floral & Decor Services (Primary)',
                'Ceremony Setup & Decor',
                'Reception Decor & Styling',
                'Chair Rentals (Chiavari – Gold)',
                'Backdrop Frame Rental',
                'Uplighting (Wireless LED)',
                'Photography',
                'Videography',
                'Delivery, setup, breakdown & pickup',
            ],

            'service_notes' => 'We need floral arrangements for 10 tables, ceremony arch, sweetheart table, and entry table. Uplighting for the entire ballroom in blush color.',
            'equipment'     => 'Please provide all decor items, floral supplies, lighting, and installation tools.',
            'quantity'      => 'Medium (100 – 200 Guests)',
            'venue_notes'   => ['Loading dock clearance required (12ft max).', 'Low power limits on North Terrace.'],

            'attachments' => [
                'Inspiration Board.pdf', 'Venue Layout.pdf', 'Guest List.xlsx', 'Timeline & Schedule.pdf',
            ],

            'client_note' => 'We loved your work from Sarah & Michael\'s wedding last year and would love to work with you again! Looking forward to creating a beautiful and unforgettable experience.',

            'planning' => [
                'target_margin'   => '30% – 40%',
                'staff'           => 'Available',
                'conflicts'       => 'None Detected',
                'subcontractors'  => [
                    ['Chicago Wedding', 'Photography'],
                    ['Dream Event Rentals', 'Furniture & Decor'],
                    ['Luxe Lighting Co.', 'Lighting Specialist'],
                ],
            ],
        ];
    }
}
