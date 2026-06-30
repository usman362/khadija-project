<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Availability Optimizer (professional). Reads the pro's calendar and
 * suggests how to fill gaps, tighten turnarounds and lift revenue — with an
 * availability score, hours saved, revenue and smart booking suggestions.
 *
 * Representative data pending the live calendar/booking pipeline.
 */
class AiAvailabilityOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.availability-optimizer', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Availability Score', '96%', 'Excellent', 'good'],
                ['Hours Saved', '18', 'This month', ''],
                ['Revenue', '$18,400', 'This month', ''],
                ['Trend Efficiency', '92%', 'Excellent', 'good'],
                ['Open Opportunities', '7', 'Act now', 'warn'],
            ],
            'days' => ['Mon 18', 'Tue 19', 'Wed 20', 'Thu 21', 'Fri 22', 'Sat 23', 'Sun 24'],
            // [day index, type, time, title]
            'events' => [
                [0, 'confirmed', '9:00 AM', 'Corporate AV Setup'],
                [0, 'open', '2:00 PM', 'Open — 2 slots'],
                [1, 'tight', '11:00 AM', 'Tight turnaround'],
                [2, 'confirmed', '5:00 PM', 'Wedding — Chicago'],
                [3, 'open', '10:00 AM', 'Open opportunity'],
                [4, 'confirmed', '6:00 PM', 'Birthday DJ set'],
                [5, 'confirmed', '4:00 PM', 'Wedding reception'],
                [5, 'personal', '12:00 PM', 'Personal time'],
                [6, 'open', '1:00 PM', 'Open — high demand'],
            ],
            'legend' => [
                ['confirmed', 'Confirmed Booking'], ['tight', 'Tight Turnaround'],
                ['open', 'Open Opportunity'], ['personal', 'Personal Time'],
            ],
            'opportunities' => [
                ['Wedding Reception DJ', 'Sat, May 23 · 6 PM', '$1,800', 96],
                ['Corporate Gala AV', 'Thu, May 21 · 10 AM', '$2,400', 91],
                ['Birthday Party', 'Sun, May 24 · 1 PM', '$650', 84],
            ],
            'forecast' => ['total' => '$69,800', 'bars' => [40, 62, 55, 78, 70, 92, 85]],
            'suggestions' => [
                'Fill your Thu 10 AM gap — 3 matching gigs nearby (+$2,400 potential).',
                'Your Sat is back-to-back — add 30 min buffer to avoid a tight turnaround.',
                'Sunday afternoon is high-demand — open it up for +$650 average.',
                'Raise weekend rates 8% — demand is above your booked capacity.',
            ],
        ]);
    }
}
