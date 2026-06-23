<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Badges & Tiers section of the influencer portal. Everything is computed from
 * real data — the influencer's referral count drives tier progression
 * (config('influencer.tiers')), and earnings/referrals/profile drive the
 * achievement badges (config('influencer.badges')).
 */
class InfluencerBadgesController extends Controller
{
    public function tiers(): View|RedirectResponse
    {
        return $this->render('influencer.badges.tiers');
    }

    public function current(): View|RedirectResponse
    {
        return $this->render('influencer.badges.current');
    }

    public function progress(): View|RedirectResponse
    {
        return $this->render('influencer.badges.progress');
    }

    public function badges(): View|RedirectResponse
    {
        return $this->render('influencer.badges.all');
    }

    public function benefits(): View|RedirectResponse
    {
        return $this->render('influencer.badges.benefits');
    }

    /** Resolve the influencer + shared tier/badge context, then render. */
    private function render(string $view): View|RedirectResponse
    {
        $influencer = auth()->user()?->influencer;
        if (! $influencer) {
            return redirect()->route('influencer.join')->with('error', 'You are not registered as an influencer yet.');
        }

        return view($view, ['influencer' => $influencer] + $this->context($influencer));
    }

    /**
     * @return array{tiers:array,currentKey:string,currentIndex:int,nextTier:?array,referralsToNext:?int,progressPct:int,badges:array}
     */
    private function context(Influencer $influencer): array
    {
        $tiers = config('influencer.tiers', []);
        $keys  = array_keys($tiers);
        $currentKey = $influencer->commission_tier->value;
        $currentIndex = array_search($currentKey, $keys, true) ?: 0;
        $refs = (int) $influencer->total_referrals;

        // Next tier + progress toward it (real referral thresholds).
        $nextKey = $keys[$currentIndex + 1] ?? null;
        $nextTier = $nextKey ? $tiers[$nextKey] + ['key' => $nextKey] : null;
        $referralsToNext = $nextTier ? max(0, ($nextTier['min_referrals'] ?? 0) - $refs) : null;
        if ($nextTier) {
            $floor = $tiers[$currentKey]['min_referrals'] ?? 0;
            $ceil  = $nextTier['min_referrals'] ?? 1;
            $span  = max(1, $ceil - $floor);
            $progressPct = (int) min(100, max(0, round(($refs - $floor) / $span * 100)));
        } else {
            $progressPct = 100; // top tier
        }

        // Achievement badges — earned/locked from real metrics.
        $metrics = [
            'referrals' => $refs,
            'earnings'  => (float) $influencer->total_earnings,
            'profile'   => (int) $influencer->profile_score,
            'tier'      => $currentIndex + 1,
        ];
        $badges = collect(config('influencer.badges', []))->map(function ($b, $slug) use ($metrics) {
            $earned = ($metrics[$b['metric']] ?? 0) >= $b['threshold'];
            return $b + ['slug' => $slug, 'earned' => $earned];
        })->values()->all();

        return [
            'tiers' => $tiers,
            'tierKeys' => $keys,
            'currentKey' => $currentKey,
            'currentIndex' => $currentIndex,
            'nextTier' => $nextTier,
            'referralsToNext' => $referralsToNext,
            'progressPct' => $progressPct,
            'badges' => $badges,
        ];
    }
}
