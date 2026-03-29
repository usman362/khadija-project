<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Join GigResource - Create Your Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-dark: #0b0f1a;
            --bg-section: #0f1629;
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
            --orange-gradient: linear-gradient(135deg, #f97316, #ea580c);
            --pro-gradient: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            --client-gradient: linear-gradient(135deg, #f97316, #f59e0b);
            --radius: 12px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            min-height: 100vh;
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
            border: 1.5px solid rgba(255,255,255,0.15); color: #fff; background: transparent;
            transition: all 0.2s; cursor: pointer;
        }
        .auth-nav-btn:hover { border-color: var(--primary); color: var(--primary); }
        .auth-nav-btn.filled {
            background: var(--orange); border-color: var(--orange); color: #fff;
        }
        .auth-nav-btn.filled:hover { opacity: 0.9; }

        /* ── HERO BANNER ── */
        .signup-hero {
            padding-top: 64px;
            background: linear-gradient(180deg, rgba(59,130,246,0.08) 0%, transparent 100%);
            text-align: center;
            padding-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        .signup-hero::before {
            content: '';
            position: absolute; top: 64px; left: 0; right: 0; height: 300px;
            background: url('https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600&q=60') center/cover;
            opacity: 0.12;
        }
        .signup-hero-content { position: relative; z-index: 1; padding: 60px 24px 0; }
        .signup-hero h1 {
            font-size: 2.5rem; font-weight: 800; margin-bottom: 12px; line-height: 1.2;
        }
        .signup-hero h1 .highlight { color: var(--orange); }
        .signup-hero p { font-size: 1.05rem; color: var(--text-light); max-width: 600px; margin: 0 auto 20px; }
        .signup-hero-badges {
            display: flex; justify-content: center; gap: 32px; flex-wrap: wrap;
        }
        .signup-hero-badge {
            display: flex; align-items: center; gap: 8px;
            font-size: 14px; color: var(--text-light); font-weight: 500;
        }
        .signup-hero-badge:nth-child(1) svg { color: #22c55e; filter: drop-shadow(0 0 4px rgba(34,197,94,0.4)); flex-shrink: 0; }
        .signup-hero-badge:nth-child(2) svg { color: #f59e0b; filter: drop-shadow(0 0 4px rgba(245,158,11,0.4)); flex-shrink: 0; }
        .signup-hero-badge:nth-child(3) svg { color: #8b5cf6; filter: drop-shadow(0 0 4px rgba(139,92,246,0.4)); flex-shrink: 0; }
        .signup-hero-badge svg { flex-shrink: 0; }

        /* ── MAIN CONTENT ── */
        .signup-main {
            max-width: 1200px; margin: 0 auto; padding: 0 24px 60px;
        }
        .signup-section-header {
            text-align: center; margin-bottom: 32px;
        }
        .signup-section-header h2 {
            font-size: 1.75rem; font-weight: 800; margin-bottom: 8px;
        }
        .signup-section-header p { color: var(--text-muted); font-size: 15px; }

        /* ── ROLE TABS ── */
        .role-tabs {
            display: flex; justify-content: center; gap: 0; margin-bottom: 32px;
        }
        .role-tab {
            padding: 12px 32px; font-size: 15px; font-weight: 600;
            border: 2px solid var(--border-color); background: transparent;
            color: var(--text-muted); cursor: pointer; transition: all 0.3s;
        }
        .role-tab:first-child { border-radius: 10px 0 0 10px; }
        .role-tab:last-child { border-radius: 0 10px 10px 0; }
        .role-tab.active-client {
            background: var(--client-gradient); border-color: var(--orange);
            color: #fff;
        }
        .role-tab.active-pro {
            background: var(--pro-gradient); border-color: #8b5cf6;
            color: #fff;
        }

        /* ── TWO COLUMN LAYOUT ── */
        .signup-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 32px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .signup-grid { grid-template-columns: 1fr; }
        }

        /* ── INFO CARD (LEFT) ── */
        .info-card {
            border-radius: var(--radius); padding: 40px 32px;
            position: relative; overflow: hidden; min-height: 520px;
        }
        .info-card.client-card {
            background: var(--client-gradient);
        }
        .info-card.pro-card {
            background: var(--pro-gradient);
        }
        .info-card-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(255,255,255,0.2); display: flex;
            align-items: center; justify-content: center; margin-bottom: 16px;
        }
        .info-card h3 {
            font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;
        }
        .info-card .subtitle {
            font-size: 14px; opacity: 0.85; margin-bottom: 28px;
        }
        .info-card .description {
            font-size: 14px; line-height: 1.7; opacity: 0.9; margin-bottom: 28px;
        }
        .info-card .benefits-title {
            font-size: 14px; font-weight: 700; margin-bottom: 16px;
            color: rgba(255,255,255,0.95);
        }
        .benefit-item {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 14px; font-size: 14px; font-weight: 500;
        }
        .benefit-icon {
            width: 28px; height: 28px; border-radius: 8px;
            background: rgba(255,255,255,0.2); display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }
        .info-card-footer {
            display: flex; gap: 24px; margin-top: 32px; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .footer-badge {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; opacity: 0.85;
        }

        /* ── FORM CARD (RIGHT) ── */
        .form-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius); padding: 36px 32px;
        }
        .form-card h3 {
            font-size: 1.25rem; font-weight: 700; margin-bottom: 6px;
        }
        .form-card .form-subtitle {
            font-size: 14px; color: var(--text-muted); margin-bottom: 28px;
        }
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--text-light); margin-bottom: 6px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-input {
            width: 100%; padding: 12px 16px; border-radius: 10px;
            border: 1.5px solid var(--border-color); background: var(--bg-input);
            color: var(--text-white); font-size: 14px; font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-input::placeholder { color: var(--text-muted); }
        .form-input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .form-input.is-invalid { border-color: #ef4444; }
        .invalid-msg { color: #ef4444; font-size: 12px; margin-top: 4px; }

        .password-wrapper { position: relative; }
        .password-toggle {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-muted); cursor: pointer;
            padding: 4px;
        }
        .password-toggle:hover { color: var(--text-light); }

        .form-submit {
            width: 100%; padding: 14px; border-radius: 10px; border: none;
            font-size: 15px; font-weight: 700; color: #fff; cursor: pointer;
            transition: all 0.2s; margin-top: 8px;
        }
        .form-submit.client-submit {
            background: var(--client-gradient);
        }
        .form-submit.pro-submit {
            background: var(--pro-gradient);
        }
        .form-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        .form-footer {
            text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted);
        }
        .form-footer a { color: var(--primary); font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        .form-divider {
            display: flex; align-items: center; gap: 16px;
            margin: 20px 0; color: var(--text-muted); font-size: 13px;
        }
        .form-divider::before, .form-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border-color);
        }

        /* ── ROLE PANELS ── */
        .role-panel { display: none; }
        .role-panel.active { display: block; }

        /* ── ALERTS ── */
        .auth-alert {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;
            font-size: 13px; font-weight: 500;
        }
        .auth-alert-error {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        @media (max-width: 600px) {
            .signup-hero h1 { font-size: 1.75rem; }
            .form-card { padding: 24px 20px; }
            .info-card { padding: 28px 20px; min-height: auto; }
            .form-row { grid-template-columns: 1fr; }
            .role-tab { padding: 10px 20px; font-size: 14px; }
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
            <a href="{{ route('login') }}" class="auth-nav-btn">Log In</a>
        </div>
    </div>
</nav>

<!-- ── HERO BANNER ── -->
<section class="signup-hero">
    <div class="signup-hero-content">
        <h1>Join <span class="highlight">GigResource</span> Today</h1>
        <p>Create your free account and connect with thousands of professionals and clients worldwide.</p>
        <div class="signup-hero-badges">
            <div class="signup-hero-badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Free to join
            </div>
            <div class="signup-hero-badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                No setup fees
            </div>
            <div class="signup-hero-badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Start immediately
            </div>
        </div>
    </div>
</section>

<!-- ── MAIN ── -->
<div class="signup-main">
    <div class="signup-section-header">
        <h2>Choose How You Want to Get Started</h2>
        <p>Select the option that best describes what you're looking for</p>
    </div>

    <!-- Role Tabs -->
    <div class="role-tabs">
        <button class="role-tab {{ ($role ?? 'client') === 'client' ? 'active-client' : '' }}" data-role="client" onclick="switchRole('client')">Join as a Client</button>
        <button class="role-tab {{ ($role ?? 'client') === 'supplier' ? 'active-pro' : '' }}" data-role="supplier" onclick="switchRole('supplier')">Join as a Professional</button>
    </div>

    <!-- ══════════ CLIENT PANEL ══════════ -->
    <div class="role-panel {{ ($role ?? 'client') === 'client' ? 'active' : '' }}" id="panel-client">
        <div class="signup-grid">
            <!-- Info Card -->
            <div class="info-card client-card">
                <div class="info-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h3>Sign Up as a Client</h3>
                <div class="subtitle">Most Popular Choice</div>
                <div class="description">Perfect for event planners, business owners, and individuals looking to hire professional services. Create your free account and start posting projects today.</div>
                <div class="benefits-title">What you get as a Client:</div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    Access verified professionals
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg>
                    </div>
                    Post unlimited events and projects
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    Secure payment protection
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    24/7 customer support
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    Review and rating system
                </div>
                <div class="info-card-footer">
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        4.8/5 rating
                    </div>
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Verified Network
                    </div>
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Quick Approval
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <h3>Create your Client account</h3>
                <div class="form-subtitle">Simple, fast and secure — get started in less than a minute.</div>

                @if ($errors->any() && old('role', 'client') === 'client')
                    <div class="auth-alert auth-alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <input type="hidden" name="role" value="client">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input {{ $errors->has('name') && old('role') === 'client' ? 'is-invalid' : '' }}" placeholder="John Doe" value="{{ old('role') === 'client' ? old('name') : '' }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input {{ $errors->has('email') && old('role') === 'client' ? 'is-invalid' : '' }}" placeholder="you@example.com" value="{{ old('role') === 'client' ? old('email') : '' }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" class="form-input {{ $errors->has('password') && old('role') === 'client' ? 'is-invalid' : '' }}" placeholder="Min. 8 characters" required>
                            <button type="button" class="password-toggle" onclick="togglePw(this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Re-enter your password" required>
                    </div>

                    <button type="submit" class="form-submit client-submit">Create Client Account</button>
                </form>

                <div class="form-footer">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ PROFESSIONAL PANEL ══════════ -->
    <div class="role-panel {{ ($role ?? 'client') === 'supplier' ? 'active' : '' }}" id="panel-supplier">
        <div class="signup-grid">
            <!-- Info Card -->
            <div class="info-card pro-card">
                <div class="info-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <h3>Join as a Professional</h3>
                <div class="subtitle">Start earning today</div>
                <div class="description">Turn your skills into income. Join thousands of professionals who are building their careers and earning more on our platform.</div>
                <div class="benefits-title">What you get as a Professional:</div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    </div>
                    Access to high-paying gigs
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    Get verified badge and priority listing
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    24/7 dedicated support team
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    Fast, secure payments within 24 hours
                </div>
                <div class="info-card-footer">
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        4.8/5 rating
                    </div>
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Verified Network
                    </div>
                    <div class="footer-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Quick Approval
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <h3>Professional / Company Signup</h3>
                <div class="form-subtitle">Create a professional profile to start receiving client requests.</div>

                @if ($errors->any() && old('role') === 'supplier')
                    <div class="auth-alert auth-alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <input type="hidden" name="role" value="supplier">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input {{ $errors->has('name') && old('role') === 'supplier' ? 'is-invalid' : '' }}" placeholder="John Smith" value="{{ old('role') === 'supplier' ? old('name') : '' }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input {{ $errors->has('email') && old('role') === 'supplier' ? 'is-invalid' : '' }}" placeholder="you@company.com" value="{{ old('role') === 'supplier' ? old('email') : '' }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" class="form-input {{ $errors->has('password') && old('role') === 'supplier' ? 'is-invalid' : '' }}" placeholder="Min. 8 characters" required>
                            <button type="button" class="password-toggle" onclick="togglePw(this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Re-enter your password" required>
                    </div>

                    <button type="submit" class="form-submit pro-submit">Create Professional Account</button>
                </form>

                <div class="form-footer">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchRole(role) {
    // Update tabs
    document.querySelectorAll('.role-tab').forEach(t => {
        t.classList.remove('active-client', 'active-pro');
    });
    const activeTab = document.querySelector(`.role-tab[data-role="${role}"]`);
    activeTab.classList.add(role === 'client' ? 'active-client' : 'active-pro');

    // Update panels
    document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + role).classList.add('active');

    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('role', role);
    window.history.replaceState({}, '', url);
}

function togglePw(btn) {
    const input = btn.parentElement.querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
}

// If validation errors, show correct panel
@if(old('role') === 'supplier')
    switchRole('supplier');
@endif
</script>

</body>
</html>
