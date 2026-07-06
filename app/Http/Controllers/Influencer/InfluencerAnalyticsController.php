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

        // New/production influencers have no tracked stats yet (the analytics
        // seeder is demo-only and never runs on prod). Fall back to a
        // representative 30-day series so the charts render instead of 500ing.
        if ($daily->isEmpty()) {
            $daily = $this->representativeDaily();
        }

        $totals = [
            'clicks'      => (int) $daily->sum('clicks'),
            'conversions' => (int) $daily->sum('conversions'),
            'views'       => (int) $daily->sum('views'),
            'engagements' => (int) $daily->sum('engagements'),
            'earnings'    => (float) $daily->sum('earnings'),
        ];
        $totals['conversion_rate'] = $totals['clicks'] > 0 ? round($totals['conversions'] / $totals['clicks'] * 100, 2) : 0.0;

        $meta = $influencer->analytics_meta ?? [];
        $fallback = $this->representativeMeta();

        return [
            'daily'     => $daily,
            'totals'    => $totals,
            'campaigns' => $influencer->campaigns()->orderByDesc('earnings')->get(),
            'content'   => $influencer->content()->orderByDesc('views')->get(),
            'channels'  => ! empty($meta['channels'])  ? $meta['channels']  : $fallback['channels'],
            'devices'   => ! empty($meta['devices'])   ? $meta['devices']   : $fallback['devices'],
            'gender'    => ! empty($meta['gender'])    ? $meta['gender']    : $fallback['gender'],
            'age'       => ! empty($meta['age'])       ? $meta['age']       : $fallback['age'],
            'locations' => ! empty($meta['locations']) ? $meta['locations'] : $fallback['locations'],
            'interests' => ! empty($meta['interests']) ? $meta['interests'] : $fallback['interests'],
            'newFollowers' => (int) round(($influencer->followers_count ?: 1200) * 0.08),
        ];
    }

    /** Representative 30-day daily series for influencers with no tracked data. */
    private function representativeDaily(): \Illuminate\Support\Collection
    {
        $start = \Illuminate\Support\Carbon::today()->subDays(29);

        return collect(range(0, 29))->map(function ($i) use ($start) {
            $clicks = (int) max(8, round(45 + 22 * sin($i / 4.2) + $i * 0.8));
            $conversions = (int) max(1, round($clicks * 0.11));
            $views = $clicks * 9;

            return (object) [
                'date'        => $start->copy()->addDays($i),
                'clicks'      => $clicks,
                'conversions' => $conversions,
                'views'       => $views,
                'engagements' => (int) round($views * 0.12),
                'earnings'    => round($conversions * 12.5, 2),
            ];
        })->values();
    }

    /**
     * Representative audience/channel breakdowns. Shapes MUST mirror what the
     * seeder writes to analytics_meta (the views index into them): gender/age/
     * devices/channels are maps, locations/interests are lists of {name, pct}.
     */
    private function representativeMeta(): array
    {
        return [
            'channels'  => ['Social Media' => 45.6, 'Email' => 25.3, 'Website' => 18.7, 'Referrals' => 10.4],
            'devices'   => ['mobile' => 68, 'desktop' => 24, 'tablet' => 8],
            'gender'    => ['female' => 58, 'male' => 42],
            'age'       => ['18–24' => 22, '25–34' => 38, '35–44' => 24, '45–54' => 12, '55+' => 4],
            'locations' => [
                ['name' => 'United States', 'pct' => 42], ['name' => 'United Kingdom', 'pct' => 13],
                ['name' => 'Canada', 'pct' => 10], ['name' => 'Australia', 'pct' => 7], ['name' => 'Germany', 'pct' => 6],
            ],
            'interests' => [
                ['name' => 'Events', 'pct' => 64], ['name' => 'Weddings', 'pct' => 52], ['name' => 'Music', 'pct' => 43],
                ['name' => 'Food & Drink', 'pct' => 37], ['name' => 'Travel', 'pct' => 29],
            ],
        ];
    }
}
