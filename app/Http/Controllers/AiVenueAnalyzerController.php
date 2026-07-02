<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Venue Analyzer (client). Scores a venue for the event, maps the layout,
 * flags gaps and lists the vendors/equipment it will need. The show() page
 * renders representative data; compute() runs a real space-planning calculation
 * on the user's inputs (square footage, guests, seating style, dance floor).
 */
class AiVenueAnalyzerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

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

    /**
     * Run a real space-planning calculation: required square footage, maximum
     * capacity, utilization, a fit verdict, an area-by-area breakdown and tips —
     * all derived from the user's inputs using per-person space standards.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_sqft'     => ['required', 'numeric', 'min:1', 'max:10000000'],
            'guest_count'    => ['required', 'integer', 'min:1', 'max:100000'],
            'seating_style'  => ['required', 'string', 'in:banquet,theater,cocktail,classroom'],
            'has_dancefloor' => ['nullable', 'boolean'],
        ]);

        try {
            $sqft   = (float) $validated['venue_sqft'];
            $guests = (int) $validated['guest_count'];
            $style  = $validated['seating_style'];
            $dance  = (bool) ($validated['has_dancefloor'] ?? false);

            // Industry rule-of-thumb square feet per person by seating style.
            $perPerson = [
                'banquet'   => 12,
                'theater'   => 8,
                'cocktail'  => 6,
                'classroom' => 18,
            ][$style];

            $baseRequired = $guests * $perPerson;
            $danceAdd     = $dance ? (int) round($baseRequired * 0.20) : 0;
            $requiredSqft = $baseRequired + $danceAdd;

            $maxCapacity   = (int) floor($sqft / $perPerson);
            $utilizationPct = $maxCapacity > 0 ? round(($guests / $maxCapacity) * 100, 1) : 999.0;

            // Verdict based on how the required space compares to what's available.
            if ($requiredSqft > $sqft) {
                $verdict = 'Over capacity';
            } elseif ($requiredSqft > $sqft * 0.85) {
                $verdict = 'Tight — consider fewer guests or a bigger space';
            } else {
                $verdict = 'Comfortable fit';
            }

            // Area-by-area breakdown. Seating covers the core per-person need;
            // circulation, service and dance floor are proportional add-ons.
            $seatingNeed     = $baseRequired;
            $circulationNeed = (int) round($baseRequired * 0.30);
            $serviceNeed     = (int) round($baseRequired * 0.15);

            $breakdown = [];
            $breakdown[] = [
                'area'   => 'Seating',
                'status' => $this->areaStatus($seatingNeed, $sqft),
            ];
            $breakdown[] = [
                'area'   => 'Circulation',
                'status' => $this->areaStatus($seatingNeed + $circulationNeed, $sqft),
            ];
            if ($dance) {
                $breakdown[] = [
                    'area'   => 'Dance Floor',
                    'status' => $this->areaStatus($baseRequired + $danceAdd, $sqft),
                ];
            }
            $breakdown[] = [
                'area'   => 'Service Areas',
                'status' => $this->areaStatus($requiredSqft + $serviceNeed, $sqft),
            ];

            $tips = [];
            $tips[] = 'For ' . number_format($guests) . ' guests in a ' . $style . ' layout you need about '
                . number_format($requiredSqft) . ' sq ft; this space offers ' . number_format($sqft) . ' sq ft.';
            $tips[] = 'At ' . $perPerson . ' sq ft per person, this room seats an estimated '
                . number_format($maxCapacity) . ' guests in a ' . $style . ' setup.';
            if ($dance) {
                $tips[] = 'A dance floor adds roughly ' . number_format($danceAdd)
                    . ' sq ft (20%) — keep it central so it doesn\'t block service paths.';
            }
            if ($verdict === 'Over capacity') {
                $overflow = max(0, $guests - $maxCapacity);
                $tips[] = 'You are about ' . number_format($overflow) . ' guests over a comfortable limit — '
                    . 'consider a larger venue, a cocktail-style layout, or trimming the list.';
            } elseif ($verdict === 'Comfortable fit') {
                $tips[] = 'You have comfortable headroom (using about ' . $utilizationPct
                    . '% of estimated capacity) — good for lounge areas, a photo booth, or a bar station.';
            } else {
                $tips[] = 'This is a tight fit (about ' . $utilizationPct
                    . '% of capacity) — reduce table sizes or move to a mingling layout to free up space.';
            }

            return response()->json([
                'success' => true,
                'result'  => [
                    'required_sqft'   => $requiredSqft,
                    'max_capacity'    => $maxCapacity,
                    'utilization_pct' => $utilizationPct,
                    'verdict'         => $verdict,
                    'breakdown'       => $breakdown,
                    'tips'            => $tips,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rate an area: "good" with headroom, "tight" near the limit, "over" beyond.
     */
    private function areaStatus(float $cumulativeNeed, float $available): string
    {
        if ($cumulativeNeed > $available) {
            return 'over';
        }
        if ($cumulativeNeed > $available * 0.85) {
            return 'tight';
        }
        return 'good';
    }
}
