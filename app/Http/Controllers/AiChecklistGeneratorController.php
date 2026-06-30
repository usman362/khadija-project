<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Checklist Generator (client). A generated planning command-center —
 * prioritised tasks, budget summary, vendor status and AI recommendations.
 * Representative data.
 */
class AiChecklistGeneratorController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.checklist-generator', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Event Health', '93%', 'good'], ['Days to Event', '184', ''],
                ['Budget Remaining', '$12,450', 'good'], ['Pros Booked', '6', ''],
                ['Tasks', '82', ''],
            ],
            'priorities' => [
                ['Book wedding photographer', 'High', 'May 20', 'todo'],
                ['Finalize catering menu', 'High', 'May 28', 'todo'],
                ['Confirm final guest count', 'Medium', 'Jun 5', 'progress'],
                ['Choose wedding favors', 'Low', 'Jun 20', 'todo'],
                ['Send invitations', 'Medium', 'Jun 1', 'progress'],
            ],
            'budget' => [
                'total' => 25000, 'spent' => 12550,
                'lines' => [
                    ['Venue', 8000, '#7c3aed'], ['Catering', 6500, '#f97316'], ['Photography', 2500, '#16a34a'],
                    ['Floral & Décor', 3000, '#ec4899'], ['Music / DJ', 1800, '#2563eb'], ['Attire', 2200, '#0ea5e9'], ['Misc', 1000, '#64748b'],
                ],
            ],
            'vendors' => [
                ['The Garden Estate', 'Venue', 'Confirmed'], ['Gourmet Eats Co.', 'Catering', 'Confirmed'],
                ['Elite Events', 'Planning', 'Confirmed'], ['Blossom Floral', 'Floral', 'Pending'],
                ['DJ Soundwave', 'Music', 'Pending'], ['', 'Photography', 'Not booked'],
            ],
            'recommendations' => [
                ['Book photographer now', 'Top pros book out 6 months ahead — secure yours this week.', 'Find Pros'],
                ['Consider a weekday', 'Shift to a Friday and save ~$1,800 across vendors.', 'Explore'],
                ['Add live music', 'Couples who add a live set rate their reception 0.6★ higher.', 'Browse'],
            ],
        ]);
    }
}
