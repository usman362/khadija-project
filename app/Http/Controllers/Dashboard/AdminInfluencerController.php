<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Influencer;
use App\Models\InfluencerPayoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminInfluencerController extends Controller
{
    public function __construct(private readonly InfluencerServiceInterface $service)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $query = Influencer::query()->with('user')->latest();
        if ($status) {
            $query->where('status', $status);
        }
        $influencers = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => Influencer::where('status', 'pending')->count(),
            'approved' => Influencer::where('status', 'approved')->count(),
            'rejected' => Influencer::where('status', 'rejected')->count(),
        ];

        return view('dashboard.admin.influencers.index', compact('influencers', 'counts', 'status'));
    }

    public function show(Influencer $influencer): View
    {
        $influencer->load(['user', 'referrals.booking', 'payoutRequests']);
        return view('dashboard.admin.influencers.show', compact('influencer'));
    }

    public function approve(Request $request, Influencer $influencer): RedirectResponse
    {
        $this->authorize('approve', $influencer);
        $this->service->approve($influencer, $request->user(), $request->input('notes'));
        return back()->with('status', 'Influencer approved.');
    }

    public function reject(Request $request, Influencer $influencer): RedirectResponse
    {
        $this->authorize('reject', $influencer);
        $this->service->reject($influencer, $request->user(), $request->input('notes'));
        return back()->with('status', 'Influencer rejected.');
    }

    public function payouts(Request $request): View
    {
        $status = $request->query('status');
        $query = InfluencerPayoutRequest::query()->with('influencer.user')->latest();
        if ($status) {
            $query->where('status', $status);
        }
        $payouts = $query->paginate(20)->withQueryString();
        return view('dashboard.admin.influencers.payouts', compact('payouts', 'status'));
    }

    public function markPayoutPaid(Request $request, InfluencerPayoutRequest $payoutRequest): RedirectResponse
    {
        $this->service->markPayoutPaid($payoutRequest, $request->user(), $request->input('notes'));
        return back()->with('status', 'Payout marked as paid.');
    }

    public function rejectPayout(Request $request, InfluencerPayoutRequest $payoutRequest): RedirectResponse
    {
        $this->service->rejectPayout($payoutRequest, $request->user(), $request->input('notes'));
        return back()->with('status', 'Payout request rejected.');
    }
}
