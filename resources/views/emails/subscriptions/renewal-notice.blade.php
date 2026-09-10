{{--
    The renewal notice.

    Washington DC, Automatic Renewal Protections Act of 2018. It says the four
    things the law is actually about: that it will renew, when, what it will
    cost, and how to stop it.

    No marketing and no unsubscribe link. This is a notice we are required to
    send, and offering to stop it would be offering to break the law on request.
--}}
@extends('emails.layouts.base')

@section('title', 'Your membership renews soon')

@section('content')
<h1>Your membership renews {{ $daysBefore === 1 ? 'tomorrow' : 'in ' . $daysBefore . ' days' }}</h1>

<p>
    Hello {{ $subscription->user?->name ?? 'there' }}, this is a reminder that your
    <strong>{{ $plan?->name ?? 'GigResource' }}</strong> membership renews automatically.
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Renews on</div>
        <div class="details-value details-value-big">{{ $renewsOn?->format('F j, Y') }}</div>
    </div>
    @if($plan?->price)
        <div class="details-row">
            <div class="details-label">Amount</div>
            <div class="details-value">${{ number_format((float) $plan->price, 2) }}@if($plan->billing_cycle) per {{ $plan->billing_cycle }}@endif</div>
        </div>
    @endif
    <div class="details-row">
        <div class="details-label">Membership</div>
        <div class="details-value">{{ $plan?->name ?? '—' }}</div>
    </div>
</div>

<p>
    <strong>If you do not want it to renew,</strong> you can cancel it yourself at any time
    before that date. It takes one step from your membership page. You do not need to call
    or email anybody.
</p>

<p style="text-align:center;margin:26px 0;">
    <a href="{{ $cancelUrl }}" class="cta-button">Manage my membership</a>
</p>

<p>If you are happy to continue, there is nothing to do.</p>

<p style="font-size:12px;color:#6b7280;line-height:1.6;margin-top:26px;">
    You are receiving this because you have an active paid membership. Renewal reminders
    are a legal requirement and are sent to every member. They are not marketing, and they
    cannot be switched off while a membership is active.
</p>
@endsection
