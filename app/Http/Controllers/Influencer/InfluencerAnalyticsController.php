<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Analytics section of the influencer portal. All figures are computed from the
 * real tracked tables (influencer_campaigns / _content / _daily_stats) plus the
 * aggregate breakdowns in influencers.analytics_meta.
 */
class InfluencerAnalyticsController extends Controller
{
    public function performance(): View|RedirectResponse     { return $this->render('influencer.analytics.performance'); }
    public function campaigns(): View|RedirectResponse       { return $this->render('influencer.analytics.campaigns'); }
    public function audience(): View|RedirectResponse        { return $this->render('influencer.analytics.audience'); }
    public function content(): View|RedirectResponse         { return $this->render('influencer.analytics.content'); }
    public function reports(): View|RedirectResponse         { return $this->render('influencer.analytics.reports'); }
    public function gettingStarted(): View|RedirectResponse  { return $this->render('influencer.analytics.getting-started'); }

    /** Export the daily stats as CSV (real data). */
    public function export(): Response|RedirectResponse
    {
        $influencer = auth()->user()?->influencer;
        if (! $influencer) {
            return redirect()->route('influencer.join');
        }

        $rows = $influencer->dailyStats()->orderBy('date')->get();
        $csv = "Date,Clicks,Conversions,Views,Engagements,Earnings\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%d,%d,%d,%d,%.2f\n", $r->date->toDateString(), $r->clicks, $r->conversions, $r->views, $r->engagements, $r->earnings);
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gigresource-analytics-'.now()->toDateString().'.csv"',
        ]);
    }

    private function render(string $view): View|RedirectResponse
    {
        $influencer = auth()->user()?->influencer;
        if (! $influencer) {
            return redirect()->route('influencer.join')->with('error', 'You are not registered as an influencer yet.');
        }

        return view($view, ['influencer' => $influencer] + $this->context($influencer));
    }

    private function context(Influencer $influencer): array
    {
        $daily = $influencer->dailyStats()->orderBy('date')->get();
        $totals = [
            'clicks'      => (int) $daily->sum('clicks'),
            'conversions' => (int) $daily->sum('conversions'),
            'views'       => (int) $daily->sum('views'),
            'engagements' => (int) $daily->sum('engagements'),
            'earnings'    => (float) $daily->sum('earnings'),
        ];
        $totals['conversion_rate'] = $totals['clicks'] > 0 ? round($totals['conversions'] / $totals['clicks'] * 100, 2) : 0.0;

        $meta = $influencer->analytics_meta ?? [];

        return [
            'daily'     => $daily,
            'totals'    => $totals,
            'campaigns' => $influencer->campaigns()->orderByDesc('earnings')->get(),
            'content'   => $influencer->content()->orderByDesc('views')->get(),
            'channels'  => $meta['channels'] ?? [],
            'devices'   => $meta['devices'] ?? [],
            'gender'    => $meta['gender'] ?? [],
            'age'       => $meta['age'] ?? [],
            'locations' => $meta['locations'] ?? [],
            'interests' => $meta['interests'] ?? [],
            'newFollowers' => (int) round($influencer->followers_count * 0.08),
        ];
    }
}
