<?php

namespace App\Http\Controllers\Influencer;

use App\Domain\Influencer\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Influencer;
use App\Models\InfluencerReferral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The four sidebar-only program sections of the influencer portal:
 * Referral Center, Marketing Center, Leaderboards & Challenges, Commissions.
 * All read from real referral rows + config tiers.
 */
class InfluencerProgramController extends Controller
{
    public function referralCenter(): View|RedirectResponse
    {
        return $this->guard(function (Influencer $inf) {
            $rows = $inf->referrals()->latest()->get();
            $bySource = $rows->whereNotNull('source')->groupBy('source')->map->count();

            return view('influencer.program.referral-center', [
                'influencer'  => $inf,
                'referralUrl' => $inf->referralUrl(),
                'referralCode'=> $inf->referral_code,
                'recent'      => $rows->take(12),
                'totals'      => $this->statusTotals($rows),
                'bySource'    => $bySource,
                'thisMonth'   => $rows->where('created_at', '>=', now()->startOfMonth())->count(),
            ]);
        });
    }

    public function marketing(): View|RedirectResponse
    {
        return $this->guard(fn (Influencer $inf) => view('influencer.program.marketing', [
            'influencer'  => $inf,
            'referralUrl' => $inf->referralUrl(),
            'referralCode'=> $inf->referral_code,
        ]));
    }

    public function leaderboards(): View|RedirectResponse
    {
        return $this->guard(function (Influencer $inf) {
            $board = Influencer::query()
                ->orderByDesc('total_referrals')
                ->orderByDesc('total_earnings')
                ->get()
                ->values();
            $myRank = $board->search(fn ($i) => $i->id === $inf->id);
            $myRank = $myRank === false ? null : $myRank + 1;

            $monthRefs = $inf->referrals()
                ->where('status', '!=', ReferralStatus::CANCELLED)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            $tiers = collect(config('influencer.tiers'))->values();
            $currentIdx = collect(config('influencer.tiers'))->keys()->search($inf->commission_tier->value);
            $nextTier = $tiers->get($currentIdx + 1);
            $toNextTier = $nextTier ? max(0, ($nextTier['min_referrals'] ?? 0) - $inf->total_referrals) : 0;

            $minPayout = (float) config('influencer.min_payout_threshold', 50);

            $challenges = [
                [
                    'title' => 'Monthly Referral Sprint', 'desc' => 'Refer 5 new members this month.',
                    'icon' => 'bolt', 'color' => '#f97316',
                    'current' => $monthRefs, 'target' => 5, 'unit' => 'referrals',
                ],
                [
                    'title' => 'Reach Your Next Tier', 'desc' => $nextTier ? 'Climb to '.$nextTier['label'].' tier.' : 'You are at the top tier!',
                    'icon' => 'trophy', 'color' => '#7c3aed',
                    'current' => $nextTier ? $inf->total_referrals : 1, 'target' => $nextTier ? ($nextTier['min_referrals'] ?? 1) : 1, 'unit' => 'referrals',
                ],
                [
                    'title' => 'Unlock a Payout', 'desc' => 'Build $'.number_format($minPayout).' in available balance.',
                    'icon' => 'cash', 'color' => '#16a34a',
                    'current' => (float) $inf->available_balance, 'target' => $minPayout, 'unit' => '$',
                ],
            ];

            return view('influencer.program.leaderboards', [
                'influencer' => $inf,
                'board' => $board,
                'myRank' => $myRank,
                'challenges' => $challenges,
                'toNextTier' => $toNextTier,
                'nextTier' => $nextTier,
            ]);
        });
    }

    public function commissions(): View|RedirectResponse
    {
        return $this->guard(function (Influencer $inf) {
            $rows = $inf->referrals()->latest()->get();
            $tiers = collect(config('influencer.tiers'));
            $currentKey = $inf->commission_tier->value;

            return view('influencer.program.commissions', [
                'influencer'  => $inf,
                'tiers'       => $tiers,
                'currentKey'  => $currentKey,
                'currentRate' => $inf->commission_tier->rate(),
                'totals'      => $this->statusTotals($rows),
                'history'     => $rows->take(15),
                'minPayout'   => (float) config('influencer.min_payout_threshold', 50),
            ]);
        });
    }

    /** Sum referral commission amounts grouped by status. */
    private function statusTotals($rows): array
    {
        return [
            'pending_count' => $rows->where('status', ReferralStatus::PENDING)->count(),
            'pending'   => round($rows->where('status', ReferralStatus::PENDING)->sum('commission_amount'), 2),
            'earned'    => round($rows->where('status', ReferralStatus::EARNED)->sum('commission_amount'), 2),
            'paid'      => round($rows->where('status', ReferralStatus::PAID)->sum('commission_amount'), 2),
            'total'     => round($rows->whereIn('status', [ReferralStatus::EARNED, ReferralStatus::PAID])->sum('commission_amount'), 2),
            'count'     => $rows->where('status', '!=', ReferralStatus::CANCELLED)->count(),
            'converted' => $rows->whereIn('status', [ReferralStatus::EARNED, ReferralStatus::PAID])->count(),
        ];
    }

    private function guard(\Closure $cb): View|RedirectResponse
    {
        $inf = Auth::user()?->influencer;
        if (! $inf) {
            return redirect()->route('influencer.join')->with('error', 'You are not registered as an influencer yet.');
        }
        return $cb($inf);
    }
}
