@extends('layouts.dashboard')
@section('title', 'Influencer Details')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i data-lucide="user" class="me-2" style="width:24px;height:24px;"></i> {{ $influencer->full_name }}</h4>
    <a href="{{ route('app.influencers.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <h6>Application Details</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $influencer->email }}</dd>
                    <dt class="col-sm-4">Monthly Reach</dt><dd class="col-sm-8">{{ number_format($influencer->monthly_reach) }}</dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge bg-{{ $influencer->status->value === 'approved' ? 'success' : ($influencer->status->value === 'pending' ? 'warning' : 'danger') }}">{{ $influencer->status->label() }}</span></dd>
                    <dt class="col-sm-4">Tier</dt><dd class="col-sm-8">{{ $influencer->commission_tier->label() }} ({{ $influencer->commission_tier->rate() }}%)</dd>
                    <dt class="col-sm-4">Referral Code</dt><dd class="col-sm-8"><code>{{ $influencer->referral_code }}</code></dd>
                    <dt class="col-sm-4">Social Links</dt><dd class="col-sm-8">
                        @foreach(($influencer->social_media_links ?? []) as $link)
                            <a href="{{ $link }}" target="_blank" class="d-block">{{ $link }}</a>
                        @endforeach
                    </dd>
                    <dt class="col-sm-4">Audience</dt><dd class="col-sm-8">{{ $influencer->audience_description ?? '—' }}</dd>
                    <dt class="col-sm-4">Admin Notes</dt><dd class="col-sm-8">{{ $influencer->admin_notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Referrals ({{ $influencer->referrals->count() }})</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($influencer->referrals as $r)
                        <tr>
                            <td>{{ $r->type->value }}</td>
                            <td>${{ number_format($r->commission_amount, 2) }}</td>
                            <td>{{ $r->status->value }}</td>
                            <td><small>{{ $r->created_at->format('M d, Y') }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No referrals</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-body">
                <h6>Stats</h6>
                <p class="mb-1">Total Earnings: <strong>${{ number_format($influencer->total_earnings, 2) }}</strong></p>
                <p class="mb-1">Available: <strong class="text-success">${{ number_format($influencer->available_balance, 2) }}</strong></p>
                <p class="mb-1">Paid Out: <strong>${{ number_format($influencer->paid_out, 2) }}</strong></p>
                <p class="mb-0">Total Referrals: <strong>{{ $influencer->total_referrals }}</strong></p>
            </div>
        </div>

        @if($influencer->status->value === 'pending')
        <div class="card mt-3">
            <div class="card-body">
                <h6>Actions</h6>
                <form method="POST" action="{{ route('app.influencers.approve', $influencer) }}" class="mb-2">
                    @csrf
                    <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Approval notes (optional)"></textarea>
                    <button class="btn btn-success w-100">Approve</button>
                </form>
                <form method="POST" action="{{ route('app.influencers.reject', $influencer) }}">
                    @csrf
                    <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Rejection reason"></textarea>
                    <button class="btn btn-danger w-100">Reject</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
