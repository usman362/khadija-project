@extends('layouts.auth.app')

@section('title', 'Reset Password')

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
                    <h4 class="mb-4">Reset your password</h4>

                    <form method="POST" action="{{ route('password.update') }}" class="forms-sample">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="New Password">
                            @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">Confirm Password</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm Password">
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0 text-white">Reset Password</button>
                            <a href="{{ route('login') }}" class="btn btn-link">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
