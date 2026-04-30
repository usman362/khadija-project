@extends('errors._layout')

@section('err-code', '503')
@section('err-emoji', '🚧')
@section('err-title')
    We're getting <span class="grad">a quick tune-up</span>
@endsection
@section('err-tagline', 'GigResource is briefly down for maintenance to bring you something even better. We\'ll be back in a few minutes — thanks for your patience.')

@section('err-actions')
    <a href="javascript:location.reload()" class="err-btn err-btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Try again
    </a>
@endsection
