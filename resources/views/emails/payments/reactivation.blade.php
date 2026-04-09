@extends('emails.layouts.base')

@section('title', 'Account Reactivated')

@section('content')
<div class="banner banner-success">
    <span>✓</span> Welcome back! Your account has been restored
</div>

<h1>Welcome back, {{ $user->name }}!</h1>

<p>
    Your account reactivation payment has been processed successfully and your
    account is now <strong>fully restored</strong>. The scheduled deletion has been cancelled
    and you can continue using {{ config('app.name') }} as before.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Amount Paid</div>
        <div class="details-value details-value-big" style="color:#10b981;">{{ strtoupper($payment->currency) }} {{ number_format($payment->amount, 2) }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Payment Method</div>
        <div class="details-value">{{ ucfirst($payment->gateway) }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Transaction ID</div>
        <div class="details-value" style="font-size:12px;">{{ $payment->gateway_payment_id ?? $payment->id }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Reactivated On</div>
        <div class="details-value">{{ ($payment->completed_at ?? now())->format('F j, Y \a\t g:i A') }}</div>
    </div>
</div>

<p>
    All your data — profile, bookings, messages, and history — are safe and
    exactly where you left them. Your subscription and account settings remain unchanged.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/dashboard') }}" class="cta-button">Go to Dashboard</a>
</p>

<p style="margin-top: 24px; font-size: 13px; color: #94a3b8;">
    Please keep this email as proof of your reactivation payment.
</p>
@endsection
