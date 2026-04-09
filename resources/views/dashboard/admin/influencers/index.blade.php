@extends('layouts.dashboard')
@section('title', 'Influencers')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="award" class="me-2" style="width:24px;height:24px;"></i> Influencers</h4>
        <p class="text-secondary mb-0">Manage influencer applications</p>
    </div>
    <a href="{{ route('app.influencers.payouts') }}" class="btn btn-outline-primary"><i data-lucide="dollar-sign" style="width:16px;height:16px;"></i> Payout Requests</a>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="mb-3">
    <a href="{{ route('app.influencers.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('app.influencers.index', ['status'=>'pending']) }}" class="btn btn-sm {{ $status==='pending' ? 'btn-warning' : 'btn-outline-warning' }}">Pending ({{ $counts['pending'] }})</a>
    <a href="{{ route('app.influencers.index', ['status'=>'approved']) }}" class="btn btn-sm {{ $status==='approved' ? 'btn-success' : 'btn-outline-success' }}">Approved ({{ $counts['approved'] }})</a>
    <a href="{{ route('app.influencers.index', ['status'=>'rejected']) }}" class="btn btn-sm {{ $status==='rejected' ? 'btn-danger' : 'btn-outline-danger' }}">Rejected ({{ $counts['rejected'] }})</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Reach</th><th>Tier</th><th>Status</th><th>Earnings</th><th>Applied</th><th></th></tr></thead>
            <tbody>
            @forelse($influencers as $inf)
                <tr>
                    <td>{{ $inf->id }}</td>
                    <td>{{ $inf->full_name }}</td>
                    <td>{{ $inf->email }}</td>
                    <td>{{ number_format($inf->monthly_reach) }}</td>
                    <td><span class="badge bg-info">{{ $inf->commission_tier->label() }}</span></td>
                    <td><span class="badge bg-{{ $inf->status->value === 'approved' ? 'success' : ($inf->status->value === 'pending' ? 'warning' : 'danger') }}">{{ $inf->status->label() }}</span></td>
                    <td>${{ number_format($inf->total_earnings, 2) }}</td>
                    <td><small>{{ $inf->created_at->format('M d, Y') }}</small></td>
                    <td><a href="{{ route('app.influencers.show', $inf) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No influencers found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $influencers->links() }}</div>
@endsection
