<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Portfolio Optimizer (professional). Audits a pro's profile/portfolio and
 * recommends high-impact improvements to lift search visibility, views and
 * win-rate. Representative data.
 */
class AiPortfolioOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.portfolio-optimizer', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Portfolio Success Score', '94%', 'good'], ['Search Visibility', '87%', 'good'],
                ['Profile Views', '88%', 'good'], ['Profile Completeness', '98%', 'good'],
            ],
            'audit' => [
                ['Professional headshot', true], ['Business description', true],
                ['Portfolio (12+ photos)', true], ['Highlight video', false],
                ['Verified badges', true], ['Service packages listed', true],
                ['5+ recent reviews', false], ['Response time set', true],
            ],
            'recommendations' => [
                ['Update your hero image', 'Your top photo is 2 years old — a fresh hero lifts clicks ~18%.', 'High', '+18% clicks'],
                ['Add more portfolio photos', 'Add 8 photos to reach the 20-photo sweet spot for conversions.', 'High', '+12% inquiries'],
                ['Add a highlight video', 'Profiles with a 30-60s reel get 2.3× more saves.', 'High', '+130% saves'],
                ['Rewrite business description', 'AI can rewrite it keyword-rich for better search ranking.', 'Medium', '+9% visibility'],
                ['Add 5 more client reviews', 'You’re 5 reviews from the Top-Rated badge threshold.', 'Medium', 'Top-Rated badge'],
            ],
            'gallery' => [
                ['photo-1519741497674-611481863552', 92], ['photo-1465495976277-4387d4b0b4c6', 88],
                ['photo-1511795409834-ef04bbd61622', 95], ['photo-1519225421980-715cb0215aed', 71],
                ['photo-1469371670807-013ccf25f16a', 84], ['photo-1511285560929-80b456fea0bc', 90],
            ],
            'benchmark' => [
                ['Your Portfolio', 94, true], ['Top 10% in your area', 91, false], ['Category average', 73, false],
            ],
            'metrics' => [
                ['Media Quality', '72%', 'Good — add hi-res'], ['Profile Views', '+24%', 'vs last month'],
                ['Inquiry Rate', '8.4%', 'Above average'], ['Win Rate', '31%', 'Top quartile'],
            ],
        ]);
    }
}
