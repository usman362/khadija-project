@extends('layouts.auth.app')

@section('title', 'Verify Email')

@section('auth_content')
<div class="col-md-10 col-lg-8 col-xl-6 mx-auto">
    <div class="card">
        <div class="row">
            <div class="col-md-4 pe-md-0">
                <div class="auth-side-wrapper"></div>
            </div>
            <div class="col-md-8 ps-md-0">
                <div class="auth-form-wrapper px-4 py-5">
                    <a href="{{ url('/') }}" class="nobleui-logo d-block mb-2">{{ config('app.name', 'App') }}</a>
                    <h4 class="mb-2">Verify your email</h4>
                    <p class="text-secondary mb-4">Before proceeding, please check your email for a verification link.</p>

                    @if (session('resent'))
                        <div class="alert alert-success">A fresh verification link has been sent to your email address.</div>
                    @endif

                    <form method="POST" action="{{ route('verification.resend') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0 text-white">Resend verification email</button>
                    </form>

                    <a href="{{ route('home') }}" class="btn btn-link">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
