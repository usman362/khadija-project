@extends('errors._layout')

@section('err-code', '403')
@section('err-emoji', '🔒')
@section('err-title')
    You don't have <span class="grad">access to this page</span>
@endsection
@section('err-tagline', 'This area is restricted. If you think you should have access, please log in with the right account or contact support.')

@section('err-actions')
    @auth
        <a href="{{ url('/dashboard') }}" class="err-btn err-btn-primary">
            Go to Dashboard
        </a>
        <a href="{{ url('/') }}" class="err-btn err-btn-ghost">Back to Home</a>
    @else
        <a href="{{ route('login') }}" class="err-btn err-btn-primary">Log in</a>
        <a href="{{ url('/') }}" class="err-btn err-btn-ghost">Back to Home</a>
    @endauth
@endsection
