@extends('errors._layout')

@section('err-code', '419')
@section('err-emoji', '⏱️')
@section('err-title')
    Your session has <span class="grad">expired</span>
@endsection
@section('err-tagline', 'For your security, we sign you out after a period of inactivity. Just refresh the page or log in again to pick up where you left off.')

@section('err-actions')
    <a href="javascript:location.reload()" class="err-btn err-btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Refresh page
    </a>
    @auth
        <a href="{{ url('/dashboard') }}" class="err-btn err-btn-ghost">Go to Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="err-btn err-btn-ghost">Log in again</a>
    @endauth
@endsection
