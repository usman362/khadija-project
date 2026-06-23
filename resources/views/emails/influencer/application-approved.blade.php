@extends('emails.layouts.base')

@section('title', 'Application Approved')

@section('content')
<div class="banner banner-success">
    <span>✓</span> Your affiliate account is approved
</div>

<h1>Welcome aboard, {{ $influencer->full_name ?? 'there' }}! 🎉</h1>

<p>
    Great news — your application to the {{ config('app.name') }} affiliate program has been
    <strong>approved</strong>. You can now log in and start earning.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Your referral code</div>
        <div class="details-value details-value-big">{{ $influencer->referral_code }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Starting tier</div>
        <div class="details-value">{{ $influencer->commission_tier->label() }} ({{ rtrim(rtrim(number_format($influencer->commission_tier->rate(), 1), '0'), '.') }}% commission)</div>
    </div>
</div>

<p style="text-align:center;">
    <a href="{{ $loginUrl }}" class="cta-button">Log In to Your Dashboard</a>
</p>

<p>
    Inside your portal you'll find your referral link, marketing assets, performance analytics, and more.
    Share your link, refer new members, and watch your earnings grow.
</p>

<p style="color:#64748b;font-size:13px;">
    Log in with the email and password you chose when applying.
</p>
@endsection
