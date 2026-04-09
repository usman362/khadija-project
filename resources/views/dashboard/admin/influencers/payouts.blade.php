@extends('layouts.dashboard')
@section('title', 'Payout Requests')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i data-lucide="dollar-sign" class="me-2" style="width:24px;height:24px;"></i> Payout Requests</h4>
    <a href="{{ route('app.influencers.index') }}" class="btn btn-sm btn-outline-secondary">← Influencers</a>
</div>

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="mb-3">
    <a href="{{ route('app.influencers.payouts') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('app.influencers.payouts', ['status'=>'pending']) }}" class="btn btn-sm {{ $status==='pending' ? 'btn-warning' : 'btn-outline-warning' }}">Pending</a>
    <a href="{{ route('app.influencers.payouts', ['status'=>'paid']) }}" class="btn btn-sm {{ $status==='paid' ? 'btn-success' : 'btn-outline-success' }}">Paid</a>
    <a href="{{ route('app.influencers.payouts', ['status'=>'rejected']) }}" class="btn btn-sm {{ $status==='rejected' ? 'btn-danger' : 'btn-outline-danger' }}">Rejected</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Influencer</th><th>Amount</th><th>Method</th><th>Account</th><th>Status</th><th>Requested</th><th></th></tr></thead>
            <tbody>
            @forelse($payouts as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->influencer->full_name }}</td>
                    <td><strong>${{ number_format($p->amount, 2) }}</strong></td>
                    <td>{{ $p->payout_method ?? '—' }}</td>
                    <td><small>{{ $p->payout_account ?? '—' }}</small></td>
                    <td><span class="badge bg-secondary">{{ $p->status->value }}</span></td>
                    <td><small>{{ $p->created_at->format('M d, Y') }}</small></td>
                    <td>
                        @if($p->status->value === 'pending')
                        <form method="POST" action="{{ route('app.influencers.payouts.paid', $p) }}" class="d-inline">
                            @csrf<button class="btn btn-sm btn-success">Mark Paid</button>
                        </form>
                        <form method="POST" action="{{ route('app.influencers.payouts.reject', $p) }}" class="d-inline">
                            @csrf<button class="btn btn-sm btn-danger">Reject</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No payout requests</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payouts->links() }}</div>
@endsection
