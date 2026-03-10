@extends('layouts.dashboard')

@section('title', 'Payment History')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="mb-0">Payment History</h4>
</div>

<div class="card">
    <div class="card-body">
        @if($payments->isEmpty())
            <div class="text-center text-muted py-5">
                <i data-lucide="receipt" style="width:48px;height:48px;opacity:0.3"></i>
                <p class="mt-3">No payments yet.</p>
                <a href="{{ route('app.membership-plans.index') }}" class="btn btn-sm btn-primary">Browse Plans</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $payment->subscription?->plan?->name ?? '—' }}</td>
                                <td><strong>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</strong></td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $payment->gatewayLabel() }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $payment->statusColor() }}">{{ $payment->statusLabel() }}</span>
                                </td>
                                <td>
                                    @if($payment->gateway_payment_id)
                                        <code class="small">{{ Str::limit($payment->gateway_payment_id, 20) }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
