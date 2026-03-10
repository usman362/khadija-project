@extends('layouts.dashboard')

@section('title', 'Payment Successful')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card text-center">
            <div class="card-body py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" style="width:80px;height:80px">
                        <i data-lucide="check-circle" style="width:48px;height:48px;color:#198754"></i>
                    </div>
                </div>

                <h3 class="mb-2">Payment Successful!</h3>

                @if($payment)
                    <p class="text-muted mb-4">
                        Your subscription to <strong>{{ $payment->subscription?->plan?->name ?? 'Plan' }}</strong> is now active.
                    </p>

                    <div class="bg-light rounded p-3 mb-4 text-start" style="max-width:350px;margin:0 auto">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amount</span>
                            <strong>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Gateway</span>
                            <span>{{ $payment->gatewayLabel() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status</span>
                            <span class="badge bg-{{ $payment->statusColor() }}">{{ $payment->statusLabel() }}</span>
                        </div>
                        @if($payment->gateway_payment_id)
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Reference</span>
                                <code class="small">{{ Str::limit($payment->gateway_payment_id, 24) }}</code>
                            </div>
                        @endif
                    </div>

                    @if($payment->isProcessing())
                        <div class="alert alert-info small">
                            <i data-lucide="loader" style="width:14px;height:14px" class="me-1"></i>
                            Payment is being processed. Your subscription will be activated shortly.
                        </div>
                    @endif
                @else
                    <p class="text-muted mb-4">Your payment has been received and is being processed.</p>
                @endif

                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('app.membership-plans.index') }}" class="btn btn-primary">
                        <i data-lucide="crown" style="width:16px;height:16px" class="me-1"></i>
                        View My Plan
                    </a>
                    <a href="{{ route('app.payments.history') }}" class="btn btn-outline-secondary">
                        Payment History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
