@extends('layouts.auth.app')

@section('title', 'Forgot Password')

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
                    <h4 class="mb-4">Forgot your password?</h4>
                    <p class="mb-4 text-secondary">Enter your email address and we'll send you instructions for resetting your password.</p>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="forms-sample">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0 text-white">Send Reset Link</button>
                            <a href="{{ route('login') }}" class="btn btn-link">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
