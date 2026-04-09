@extends('emails.layouts.base')

@section('title', 'Payment Confirmation')

@section('content')
<div class="banner banner-success">
    <span>✓</span> Payment received successfully
</div>

<h1>Thank you, {{ $user->name }}!</h1>

<p>
    We've received your payment and your subscription is now active. Here's your
    transaction receipt for your records:
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Amount Paid</div>
        <div class="details-value details-value-big">{{ strtoupper($payment->currency) }} {{ number_format($payment->amount, 2) }}</div>
    </div>
    @if($plan)
    <div class="details-row">
        <div class="details-label">Plan</div>
        <div class="details-value">{{ $plan->name }}</div>
    </div>
    @endif
    <div class="details-row">
        <div class="details-label">Payment Method</div>
        <div class="details-value">{{ ucfirst($payment->gateway) }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Transaction ID</div>
        <div class="details-value" style="font-size:12px;">{{ $payment->gateway_payment_id ?? $payment->id }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Date</div>
        <div class="details-value">{{ ($payment->completed_at ?? $payment->updated_at)->format('F j, Y \a\t g:i A') }}</div>
    </div>
    @if($subscription?->expires_at)
    <div class="details-row">
        <div class="details-label">Subscription Valid Until</div>
        <div class="details-value">{{ $subscription->expires_at->format('F j, Y') }}</div>
    </div>
    @endif
</div>

<p>
    Your subscription is now <strong>active</strong> and you have full access to all plan features.
    Log in to your dashboard to get started.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/dashboard') }}" class="cta-button">Go to Dashboard</a>
</p>

<p style="margin-top: 24px; font-size: 13px; color: #94a3b8;">
    Please keep this email as proof of purchase. If you have any questions about your
    billing or subscription, contact our support team.
</p>
@endsection
