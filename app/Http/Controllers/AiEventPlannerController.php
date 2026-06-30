<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Event Planner (client). Organises an event end-to-end — a smart checklist
 * by milestone, progress, AI recommendations, marketplace vendor suggestions
 * and upcoming deadlines. Representative data pending the live planner store.
 */
class AiEventPlannerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.event-planner', [
            'aiLayout' => $aiLayout,
            'event' => ['name' => 'Sarah & Alex Wedding', 'date' => 'June 14, 2027', 'location' => 'Los Angeles, CA', 'guests' => 120, 'progress' => 84, 'days_left' => 540],
            'phases' => [
                ['12 Months', 'done'], ['9 Months', 'done'], ['6 Months', 'active'],
                ['3 Months', 'todo'], ['1 Month', 'todo'], ['Event Day', 'todo'],
            ],
            'tasks' => [
                ['Set overall budget', 'High', 'Mar 12, 2026', 'done'],
                ['Create preliminary guest list', 'High', 'Mar 28, 2026', 'done'],
                ['Book wedding venue', 'High', 'Apr 15, 2026', 'progress'],
                ['Book photographer', 'High', 'May 20, 2026', 'progress'],
                ['Research & shortlist caterers', 'Medium', 'Jun 10, 2026', 'todo'],
                ['Hire wedding planner', 'Medium', 'Jun 25, 2026', 'todo'],
                ['Choose floral designer', 'Medium', 'Jul 15, 2026', 'todo'],
                ['Send save-the-dates', 'Low', 'Aug 1, 2026', 'todo'],
                ['Book entertainment / DJ', 'Medium', 'Aug 20, 2026', 'todo'],
            ],
            'recommendations' => [
                'Book your venue this month — top LA venues fill 14+ months ahead.',
                'Your budget allows a premium photographer — shortlist now.',
                'Consider a day-of coordinator to reduce week-of stress.',
            ],
            'marketplace' => [
                ['Elegant Affairs Events', 'Planning', '4.9', '$2,500'],
                ['TrueBlue Photography', 'Photography', '4.8', '$1,800'],
                ['Blossom Floral Studio', 'Floral', '4.9', '$1,200'],
            ],
            'deadlines' => [
                ['Book wedding venue', 'Apr 15', 'high'],
                ['Book photographer', 'May 20', 'high'],
                ['Research caterers', 'Jun 10', 'med'],
            ],
            'tips' => [
                'Aim to spend ~50% of budget on venue + catering.',
                'Lock vendors in priority order: venue → photo → catering.',
                'Build a 10% buffer for last-minute changes.',
            ],
        ]);
    }
}
