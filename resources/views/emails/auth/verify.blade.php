@extends('emails.layouts.base')

@section('title', 'Confirm your email address')

@section('content')
<h1>Hi {{ $user->name }},</h1>

<p>Thanks for joining {{ config('app.name') }}. Please confirm this is your email address.</p>

@include('emails.partials.button', ['url' => $url, 'label' => 'Confirm my email'])

@if($blocking)
    <p>You'll need to confirm before you can post an event or contact a professional.</p>
@else
    <p>You can start looking around straight away — confirming just means we can reach you about your events.</p>
@endif

<p style="font-size: 13px; color: #64748b;">
    If you didn't create an account, you can ignore this email and nothing further will happen.
</p>
@endsection
