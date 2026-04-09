@extends('emails.layouts.base')

@section('title', 'Payout Request Received')

@section('content')
<div class="banner banner-info">
    <span>⏳</span> Your payout request has been received
</div>

<h1>Hi {{ $influencer->full_name ?? 'there' }},</h1>

<p>
    We've received your payout request and it's now being reviewed by our team.
    You'll receive another email as soon as we process it.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Amount</div>
        <div class="details-value details-value-big">{{ strtoupper(config('influencer.currency', 'USD')) }} {{ number_format($payout->amount, 2) }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Payout Method</div>
        <div class="details-value">{{ ucfirst($payout->payout_method ?? 'N/A') }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Request ID</div>
        <div class="details-value">#{{ $payout->id }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Requested On</div>
        <div class="details-value">{{ $payout->created_at->format('F j, Y \a\t g:i A') }}</div>
    </div>
</div>

<p>
    The amount has been <strong>reserved</strong> from your available balance and will be
    released to your account once the payout is processed. Typical processing time is
    <strong>3–7 business days</strong>.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/influencer/payouts') }}" class="cta-button">View Payout Status</a>
</p>

<p style="margin-top: 24px;">
    If you didn't make this request, please contact our support team immediately.
</p>
@endsection
