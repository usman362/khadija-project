@extends('layouts.auth.app')

@section('title', 'Confirm Password')

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
                    <h4 class="mb-2">Confirm Password</h4>
                    <p class="text-secondary mb-4">Please confirm your password before continuing.</p>

                    <form method="POST" action="{{ route('password.confirm') }}" class="forms-sample">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                            @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0 text-white">Confirm Password</button>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="btn btn-link">Forgot password?</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
