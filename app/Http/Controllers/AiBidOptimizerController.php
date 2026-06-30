<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Bid Optimizer (professional). Recommends the best bid amount balanced
 * against the pro's margin, using budget, competition and similar-bid history.
 * Representative data.
 */
class AiBidOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

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
}
