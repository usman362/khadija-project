@extends('emails.layouts.base')

@section('title', 'Assign your on-site contact')

@section('content')
{{-- Khadijah's copy, 29 October, kept as she wrote it. --}}
<h1>Hi {{ $firstName }},</h1>

<p>
    Your upcoming event, <b>{{ $event?->title ?? 'your event' }}</b>, is scheduled for
    {{ $event?->starts_at?->format('F j, Y \a\t g:i A') ?? 'soon' }}, just 24 hours away.
</p>

<p>
    Because your agreement was set to "Decide Later" for attendance, please select and confirm your
    primary on-site contact now so the client has the correct name, photo and direct phone number
    before the event.
</p>

<p style="text-align:center; margin-top: 28px;">
    <a href="{{ $url }}" class="cta-button">Assign On-Site Contact</a>
</p>

<p>
    If you have any questions or need to update event details, you can manage your booking directly
    from your {{ config('brand.name') }} dashboard.
</p>
@endsection
