<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Guest Capacity Planner (client). Models guest flow, comfort and legal
 * capacity with a heatmap and what-if adjustments. Representative data.
 */
class AiGuestCapacityController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.guest-capacity', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Expected Guests', '185', ''],
                ['Recommended Capacity', '220', 'good'],
                ['Guest Comfort Score', '94%', 'good'],
                ['Legal Capacity', '280', ''],
            ],
            // table dots on the layout [x%, y%]
            'tables' => [[18,28],[34,28],[50,28],[66,28],[26,48],[42,48],[58,48],[74,48],[20,68],[36,68],[52,68],[68,68]],
            'insights' => [
                ['Seating', 'Excellent', 'good'], ['Walking Space', 'Good', 'good'],
                ['Buffet Line', 'Tight', 'warn'], ['Restrooms', 'Adequate', 'good'],
                ['Parking', 'Good', 'good'], ['Exit Access', 'Excellent', 'good'],
            ],
            'capacity' => ['expected' => 185, 'comfort' => 220, 'legal' => 280],
            'tips' => [
                'Add a second buffet line to clear the dinner rush 9 min faster.',
                'At 185 guests you have comfortable spacing — room for +35 before it feels tight.',
                'Keep the east exit clear — it carries 40% of egress flow.',
            ],
        ]);
    }
}
