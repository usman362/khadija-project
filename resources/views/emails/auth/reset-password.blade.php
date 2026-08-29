@extends('emails.layouts.base')

@section('title', 'Reset your password')

@section('content')
<h1>Hi {{ $user->name }},</h1>

<p>We received a request to reset the password on your {{ config('app.name') }} account.</p>

@include('emails.partials.button', ['url' => $url, 'label' => 'Reset my password'])

<p>This link expires in {{ $minutes }} minutes.</p>

<p style="font-size: 13px; color: #64748b;">
    If you didn't ask for this, you can ignore this email — your password stays as it is.
</p>
@endsection
