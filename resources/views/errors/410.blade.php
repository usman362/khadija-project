{{-- A request that has closed.

     The bid wizard has always answered 410 for a request that is finished,
     cancelled, or past the point where it takes proposals. There was no page
     for it, so it came out as Laravel's bare exception screen. 13 November. --}}
@extends('errors._layout')

@section('err-code', '410')
@section('err-emoji', '📪')
@section('err-title')
    This request is <span class="grad">no longer open</span>
@endsection
@section('err-tagline', 'It has closed, or it is not taking proposals any more. Open gigs are on the bidding board.')

@section('err-actions')
    @auth
        <a href="{{ url('/professional/bidding-board') }}" class="err-btn err-btn-primary">
            Find open gigs
        </a>
        <a href="{{ url('/dashboard') }}" class="err-btn err-btn-ghost">Go to Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="err-btn err-btn-primary">Log in</a>
        <a href="{{ url('/') }}" class="err-btn err-btn-ghost">Back to Home</a>
    @endauth
@endsection
