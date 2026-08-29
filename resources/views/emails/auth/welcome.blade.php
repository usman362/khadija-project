@extends('emails.layouts.base')

@section('title', 'Welcome to ' . config('app.name'))

@section('content')
<h1>Welcome, {{ $user->name }}.</h1>

@if($isPro)
    <p>Your professional account is ready. Clients post events and you send them proposals.</p>

    <p>Two things worth doing first:</p>
    <ul>
        <li>Fill in your profile and the services you offer — that is what clients search on.</li>
        <li>Set the areas and dates you work, so you only hear about jobs you can take.</li>
    </ul>
@else
    <p>Your account is ready. Tell us about your event and professionals come to you.</p>

    <p>You can:</p>
    <ul>
        <li>Post an event and receive proposals from professionals.</li>
        <li>Browse professionals and message one directly.</li>
    </ul>
@endif

@include('emails.partials.button', ['url' => $ctaUrl, 'label' => $ctaLabel])

<p style="font-size: 13px; color: #64748b;">
    Questions? Reply to this email and it reaches us.
</p>
@endsection
