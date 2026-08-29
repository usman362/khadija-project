@extends('emails.layouts.base')

@section('title', 'Your booking is complete')

@section('content')
<div class="banner banner-success"><span>✓</span> Marked complete</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    <strong>{{ $data['supplier_name'] ?? 'The professional' }}</strong> has marked their work on
    <strong>{{ $data['event_title'] ?? 'your event' }}</strong> as complete.
</p>

<p>If that matches what happened, a review helps the next client choose.</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'View the booking', 'color' => '#10b981'])
@endsection
