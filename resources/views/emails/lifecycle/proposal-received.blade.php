@extends('emails.layouts.base')

@section('title', 'You have a new proposal')

@section('content')
<div class="banner banner-info"><span>✉</span> A professional has replied to your event</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    <strong>{{ $data['supplier_name'] ?? 'A professional' }}</strong> sent a proposal for
    <strong>{{ $data['event_title'] ?? 'your event' }}</strong>.
</p>

<p>Open it to see what they're offering and what they'd charge. Nothing is booked until you accept.</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'View the proposal'])
@endsection
