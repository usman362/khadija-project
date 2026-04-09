@extends('layouts.dashboard')

@section('title', 'Influencer Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="award" class="me-2" style="width:24px;height:24px;"></i> Influencer Dashboard</h4>
        <p class="text-secondary mb-0">Welcome back, {{ $influencer->full_name }}!</p>
    </div>
    <span class="badge bg-{{ $influencer->status->value === 'approved' ? 'success' : ($influencer->status->value === 'pending' ? 'warning' : 'danger') }}">
        {{ $influencer->status->label() }}
    </span>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if(!$influencer->isApproved())
    <div class="alert alert-info">Your application is under review. You'll get a referral link once approved.</div>
@else
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Your Referral Link</h6>
            <div class="input-group">
                <input type="text" class="form-control" value="{{ $influencer->referralUrl() }}" readonly id="refLink">
                <button class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); this.innerText='Copied!'">Copy</button>
            </div>
            <small class="text-muted mt-2 d-block">Share this link. When people sign up through it, you earn commission.</small>
        </div>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Earnings</h6>
                <h3 class="mb-0">${{ number_format($stats['total_earnings'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Available Balance</h6>
                <h3 class="mb-0 text-success">${{ number_format($stats['available_balance'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Paid Out</h6>
                <h3 class="mb-0">${{ number_format($stats['paid_out'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Referrals / Tier</h6>
                <h3 class="mb-0">{{ $stats['total_referrals'] }}</h3>
                <small class="text-muted">{{ $stats['tier'] }} · {{ $stats['rate'] }}%</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Recent Referrals</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($recentReferrals as $r)
                        <tr>
                            <td><small>{{ $r->type->value }}</small></td>
                            <td>${{ number_format($r->commission_amount, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ $r->status->value }}</span></td>
                            <td><small>{{ $r->created_at->diffForHumans() }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No referrals yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Recent Payouts</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($recentPayouts as $p)
                        <tr>
                            <td>${{ number_format($p->amount, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ $p->status->value }}</span></td>
                            <td><small>{{ $p->created_at->diffForHumans() }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No payouts yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
