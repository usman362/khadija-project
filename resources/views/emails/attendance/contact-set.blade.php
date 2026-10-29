@extends('emails.layouts.base')

@section('title', 'On-site contact updated')

@section('content')
{{-- Khadijah's copy, 29 October. The headshot is linked rather than
     embedded: an image in an email is fetched from wherever the reader opens
     it, and this one is behind a sign-in. --}}
<h1>Hi {{ $firstName }},</h1>

<p>
    <b>{{ $pro?->name ?? 'Your professional' }}</b> has designated your primary on-site contact for
    <b>{{ $event?->title ?? 'your event' }}</b> on
    {{ $event?->starts_at?->format('F j, Y \a\t g:i A') ?? 'the event date' }}:
</p>

<div class="details-box">
    <div class="details-row">
        <div class="details-label">Name</div>
        <div class="details-value">{{ $staff?->name ?? 'Not set' }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Role</div>
        <div class="details-value">{{ $staff?->role ?: 'Not given' }}</div>
    </div>
    <div class="details-row">
        <div class="details-label">Phone</div>
        <div class="details-value">{{ $staff?->phone ?: 'Not given' }}</div>
    </div>
</div>

{{-- The line that matters most, and it is not in her draft. Without it this
     email reads as being handed off to somebody else, which is exactly what
     has not happened. Sir Peter's own rule. --}}
<p>
    {{ $pro?->name ?? 'Your professional' }} remains responsible for fulfilling the agreement, even
    when a staff member attends instead.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ $url }}" class="cta-button">View your booking</a>
</p>
@endsection
