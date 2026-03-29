<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log In - GigResource</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-dark: #0b0f1a;
            --bg-card: #151d35;
            --bg-input: #1a2440;
            --text-white: #ffffff;
            --text-light: #c8cdd8;
            --text-muted: #7a829a;
            --border-color: #1e2d4a;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #8b5cf6;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
            --success: #22c55e;
            --orange: #f97316;
            --radius: 12px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { text-decoration: none; color: inherit; }

        /* ── NAVBAR ── */
        .auth-navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(11, 15, 26, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            height: 64px;
            display: flex; align-items: center;
            padding: 0 32px;
        }
        .auth-navbar-inner {
            max-width: 1200px; margin: 0 auto; width: 100%;
            display: flex; align-items: center; justify-content: space-between;
        }
        .auth-logo {
            display: flex; align-items: center;
        }
        .auth-logo img { height: 34px; }
        .auth-nav-links { display: flex; gap: 20px; align-items: center; }
        .auth-nav-link {
            font-size: 14px; color: var(--text-muted); font-weight: 500; transition: color 0.2s;
        }
        .auth-nav-link:hover { color: var(--text-white); }
        .auth-nav-btn {
            padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
            border: 1.5px solid var(--orange); color: var(--orange); background: transparent;
            transition: all 0.2s;
        }
        .auth-nav-btn:hover { background: var(--orange); color: #fff; }

        /* ── MAIN LAYOUT ── */
        .login-container {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 100px 24px 40px;
        }
        .login-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0;
            max-width: 960px; width: 100%; border-radius: 16px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        @media (max-width: 800px) {
            .login-grid { grid-template-columns: 1fr; max-width: 480px; }
            .login-image-side { display: none; }
        }

        /* ── IMAGE SIDE ── */
        .login-image-side {
            position: relative; min-height: 560px;
            background: url('https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80') center/cover;
        }
        .login-image-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(11,15,26,0.3) 0%, rgba(11,15,26,0.85) 100%);
            display: flex; flex-direction: column; justify-content: flex-end;
            padding: 40px 32px;
        }
        .login-image-overlay h2 {
            font-size: 1.5rem; font-weight: 800; margin-bottom: 8px;
        }
        .login-image-overlay p {
            font-size: 14px; color: var(--text-light); line-height: 1.6; margin-bottom: 16px;
        }
        .login-image-badges {
            display: flex; gap: 20px;
        }
        .login-image-badge {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--text-light);
        }
        .login-image-badge:nth-child(1) svg { color: #22c55e; filter: drop-shadow(0 0 4px rgba(34,197,94,0.4)); }
        .login-image-badge:nth-child(2) svg { color: #f59e0b; filter: drop-shadow(0 0 4px rgba(245,158,11,0.4)); }

        /* ── FORM SIDE ── */
        .login-form-side {
            background: var(--bg-card); padding: 48px 40px;
            display: flex; flex-direction: column; justify-content: center;
        }
        .login-form-side h2 {
            font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;
        }
        .login-form-subtitle {
            font-size: 14px; color: var(--text-muted); margin-bottom: 32px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--text-light); margin-bottom: 6px;
        }
        .form-input-wrap {
            position: relative;
        }
        .form-input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--primary); pointer-events: none; opacity: 0.8;
        }
        .form-group:nth-child(1) .form-input-icon { color: #3b82f6; }
        .form-group:nth-child(2) .form-input-icon { color: #f59e0b; }
        .form-input:focus ~ .form-input-icon, .form-input-wrap:focus-within .form-input-icon { opacity: 1; }
        .form-input {
            width: 100%; padding: 12px 16px 12px 42px; border-radius: 10px;
            border: 1.5px solid var(--border-color); background: var(--bg-input);
            color: var(--text-white); font-size: 14px; font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-input.no-icon { padding-left: 16px; }
        .form-input::placeholder { color: var(--text-muted); }
        .form-input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .form-input.is-invalid { border-color: #ef4444; }
        .invalid-msg { color: #ef4444; font-size: 12px; margin-top: 4px; }

        .password-toggle-btn {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-muted); cursor: pointer;
        }
        .password-toggle-btn:hover { color: var(--text-light); }

        .form-row-between {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px;
        }
        .form-check {
            display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-light);
        }
        .form-check input[type="checkbox"] {
            width: 16px; height: 16px; accent-color: var(--primary);
        }
        .forgot-link {
            font-size: 13px; color: var(--primary); font-weight: 500;
        }
        .forgot-link:hover { text-decoration: underline; }

        .login-btn {
            width: 100%; padding: 14px; border-radius: 10px; border: none;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff; font-size: 15px; font-weight: 700; cursor: pointer;
            transition: all 0.2s;
        }
        .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .form-divider {
            display: flex; align-items: center; gap: 16px;
            margin: 24px 0; color: var(--text-muted); font-size: 13px;
        }
        .form-divider::before, .form-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border-color);
        }

        .social-buttons {
            display: flex; gap: 12px;
        }
        .social-btn {
            flex: 1; padding: 12px; border-radius: 10px;
            border: 1.5px solid var(--border-color); background: transparent;
            color: var(--text-white); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .social-btn:hover { border-color: var(--primary); background: rgba(59,130,246,0.05); }

        .login-footer {
            text-align: center; margin-top: 28px; font-size: 14px; color: var(--text-muted);
        }
        .login-footer a { color: var(--primary); font-weight: 600; }
        .login-footer a:hover { text-decoration: underline; }

        .auth-alert {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;
            font-size: 13px; font-weight: 500;
        }
        .auth-alert-success {
            background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
        }
        .auth-alert-error {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        @media (max-width: 600px) {
            .login-form-side { padding: 32px 24px; }
            .social-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="auth-navbar">
    <div class="auth-navbar-inner">
        <a href="{{ url('/') }}" class="auth-logo"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource"></a>
        <div class="auth-nav-links">
            <a href="{{ url('/') }}" class="auth-nav-link">Home</a>
            <a href="{{ route('register') }}" class="auth-nav-btn">Sign Up</a>
        </div>
    </div>
</nav>

<!-- ── LOGIN CARD ── -->
<div class="login-container">
    <div class="login-grid">
        <!-- Image Side -->
        <div class="login-image-side">
            <div class="login-image-overlay">
                <h2>Welcome back to GigResource</h2>
                <p>Connect with thousands of professionals and create unforgettable events.</p>
                <div class="login-image-badges">
                    <div class="login-image-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Verified Professionals
                    </div>
                    <div class="login-image-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Secure Platform
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Side -->
        <div class="login-form-side">
            <h2>Sign In</h2>
            <div class="login-form-subtitle">Welcome back! Please sign in to your account</div>

            @if (session('status'))
                <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="auth-alert auth-alert-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="form-input-wrap">
                        <div class="form-input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
                        </div>
                        <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="form-input-wrap">
                        <div class="form-input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <input type="password" name="password" id="login-password" class="form-input" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle-btn" onclick="toggleLoginPw()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-row-between">
                    <label class="form-check">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <div class="form-divider">Or continue with</div>

            <div class="social-buttons">
                <button type="button" class="social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Google
                </button>
                <button type="button" class="social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </button>
            </div>

            <div class="login-footer">
                Don't have an account? <a href="{{ route('register') }}">Sign up for free</a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLoginPw() {
    const input = document.getElementById('login-password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
