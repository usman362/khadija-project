@extends('errors._layout')

@section('err-code', '429')
@section('err-emoji', '🛑')
@section('err-title')
    Whoa, <span class="grad">slow down a bit</span>
@endsection
@section('err-tagline', 'You\'ve sent too many requests in a short window. Please wait a minute and try again — this is just a safety guardrail to protect the platform.')

@section('err-actions')
    <a href="javascript:setTimeout(()=>location.reload(),60000);location.reload();" class="err-btn err-btn-primary">
        Try again
    </a>
    <a href="{{ url('/') }}" class="err-btn err-btn-ghost">Back to Home</a>
@endsection
