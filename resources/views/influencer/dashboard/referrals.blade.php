@extends('layouts.dashboard')
@section('title', 'My Referrals')
@section('content')
{{-- Row 113 — this is the whole list, at every status. The panels elsewhere
     show the most recent few of the same list; saying so is what stops them
     reading as three different datasets. --}}
<h4 class="mb-1"><i data-lucide="users" class="me-2" style="width:24px;height:24px;"></i> My Referrals</h4>
<p class="text-muted mb-4" style="font-size:13px;">Every referral you have made, at every status — {{ $referrals->total() }} in total.</p>
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
