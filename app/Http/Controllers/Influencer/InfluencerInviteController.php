<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * "Invite & Earn More" section of the influencer portal. Invite Tools + Earn
 * are fully dynamic (real referral link, earnings, tier data); Promote /
 * Become / Onboarding / Success Stories / FAQs are program content pages.
 */
class InfluencerInviteController extends Controller
{
    public function tools(): View|RedirectResponse   { return $this->render('influencer.invite.tools'); }
    public function earn(): View|RedirectResponse     { return $this->render('influencer.invite.earn'); }
    public function promote(): View|RedirectResponse  { return $this->render('influencer.invite.promote'); }
    public function become(): View|RedirectResponse   { return $this->render('influencer.invite.become'); }
    public function onboarding(): View|RedirectResponse { return $this->render('influencer.invite.onboarding'); }
    public function stories(): View|RedirectResponse  { return $this->render('influencer.invite.stories'); }
    public function faqs(): View|RedirectResponse     { return $this->render('influencer.invite.faqs'); }

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
        $earned = ['earned', 'paid'];
        $referrals = $influencer->referrals();
        $signups = (clone $referrals)->whereIn('status', $earned)->count();

        // 6-month earnings series for the Earn chart.
        $series = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $series[$m->format('M')] = (float) (clone $referrals)->whereIn('status', $earned)
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)
                ->sum('commission_amount');
        }

        // Pending = earned-but-not-paid commission.
        $pending = (float) (clone $referrals)->where('status', 'earned')->sum('commission_amount');

        return [
            'referralUrl' => method_exists($influencer, 'referralUrl') ? $influencer->referralUrl() : url('/ref/'.$influencer->referral_code),
            'referralCode' => $influencer->referral_code,
            'signups' => $signups,
            'pending' => $pending,
            'earningsSeries' => $series,
            'tiers' => config('influencer.tiers', []),
            'currentKey' => $influencer->commission_tier->value,
            'signupBonus' => (float) config('influencer.signup_bonus', 5),
            'minPayout' => (float) config('influencer.min_payout_threshold', 50),
        ];
    }
}
