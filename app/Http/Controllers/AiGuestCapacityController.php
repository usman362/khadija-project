<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

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

    /**
     * Estimate comfort, legal and comfort-score capacity from real input.
     * Pure deterministic math — no external API.
     */
    public function compute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_sqft'     => ['required', 'numeric', 'min:20', 'max:2000000'],
                'seating_style' => ['required', 'string', 'in:banquet,theater,cocktail,mixed'],
                'guest_count'   => ['required', 'integer', 'min:1', 'max:100000'],
            ]);

            $sqft   = (float) $validated['room_sqft'];
            $style  = $validated['seating_style'];
            $guests = (int) $validated['guest_count'];

            // Square feet needed per person by seating style.
            $perPerson = [
                'banquet' => 12,
                'theater' => 8,
                'cocktail' => 6,
                'mixed'   => 10,
            ][$style];

            $comfort = (int) floor($sqft / $perPerson);
            $legal   = (int) floor($sqft / 7); // ~7 sq ft/person life-safety rule of thumb
            $comfort = max($comfort, 1);
            $legal   = max($legal, 1);

            // Comfort score: 100 when well under comfort capacity, falling as it approaches/exceeds.
            $ratio = $guests / $comfort;
            if ($ratio <= 0.75) {
                $pct = 100;
            } elseif ($ratio <= 1.0) {
                // 0.75 -> 100, 1.0 -> 80
                $pct = (int) round(100 - (($ratio - 0.75) / 0.25) * 20);
            } elseif ($ratio <= 1.5) {
                // 1.0 -> 80, 1.5 -> 40
                $pct = (int) round(80 - (($ratio - 1.0) / 0.5) * 40);
            } else {
                // beyond 1.5x comfort capacity
                $pct = (int) max(5, round(40 - (($ratio - 1.5) * 30)));
            }
            $pct = max(0, min(100, $pct));

            $comfortClass = $guests <= $comfort ? 'good' : 'warn';
            $scoreClass   = $pct >= 80 ? 'good' : ($pct >= 50 ? 'warn' : '');

            // Insight helper: rate a metric against a comfort threshold.
            $rate = static function (float $r): array {
                if ($r <= 0.85) {
                    return ['Good', 'good'];
                }
                if ($r <= 1.05) {
                    return ['Adequate', 'good'];
                }
                if ($r <= 1.3) {
                    return ['Tight', 'warn'];
                }
                return ['Over capacity', 'warn'];
            };

            [$seatLbl, $seatCls]     = $rate($guests / $comfort);
            [$walkLbl, $walkCls]     = $rate($guests / (int) floor($sqft / ($perPerson + 3)));
            // One buffet line comfortably serves ~75 guests.
            $buffetCap = 75;
            [$buffetLbl, $buffetCls] = $rate($guests / $buffetCap);
            // One restroom set comfortably serves ~50 guests.
            $restroomCap = 50;
            [$restLbl, $restCls]     = $rate($guests / $restroomCap);
            [$exitLbl, $exitCls]     = $rate($guests / $legal);

            $tips = [];
            if ($guests <= $comfort) {
                $tips[] = "At {$guests} guests you have room for about " . ($comfort - $guests)
                    . " more before spacing feels tight (comfort estimate: {$comfort}).";
            } else {
                $tips[] = "You're about " . ($guests - $comfort)
                    . " guests over the comfort estimate of {$comfort} — consider a larger space or a {$style} alternative.";
            }
            $buffetLines = max(1, (int) ceil($guests / $buffetCap));
            $tips[] = "Plan for roughly {$buffetLines} buffet or service line(s) to keep the meal flowing (about {$buffetCap} guests each).";
            $restroomSets = max(1, (int) ceil($guests / $restroomCap));
            $tips[] = "Aim for about {$restroomSets} restroom set(s) and keep exits clear — legal capacity is estimated near {$legal}.";

            return response()->json([
                'success' => true,
                'result'  => [
                    'capacity' => [
                        'expected' => $guests,
                        'comfort'  => $comfort,
                        'legal'    => $legal,
                    ],
                    'comfort_score_pct' => $pct,
                    'stats' => [
                        ['Expected Guests', (string) $guests, ''],
                        ['Recommended Capacity', (string) $comfort, $comfortClass],
                        ['Guest Comfort Score', $pct . '%', $scoreClass],
                        ['Legal Capacity', (string) $legal, ''],
                    ],
                    'insights' => [
                        ['Seating', $seatLbl, $seatCls],
                        ['Walking Space', $walkLbl, $walkCls],
                        ['Buffet Line', $buffetLbl, $buffetCls],
                        ['Restrooms', $restLbl, $restCls],
                        ['Exit Access', $exitLbl, $exitCls],
                    ],
                    'tips' => $tips,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
