@extends('layouts.dashboard')
@section('title', 'My Referrals')
@section('content')
<h4 class="mb-4"><i data-lucide="users" class="me-2" style="width:24px;height:24px;"></i> My Referrals</h4>
<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Type</th><th>Base Amount</th><th>Rate</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($referrals as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->type->value }}</td>
                    <td>${{ number_format($r->base_amount, 2) }}</td>
                    <td>{{ $r->commission_rate }}%</td>
                    <td><strong>${{ number_format($r->commission_amount, 2) }}</strong></td>
                    <td><span class="badge bg-secondary">{{ $r->status->value }}</span></td>
                    <td><small>{{ $r->created_at->format('M d, Y') }}</small></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No referrals yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $referrals->links() }}</div>
@endsection
