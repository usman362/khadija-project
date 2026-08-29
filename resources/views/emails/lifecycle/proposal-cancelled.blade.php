@extends('emails.layouts.base')

@section('title', 'A professional has withdrawn')

@section('content')
<div class="banner banner-warning"><span>⚠</span> A professional has stepped away</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    <strong>{{ $data['actor_name'] ?? 'A professional' }}</strong> has withdrawn from
    <strong>{{ $data['event_title'] ?? 'your event' }}</strong>.
</p>

<p>Your event is still open, and you can review whoever else has proposed.</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'See my event'])
@endsection
