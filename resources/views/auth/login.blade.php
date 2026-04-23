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

        /* ── TESTIMONIAL SIDE ── */
        .login-image-side {
            position: relative; min-height: 560px;
            background:
                radial-gradient(1200px 600px at 0% 0%, rgba(59,130,246,0.18) 0%, transparent 55%),
                radial-gradient(900px 500px at 100% 100%, rgba(139,92,246,0.20) 0%, transparent 60%),
                linear-gradient(160deg, #101832 0%, #0b0f1a 100%);
            overflow: hidden;
            display: flex; flex-direction: column;
            padding: 44px 36px 32px;
            color: #fff;
        }
        .login-image-side::before {
            content: ''; position: absolute; top: -40%; right: -30%;
            width: 420px; height: 420px; border-radius: 50%;
            background: radial-gradient(circle, rgba(139,92,246,0.35), transparent 70%);
            filter: blur(40px); pointer-events: none;
        }
        .login-image-side::after {
            content: ''; position: absolute; bottom: -30%; left: -20%;
            width: 380px; height: 380px; border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.30), transparent 70%);
            filter: blur(40px); pointer-events: none;
        }
        .ti-brand-row {
            position: relative; z-index: 1;
            display: flex; align-items: center; gap: 10px;
            font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75);
            letter-spacing: 0.8px; text-transform: uppercase;
        }
        .ti-brand-row .ti-brand-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 0 8px rgba(139,92,246,0.6);
        }
        .ti-headline {
            position: relative; z-index: 1;
            font-size: 1.55rem; font-weight: 800; line-height: 1.25;
            margin: 14px 0 18px;
        }
        .ti-headline .grad {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .ti-carousel {
            position: relative; z-index: 1; flex: 1;
            display: flex; flex-direction: column; justify-content: center;
        }
        .ti-stage {
            position: relative; min-height: 220px;
        }
        .ti-slide {
            position: absolute; inset: 0;
            opacity: 0; transform: translateY(8px);
            transition: opacity 0.45s ease, transform 0.45s ease;
            pointer-events: none;
        }
        .ti-slide.is-active { opacity: 1; transform: none; pointer-events: auto; }
        .ti-stars {
            color: #fbbf24; font-size: 15px; letter-spacing: 2px;
            filter: drop-shadow(0 0 6px rgba(251,191,36,0.35));
            margin-bottom: 12px;
        }
        .ti-quote {
            font-size: 1rem; line-height: 1.65; color: #e8ebf3;
            font-weight: 500; margin-bottom: 20px;
        }
        .ti-quote::before {
            content: '"'; font-family: Georgia, serif;
            font-size: 3rem; line-height: 0; color: rgba(139,92,246,0.45);
            vertical-align: -0.45em; margin-right: 4px; font-weight: 900;
        }
        .ti-person {
            display: flex; align-items: center; gap: 12px;
        }
        .ti-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; color: #fff;
            box-shadow: 0 6px 16px rgba(0,0,0,0.35), 0 0 0 2px rgba(255,255,255,0.08);
        }
        .ti-avatar.bg-0 { background: linear-gradient(135deg, #f97316, #f59e0b); }
        .ti-avatar.bg-1 { background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .ti-avatar.bg-2 { background: linear-gradient(135deg, #22c55e, #059669); }
        .ti-person-meta { display: flex; flex-direction: column; gap: 2px; }
        .ti-person-name { font-size: 14px; font-weight: 700; color: #fff; }
        .ti-person-role { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .ti-person-role .dot { display: inline-block; width: 3px; height: 3px; border-radius: 50%; background: var(--text-muted); vertical-align: middle; margin: 0 6px; }

        .ti-dots {
            position: relative; z-index: 1;
            display: flex; gap: 8px; margin-top: 18px;
        }
        .ti-dot {
            width: 22px; height: 4px; border-radius: 2px;
            background: rgba(255,255,255,0.18); border: none; cursor: pointer;
            transition: background 0.3s, width 0.3s;
        }
        .ti-dot:hover { background: rgba(255,255,255,0.3); }
        .ti-dot.is-active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            width: 36px;
        }

        .ti-stats {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 12px; margin-top: 28px; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .ti-stat-value {
            font-size: 1.1rem; font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }
        .ti-stat-label {
            font-size: 11px; color: var(--text-muted); font-weight: 500;
            letter-spacing: 0.3px; margin-top: 4px;
        }

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
    @php
        $recaptchaSettings = app(\App\Domain\Settings\Services\SettingsService::class);
        $showRecaptcha = $recaptchaSettings->isRecaptchaEnabledFor('login');
        $recaptchaSiteKey = $recaptchaSettings->getRecaptchaSiteKey();
        $recaptchaVersion = $recaptchaSettings->get('recaptcha.version', 'v2');
    @endphp
    @if($showRecaptcha && $recaptchaSiteKey)
        @if($recaptchaVersion === 'v3')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
        @else
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif
    @endif
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
        <!-- Testimonial Side -->
        <div class="login-image-side">
            <div class="ti-brand-row">
                <span class="ti-brand-dot"></span> Trusted by 10,000+ pros
            </div>

            <h2 class="ti-headline">Welcome back —<br>your next <span class="grad">great booking</span> is waiting.</h2>

            <div class="ti-carousel" id="tiCarousel">
                <div class="ti-stage">
                    <div class="ti-slide is-active">
                        <div class="ti-stars">★★★★★</div>
                        <p class="ti-quote">Found a wedding photographer in under an hour. The verified badge gave me confidence and the final photos were stunning.</p>
                        <div class="ti-person">
                            <div class="ti-avatar bg-0">EB</div>
                            <div class="ti-person-meta">
                                <div class="ti-person-name">Emma Bennett</div>
                                <div class="ti-person-role">Client<span class="dot"></span>Austin, TX</div>
                            </div>
                        </div>
                    </div>
                    <div class="ti-slide">
                        <div class="ti-stars">★★★★★</div>
                        <p class="ti-quote">GigResource tripled my bookings in three months. The review system is honest and clients come in already informed.</p>
                        <div class="ti-person">
                            <div class="ti-avatar bg-1">MC</div>
                            <div class="ti-person-meta">
                                <div class="ti-person-name">Marcus Cole</div>
                                <div class="ti-person-role">Event DJ<span class="dot"></span>Nashville, TN</div>
                            </div>
                        </div>
                    </div>
                    <div class="ti-slide">
                        <div class="ti-stars">★★★★★</div>
                        <p class="ti-quote">Secure payments, real verification, zero drama. This is how every marketplace should run.</p>
                        <div class="ti-person">
                            <div class="ti-avatar bg-2">SR</div>
                            <div class="ti-person-meta">
                                <div class="ti-person-name">Sophia Rivera</div>
                                <div class="ti-person-role">Caterer<span class="dot"></span>Miami, FL</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ti-dots" role="tablist">
                    <button type="button" class="ti-dot is-active" data-ti="0" aria-label="Testimonial 1"></button>
                    <button type="button" class="ti-dot" data-ti="1" aria-label="Testimonial 2"></button>
                    <button type="button" class="ti-dot" data-ti="2" aria-label="Testimonial 3"></button>
                </div>
            </div>

            <div class="ti-stats">
                <div>
                    <div class="ti-stat-value">10,000+</div>
                    <div class="ti-stat-label">Verified Pros</div>
                </div>
                <div>
                    <div class="ti-stat-value">4.8★</div>
                    <div class="ti-stat-label">Average Rating</div>
                </div>
                <div>
                    <div class="ti-stat-value">48h</div>
                    <div class="ti-stat-label">Avg Response</div>
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

                @if($showRecaptcha && $recaptchaSiteKey)
                    @if($recaptchaVersion === 'v2')
                        <div style="margin-bottom: 16px;">
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}" data-theme="dark"></div>
                            @error('g-recaptcha-response') <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="g-recaptcha-response" id="recaptcha-token-login">
                        @error('g-recaptcha-response') <div style="color: #ef4444; font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror
                    @endif
                @endif

                <button type="submit" class="login-btn" @if($showRecaptcha && $recaptchaSiteKey && $recaptchaVersion === 'v3') id="login-submit-btn" @endif>Sign In</button>
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

// Testimonial carousel — auto-rotate + click-to-jump
(function() {
    const carousel = document.getElementById('tiCarousel');
    if (!carousel) return;
    const slides = carousel.querySelectorAll('.ti-slide');
    const dots   = carousel.querySelectorAll('.ti-dot');
    if (!slides.length) return;

    let index = 0;
    let timer = null;

    function go(i) {
        index = (i + slides.length) % slides.length;
        slides.forEach((s, n) => s.classList.toggle('is-active', n === index));
        dots.forEach((d, n) => d.classList.toggle('is-active', n === index));
    }
    function start() { timer = setInterval(() => go(index + 1), 6000); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }

    dots.forEach(d => d.addEventListener('click', () => {
        stop(); go(parseInt(d.dataset.ti, 10)); start();
    }));

    // Respect prefers-reduced-motion
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) start();
})();

@if($showRecaptcha && $recaptchaSiteKey && $recaptchaVersion === 'v3')
// reCAPTCHA v3 - auto-fill token on form submit
document.getElementById('login-submit-btn').addEventListener('click', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ $recaptchaSiteKey }}', {action: 'login'}).then(function(token) {
            document.getElementById('recaptcha-token-login').value = token;
            e.target.closest('form').submit();
        });
    });
});
@endif
</script>

</body>
</html>
