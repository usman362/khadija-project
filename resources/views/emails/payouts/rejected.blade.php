@extends('emails.layouts.base')

@section('title', 'Payout Request Declined')

@section('content')
<div class="banner banner-danger">
    <span>✕</span> Your payout request has been declined
</div>

<h1>Hi {{ $influencer->full_name ?? 'there' }},</h1>

<p>
    Unfortunately, your recent payout request could not be processed at this time.
    The reserved amount has been <strong>returned to your available balance</strong>,
    so no funds have been lost.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Amount</div>
        <div class="details-value details-value-big">{{ strtoupper(config('influencer.currency', 'USD')) }} {{ number_format($payout->amount, 2) }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Request ID</div>
        <div class="details-value">#{{ $payout->id }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Reviewed On</div>
        <div class="details-value">{{ $payout->processed_at?->format('F j, Y') ?? now()->format('F j, Y') }}</div>
    </div>
    @if($payout->admin_notes)
    <div class="details-row">
        <div class="details-label">Reason</div>
        <div class="details-value">{{ $payout->admin_notes }}</div>
    </div>
    @endif
</div>

<p>
    You can submit a new payout request from your dashboard anytime. If you believe this
    was a mistake or need more information, please reach out to our support team.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/influencer/payouts') }}" class="cta-button">Request Again</a>
</p>
@endsection
