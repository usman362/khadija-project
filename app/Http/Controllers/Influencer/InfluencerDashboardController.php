<?php

namespace App\Http\Controllers\Influencer;

use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class InfluencerDashboardController extends Controller
{
    public function __construct(private readonly InfluencerServiceInterface $service)
    {
    }

    public function index(): View|RedirectResponse
    {
        $influencer = $this->currentInfluencer();
        if (! $influencer) {
            return redirect()->route('influencer.join')
                ->with('error', 'You are not registered as an influencer yet.');
        }

        $stats = [
            'total_earnings' => $influencer->total_earnings,
            'available_balance' => $influencer->available_balance,
            'paid_out' => $influencer->paid_out,
            'total_referrals' => $influencer->total_referrals,
            'tier' => $influencer->commission_tier->label(),
            'rate' => $influencer->commission_tier->rate(),
        ];

        $recentReferrals = $influencer->referrals()->latest()->limit(10)->get();
        $recentPayouts = $influencer->payoutRequests()->latest()->limit(5)->get();

        return view('influencer.dashboard.index', compact('influencer', 'stats', 'recentReferrals', 'recentPayouts'));
    }

    public function referrals(): View|RedirectResponse
    {
        $influencer = $this->currentInfluencer();
        if (! $influencer) {
            return redirect()->route('influencer.join');
        }

        $referrals = $influencer->referrals()->latest()->paginate(20);
        return view('influencer.dashboard.referrals', compact('influencer', 'referrals'));
    }

    public function payouts(): View|RedirectResponse
    {
        $influencer = $this->currentInfluencer();
        if (! $influencer) {
            return redirect()->route('influencer.join');
        }

        $payouts = $influencer->payoutRequests()->latest()->paginate(20);
        $minPayout = config('influencer.min_payout_threshold', 50);
        return view('influencer.dashboard.payouts', compact('influencer', 'payouts', 'minPayout'));
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        $influencer = $this->currentInfluencer();
        if (! $influencer) {
            return redirect()->route('influencer.join');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payout_method' => ['nullable', 'string', 'in:paypal,bank,other'],
            'payout_account' => ['nullable', 'string', 'max:255'],
            'user_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->requestPayout(
                $influencer,
                (float) $validated['amount'],
                $validated['payout_method'] ?? null,
                $validated['payout_account'] ?? null,
                $validated['user_notes'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('influencer.dashboard.payouts')
            ->with('status', 'Payout request submitted.');
    }

    protected function currentInfluencer(): ?Influencer
    {
        $user = Auth::user();
        return $user ? Influencer::where('user_id', $user->id)->first() : null;
    }
}
