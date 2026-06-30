<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Upsell Assistant (professional). Spots add-ons & package upgrades per
 * booking and suggests them at the right moment. Representative data.
 */
class AiUpsellAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.upsell-assistant', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Upsell Opportunities', '6', ''], ['Potential Extra', '+$2,150', 'good'],
                ['Acceptance Rate', '64%', 'good'], ['Avg Order Uplift', '+18%', 'good'],
            ],
            'booking' => ['client' => 'Sarah Johnson', 'event' => 'Wedding Reception', 'package' => 'Silver — $1,850', 'guests' => '150 guests · 6 hrs'],
            'addons' => [
                ['Engagement Photo Session', 450, 82, 'High fit'],
                ['Highlight Film (3–5 min)', 799, 71, 'Trending'],
                ['Extra Hour of Coverage', 300, 64, 'Common'],
                ['Premium Album Upgrade', 350, 58, ''],
            ],
            'moment' => 'Best moment to offer: right after they accept the base proposal — acceptance is 3× higher than offering later.',
        ]);
    }
}
