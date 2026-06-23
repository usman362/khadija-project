@extends('layouts.auth-pro')

@section('apRole', 'influencer')
@section('title', 'Become an Affiliate')
@section('top-right')
    Already have an account? <a href="{{ route('login.affiliate') }}">Log In</a>
@endsection

@section('auth_form')
    <h2>Create Your Influencer (Affiliate) Account</h2>
    <div class="apx-card-sub">Tell us about your audience to get started.</div>

    {{-- OAuth (UI placeholder — backend not wired yet) --}}
    <div class="apx-oauth">
        <button type="button" class="apx-oauth-btn" disabled title="Coming soon">
            <span class="apx-soon">Soon</span>
            <svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continue with Google
        </button>
        <button type="button" class="apx-oauth-btn" disabled title="Coming soon">
            <span class="apx-soon">Soon</span>
            <svg viewBox="0 0 24 24" fill="#000"><path d="M16.36 1.43c.05 1.05-.37 2.06-1.06 2.81-.74.79-1.93 1.4-3.05 1.31-.06-1.05.42-2.12 1.07-2.79.73-.78 2.01-1.36 3.04-1.33zM20.5 17.2c-.55 1.27-.82 1.84-1.53 2.96-.99 1.57-2.39 3.52-4.12 3.53-1.54.02-1.94-1-4.03-.99-2.09.01-2.53 1.01-4.07.99-1.73-.02-3.06-1.78-4.05-3.34C-.04 16.43-.34 11.34 1.36 8.64 2.56 6.71 4.46 5.58 6.25 5.58c1.82 0 2.97 1 4.48 1 1.46 0 2.35-1 4.46-1 1.59 0 3.28.87 4.48 2.37-3.94 2.16-3.3 7.78.83 9.25z"/></svg>
            Continue with Apple
        </button>
    </div>

    <div class="apx-or">OR</div>

    @if (session('status'))
        <div class="apx-alert apx-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="apx-alert apx-alert-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="apx-alert apx-alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('influencer.join.submit') }}" method="POST">
        @csrf

        <div class="apx-field">
            <label class="apx-label">Full Name</label>
            <div class="apx-input-wrap">
                <svg class="apx-ic-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="full_name" class="apx-input {{ $errors->has('full_name') ? 'is-invalid' : '' }}" value="{{ old('full_name') }}" placeholder="Enter your full name" required
                       data-validate="required|min:2" data-error-required="Please enter your full name.">
            </div>
        </div>

        <div class="apx-field">
            <label class="apx-label">Email Address</label>
            <div class="apx-input-wrap">
                <svg class="apx-ic-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
                <input type="email" name="email" class="apx-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" placeholder="Enter your email address" required
                       data-validate="required|email" data-error-required="We need an email to reach you." data-error-email="That doesn't look like a valid email.">
            </div>
        </div>

        <div class="apx-field">
            <label class="apx-label">Password</label>
            <div class="apx-input-wrap">
                <svg class="apx-ic-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password" id="apxPw" class="apx-input {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Create a strong password" required
                       data-validate="required|min:8" data-error-required="Please create a password." data-error-min="Use at least 8 characters.">
                <button type="button" class="apx-eye" data-eye="apxPw" aria-label="Show or hide password">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <div class="apx-hint">Minimum 8 characters — you'll use this to log in.</div>
        </div>

        <div class="apx-field">
            <label class="apx-label">Confirm Password</label>
            <div class="apx-input-wrap">
                <svg class="apx-ic-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password_confirmation" id="apxPw2" class="apx-input" placeholder="Confirm your password" required
                       data-validate="required|match:password" data-error-required="Please confirm your password." data-error-match="Passwords don't match.">
                <button type="button" class="apx-eye" data-eye="apxPw2" aria-label="Show or hide password">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <div class="apx-field">
            <label class="apx-label">Social Media Links</label>
            <div class="apx-input-wrap">
                <svg class="apx-ic-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <input type="text" name="social_media_links" class="apx-input {{ $errors->has('social_media_links') ? 'is-invalid' : '' }}" value="{{ old('social_media_links') }}" placeholder="Instagram, YouTube, TikTok URLs (comma separated)">
            </div>
        </div>

        <div class="apx-field">
            <label class="apx-label">Tell us about your audience</label>
            <textarea name="audience_description" rows="4" class="apx-textarea {{ $errors->has('audience_description') ? 'is-invalid' : '' }}" placeholder="Who are your followers? What content do you create?">{{ old('audience_description') }}</textarea>
        </div>

        <div class="apx-field">
            <label class="apx-label">Monthly Reach</label>
            <input type="number" name="monthly_reach" min="0" class="apx-input no-ic {{ $errors->has('monthly_reach') ? 'is-invalid' : '' }}" value="{{ old('monthly_reach') }}" placeholder="e.g. 50000">
        </div>

        <label class="apx-agree">
            <input type="checkbox" name="agree" value="1" {{ old('agree') ? 'checked' : '' }} required>
            <span>I agree to the <a href="{{ route('platform-disclaimer') }}" target="_blank">Terms of Service</a> and <a href="{{ route('privacy-policy') }}" target="_blank">Privacy Policy</a></span>
        </label>

        <button type="submit" class="apx-submit">Create Account &amp; Apply</button>
    </form>

    <div class="apx-foot">
        Looking to hire or get hired instead? <a href="{{ route('register') }}">Create a standard account</a>
    </div>
@endsection
