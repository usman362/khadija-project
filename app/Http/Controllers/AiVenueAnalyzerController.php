<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Venue Analyzer (client). Scores a venue for the event, maps the layout,
 * flags gaps and lists the vendors/equipment it will need. Representative data.
 */
class AiVenueAnalyzerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.venue-analyzer', [
            'aiLayout' => $aiLayout,
            'venue' => ['name' => 'The Garden Estate', 'address' => '1234 Garden Way, Pasadena, CA 91101', 'score' => 94, 'capacity' => 250, 'compatibility' => 96],
            'summary' => [
                ['Capacity Match', '92%', 'good'], ['Accessibility', '88%', 'good'],
                ['Parking', '85%', 'good'], ['Power / Electrical', '90%', 'good'],
            ],
            'gaps' => [
                ['Capacity & Layout', 'good', 'Fits 250 guests across lawn + ballroom.', 'No action needed.'],
                ['Accessibility', 'warn', 'One ramp; restrooms partially accessible.', 'Add a portable ADA restroom.'],
                ['Parking', 'good', '120 on-site spots + valet option.', 'Reserve valet for 6 PM peak.'],
                ['Power & Electrical', 'warn', '4 outdoor circuits; DJ + lighting heavy.', 'Hire a 20kW generator for the lawn.'],
                ['Catering Facilities', 'good', 'Full prep kitchen on-site.', 'Confirm load-in window with caterer.'],
                ['Sound Restrictions', 'warn', 'Amplified sound must end by 11 PM.', 'Plan an acoustic after-set.'],
            ],
            'zones' => [
                ['Parking', 6, 12, '#64748b'], ['Main Entrance', 42, 8, '#2563eb'],
                ['Garden Lawn', 20, 42, '#16a34a'], ['Ceremony', 62, 30, '#7c3aed'],
                ['Reception Hall', 60, 62, '#f97316'], ['Restrooms', 14, 74, '#64748b'],
                ['Loading Dock', 80, 16, '#64748b'], ['Emergency Exit', 84, 80, '#dc2626'],
            ],
            'vendors' => [
                ['Catering', '🍽'], ['Lighting', '💡'], ['Sound / AV', '🔊'], ['Floral', '🌸'],
                ['Furniture Rental', '🪑'], ['Décor', '🎀'], ['Generator', '⚡'], ['Valet', '🚗'],
            ],
            'alerts' => [
                'Amplified sound cut-off is 11 PM — confirm your timeline.',
                'Outdoor lawn needs supplemental power for DJ + lighting.',
                'Book ADA restroom — current accessibility is partial.',
            ],
            'hidden_costs' => [
                ['Generator rental', '$450'], ['ADA restroom', '$280'], ['Valet (4 hrs)', '$600'],
            ],
        ]);
    }
}
