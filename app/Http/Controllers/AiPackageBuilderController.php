<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Package Builder (professional). Builds, prices and compares tiered service
 * packages (Bronze → Platinum) with profit margins and add-ons. Representative.
 */
class AiPackageBuilderController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.package-builder', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Avg Profit Margin', '58%', 'good'], ['Market Competitiveness', '94%', 'good'],
                ['Active Packages', '4', ''], ['Bundle Revenue', '$18,650', 'good'],
            ],
            'tiers' => [
                ['Bronze', 1250, '52%', '8 hrs', '#b08d57', false,
                    ['Wedding day photography', '200 edited photos', 'Online gallery', '1 photographer'],
                    ['+ Extra hour — $150', '+ Prints — $200']],
                ['Silver', 1850, '58%', '10 hrs', '#8b95a5', true,
                    ['Everything in Bronze', 'Engagement session', '400 edited photos', '2 photographers', 'Print release'],
                    ['+ Album — $300', '+ Drone — $250']],
                ['Gold', 2550, '61%', '12 hrs', '#c9a227', false,
                    ['Everything in Silver', 'Second shooter', '600 edited photos', 'Premium album', 'Drone coverage'],
                    ['+ Videography — $800']],
                ['Platinum', 3750, '64%', '16 hrs', '#3b3f4a', false,
                    ['Everything in Gold', 'Cinematic videography', 'Unlimited edited photos', 'Luxury album', 'Same-day teaser'],
                    ['Fully bespoke']],
            ],
            'compare' => [
                ['Hours of coverage', ['8 hrs', '10 hrs', '12 hrs', '16 hrs']],
                ['Photographers', ['1', '2', '2 + 2nd shooter', '3 + video']],
                ['Edited photos', ['200', '400', '600', 'Unlimited']],
                ['Engagement session', ['—', '✓', '✓', '✓']],
                ['Album', ['—', 'Add-on', 'Premium', 'Luxury']],
                ['Videography', ['—', '—', 'Add-on', '✓']],
            ],
            'suggestions' => [
                'Your Silver tier converts best — feature it as “Most Popular”.',
                'Add a Same-Day Teaser to Gold (+$400) — high demand, low effort.',
                'Bronze margin is thin — trim 1 hour or raise to $1,350.',
            ],
        ]);
    }
}
