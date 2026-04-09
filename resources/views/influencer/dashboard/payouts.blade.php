@extends('layouts.dashboard')
@section('title', 'Payouts')
@section('content')
<h4 class="mb-4"><i data-lucide="dollar-sign" class="me-2" style="width:24px;height:24px;"></i> Payouts</h4>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body">
                <h6>Request a Payout</h6>
                <p class="text-muted small">Available balance: <strong>${{ number_format($influencer->available_balance, 2) }}</strong><br>
                Minimum payout: ${{ number_format($minPayout, 2) }}</p>
                <form method="POST" action="{{ route('influencer.dashboard.payouts.request') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" required class="form-control" max="{{ $influencer->available_balance }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Payout Method</label>
                        <select name="payout_method" class="form-select">
                            <option value="paypal">PayPal</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Account (email/details)</label>
                        <input type="text" name="payout_account" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="user_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Payout History</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Amount</th><th>Method</th><th>Status</th><th>Requested</th></tr></thead>
                    <tbody>
                    @forelse($payouts as $p)
                        <tr>
                            <td><strong>${{ number_format($p->amount, 2) }}</strong></td>
                            <td>{{ $p->payout_method ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $p->status->value }}</span></td>
                            <td><small>{{ $p->created_at->format('M d, Y') }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No payout requests yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">{{ $payouts->links() }}</div>
@endsection
