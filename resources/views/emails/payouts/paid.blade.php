@extends('emails.layouts.base')

@section('title', 'Payout Processed')

@section('content')
<div class="banner banner-success">
    <span>✓</span> Payment sent successfully
</div>

<h1>Great news, {{ $influencer->full_name ?? 'there' }}!</h1>

<p>
    Your payout has been processed and the funds are on their way to your account.
    Please allow a few business days for the funds to appear depending on your payout method.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Amount Paid</div>
        <div class="details-value details-value-big" style="color:#10b981;">{{ strtoupper(config('influencer.currency', 'USD')) }} {{ number_format($payout->amount, 2) }}</div>
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
        <div class="details-label">Processed On</div>
        <div class="details-value">{{ $payout->paid_at?->format('F j, Y \a\t g:i A') ?? now()->format('F j, Y') }}</div>
    </div>
    @if($payout->admin_notes)
    <div class="details-row">
        <div class="details-label">Notes</div>
        <div class="details-value">{{ $payout->admin_notes }}</div>
    </div>
    @endif
</div>

<p>
    Thank you for being part of our influencer program. Keep up the great work!
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/influencer/dashboard') }}" class="cta-button">View Dashboard</a>
</p>
@endsection
