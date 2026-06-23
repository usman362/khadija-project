@extends('emails.layouts.base')

@section('title', 'Application Received')

@section('content')
<div class="banner banner-info">
    <span>⏳</span> Application under review
</div>

<h1>Thanks for applying, {{ $influencer->full_name ?? 'there' }}!</h1>

<p>
    We've received your application to join the {{ config('app.name') }} affiliate program.
    Our team will review your details and get back to you by email as soon as your account is approved.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Name</div>
        <div class="details-value">{{ $influencer->full_name }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Email</div>
        <div class="details-value">{{ $influencer->email }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Status</div>
        <div class="details-value" style="color:#b45309;">Pending review</div>
    </div>
</div>

<p>
    Once approved, you'll be able to log in, grab your unique referral link, and start earning commissions.
    No further action is needed from you right now.
</p>

<p style="color:#64748b;font-size:13px;">
    If you didn't apply for this, you can safely ignore this email.
</p>
@endsection
