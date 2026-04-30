@extends('errors._layout')

@section('err-code', '401')
@section('err-emoji', '🔐')
@section('err-title')
    You need to be <span class="grad">logged in</span>
@endsection
@section('err-tagline', 'This page is only available to signed-in users. Log in or create an account to continue.')

@section('err-actions')
    <a href="{{ route('login') }}" class="err-btn err-btn-primary">Log in</a>
    <a href="{{ route('register') }}" class="err-btn err-btn-ghost">Create account</a>
@endsection
