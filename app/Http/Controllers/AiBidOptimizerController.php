<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Bid Optimizer (professional). Recommends the best bid amount balanced
 * against the pro's margin, using budget, competition and similar-bid history.
 * Representative data.
 */
class AiBidOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.bid-optimizer', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Recommended Bid', '$1,720', 'good'], ['Win Probability', '82%', 'good'],
                ['Competing Bids', '7', ''], ['Market Range', '$1.2k–2k', ''],
            ],
            'gig' => [
                'title' => 'Wedding Photography — Johnson Reception',
                'client_budget' => '$1,500 – $2,000', 'date' => 'Jun 14, 2027',
                'target_margin' => '40%', 'urgency' => 'Standard',
            ],
            'bid' => [
                'recommended' => 1720, 'win' => 82, 'margin' => 46,
                'low' => ['amount' => 1400, 'margin' => 41, 'label' => 'Too low'],
                'high' => ['amount' => 2200, 'margin' => 56, 'label' => 'Too high'],
            ],
            'strategy' => 'Bid within 6 hours and include a short personalised note — early, personal bids in this category win ~40% more often.',
        ]);
    }

    /**
     * Compute a suggested bid from real inputs using deterministic rules/math.
     * No external API — pure PHP. Results are estimates only.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gig_budget'      => ['required', 'numeric', 'min:1', 'max:9999999'],
            'your_base_price' => ['required', 'numeric', 'min:1', 'max:9999999'],
            'num_competitors' => ['required', 'integer', 'min:0', 'max:50'],
            'turnaround'      => ['nullable', 'in:standard,rush'],
        ]);

        try {
            $budget     = (float) $validated['gig_budget'];
            $base       = (float) $validated['your_base_price'];
            $competitors = (int) $validated['num_competitors'];
            $turnaround = $validated['turnaround'] ?? 'standard';

            // Suggested bid: sit slightly below the budget but never under your base price.
            // Rush jobs justify a modest premium (they are harder to staff).
            $rushFactor = $turnaround === 'rush' ? 1.08 : 1.0;
            $ceiling    = $budget * 0.92;
            $floor      = max($base * 1.05, $budget * 0.80);
            $suggested  = min($ceiling, $floor);
            $suggested  = max($suggested, $base) * $rushFactor;
            // Never exceed the budget itself.
            $suggested  = min($suggested, $budget);
            $suggested  = round($suggested, 2);

            // A sensible range around the suggested bid, clamped to base..budget.
            $low  = round(max($base, $suggested * 0.93), 2);
            $high = round(min($budget, $suggested * 1.06), 2);

            // Win probability: higher when the bid leaves room under budget and when
            // there are fewer competitors. Deterministic, clamped 5-95.
            // "Room" = how far the bid sits below budget (0..1).
            $room = $budget > 0 ? max(0.0, min(1.0, ($budget - $suggested) / $budget)) : 0.0;
            $prob = 90 - ($competitors * 3.2) + ($room * 40);
            if ($turnaround === 'rush') {
                $prob -= 4; // rush narrows the pool of pros willing/able to bid, but also raises client scrutiny
            }
            $winProbability = (int) round(max(5, min(95, $prob)));

            $margin = $base > 0 ? (int) round((($suggested - $base) / $suggested) * 100) : 0;

            $positioning = [
                "Your suggested bid of $" . number_format($suggested, 0) . " sits about " . (int) round((1 - $suggested / $budget) * 100) . "% under the client's budget of $" . number_format($budget, 0) . ", which typically reads as competitive without looking underpriced.",
                $competitors > 0
                    ? "With {$competitors} competing bid" . ($competitors === 1 ? '' : 's') . " estimated, the win probability lands near {$winProbability}% — respond early and lead with relevant work to stand out."
                    : "With no competing bids estimated, you have room to hold closer to the top of your range around $" . number_format($high, 0) . ".",
                "Staying at or above your base price of $" . number_format($base, 0) . " keeps an estimated ~{$margin}% margin on this job.",
            ];
            if ($turnaround === 'rush') {
                $positioning[] = "This is a rush job — a modest premium is built into the suggestion, but confirm you can realistically meet the tighter timeline before committing.";
            }

            $result = [
                'suggested_bid'      => $suggested,
                'bid_range'          => ['low' => $low, 'high' => $high],
                'win_probability_pct' => $winProbability,
                'margin_pct'         => $margin,
                'positioning'        => $positioning,
                'summary'            => "Estimated sweet spot: $" . number_format($suggested, 0) . " (range $" . number_format($low, 0) . "–$" . number_format($high, 0) . ") with an estimated {$winProbability}% chance of winning against {$competitors} competitor" . ($competitors === 1 ? '' : 's') . ". These are estimates to guide your decision, not guarantees.",
            ];
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
        ]);
    }
}
