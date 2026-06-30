<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Timeline Builder (client). Builds an event-day run-of-show across vendor
 * tracks with buffers and conflict detection. Representative data.
 */
class AiTimelineBuilderController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.timeline-builder', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Timeline Health', '96%', 'Excellent', 'good'],
                ['Event Duration', '8h 00m', '5 PM – 1 AM', ''],
                ['Vendors Scheduled', '12', 'All confirmed', ''],
                ['Buffer Time Added', '1h 45m', 'of slack', 'good'],
                ['Conflicts Detected', '2', 'Review needed', 'warn'],
            ],
            'hours' => ['5 PM', '6 PM', '7 PM', '8 PM', '9 PM', '10 PM', '11 PM', '12 AM', '1 AM'],
            // track => [name, colour, [ [label, start%, width%], ... ] ]
            'tracks' => [
                ['Setup', '#64748b', [['Venue Access', 0, 12], ['Vendor Load-in', 9, 16]]],
                ['Ceremony', '#7c3aed', [['Guest Arrival', 18, 11], ['Ceremony', 28, 16]]],
                ['Reception', '#f97316', [['Cocktail Hour', 44, 12], ['Dinner Service', 55, 17], ['Dancing', 71, 25]]],
                ['Vendors', '#16a34a', [['Photographer', 14, 80], ['Catering Crew', 40, 38]]],
                ['Music / DJ', '#2563eb', [['Sound Check', 38, 7], ['Live Set', 45, 51]]],
            ],
            'conflicts' => [
                'Photographer overlaps DJ sound check at 8:00 PM — stagger by 15 min.',
                'Catering breakdown runs into dancing — add a 20 min buffer.',
            ],
        ]);
    }
}
