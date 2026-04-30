@extends('errors._layout')

@section('err-code', '404')
@section('err-emoji', '🧭')
@section('err-title')
    The page you're looking for is <span class="grad">missing in action</span>
@endsection
@section('err-tagline', 'The link might be broken, or the page may have been moved. Don\'t worry — your event-planning journey can pick up right where you left off.')

@section('err-helpful')
    <div class="err-helpful-links">
        <a href="{{ route('public.browse') }}">Browse Professionals</a>
        <a href="{{ route('events-categories') }}">All Categories</a>
        <a href="{{ route('public.how-it-works') }}">How It Works</a>
        <a href="{{ route('public.faq') }}">FAQ</a>
        <a href="{{ route('blog.index') }}">Blog</a>
        <a href="{{ route('about-us') }}">About Us</a>
    </div>
@endsection
