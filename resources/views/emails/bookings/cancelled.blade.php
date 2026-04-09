@extends('emails.layouts.base')

@section('title', 'Booking Cancelled')

@section('content')
<div class="banner banner-warning">
    <span>⚠</span> A booking has been cancelled
</div>

<h1>Hi {{ $recipient->name }},</h1>

<p>
    We're writing to let you know that the following booking has been cancelled
    @if($cancelledBy && $cancelledBy->id !== $recipient->id)
        by <strong>{{ $cancelledBy->name }}</strong>.
    @else
        .
    @endif
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Booking ID</div>
        <div class="details-value">#{{ $booking->id }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Event</div>
        <div class="details-value">{{ $booking->event?->title ?? 'N/A' }}</div>
    </div>
    @if($booking->client)
    <div class="details-row">
        <div class="details-label">Client</div>
        <div class="details-value">{{ $booking->client->name }}</div>
    </div>
    @endif
    @if($booking->supplier)
    <div class="details-row">
        <div class="details-label">Professional</div>
        <div class="details-value">{{ $booking->supplier->name }}</div>
    </div>
    @endif
    @if($booking->price)
    <div class="details-row">
        <div class="details-label">Amount</div>
        <div class="details-value">{{ $booking->currency ?? 'USD' }} {{ number_format($booking->price, 2) }}</div>
    </div>
    @endif
    <div class="details-row">
        <div class="details-label">Cancelled On</div>
        <div class="details-value">{{ now()->format('F j, Y \a\t g:i A') }}</div>
    </div>
    @if($reason)
    <div class="details-row">
        <div class="details-label">Reason</div>
        <div class="details-value">{{ $reason }}</div>
    </div>
    @endif
</div>

<p>
    If this cancellation was unexpected or you have any questions, please
    reach out to the other party via our platform messaging or contact our
    support team.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ url('/dashboard') }}" class="cta-button">Go to Dashboard</a>
</p>
@endsection
