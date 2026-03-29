<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $policy->title ?? $fallbackTitle }} - {{ config('app.name', 'GigResource') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #8b5cf6;
            --bg-dark: #0b0f1a;
            --bg-section: #0f1629;
            --bg-card: #151d35;
            --bg-card-hover: #1a2440;
            --text-white: #ffffff;
            --text-light: #c8cdd8;
            --text-muted: #7a829a;
            --border-color: #1e2d4a;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }
        button { cursor: pointer; border: none; font-family: inherit; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ─── NAVBAR (same as landing) ──────────────────────────── */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(11, 15, 26, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 0 24px;
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #fff;
            text-decoration: none;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .navbar-links a {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
            transition: color 0.2s;
        }

        .navbar-links a:hover { color: var(--text-white); }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-outline {
            border: 1.5px solid rgba(255,255,255,0.2);
            color: var(--text-white);
            background: transparent;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-blue {
            background: #2563eb;
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-blue:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-red {
            background: #dc2626;
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-red:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 8px 18px;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        .mobile-menu-btn {
            display: none;
            background: transparent;
            color: #fff;
            font-size: 1.5rem;
            padding: 8px;
        }

        /* ─── POLICY CONTENT ──────────────────────────── */
        .policy-hero {
            padding: 120px 0 40px;
            text-align: center;
        }

        .policy-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .policy-hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .policy-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .policy-body {
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        .policy-body h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 36px;
            margin-bottom: 14px;
            color: var(--text-white);
        }

        .policy-body h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .policy-body p {
            color: var(--text-light);
            margin-bottom: 14px;
            line-height: 1.75;
        }

        .policy-body ul, .policy-body ol {
            margin: 14px 0 14px 24px;
            color: var(--text-light);
        }

        .policy-body li {
            margin-bottom: 8px;
            line-height: 1.7;
        }

        .policy-body strong {
            color: var(--text-white);
        }

        .policy-body a {
            color: var(--primary);
        }

        .policy-body a:hover {
            text-decoration: underline;
        }

        .policy-body .highlight-box {
            background: var(--bg-card);
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .policy-body .highlight-box p {
            margin: 0;
        }

        /* ─── FOOTER (same as landing) ──────────────────────────── */
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 60px 0 32px;
            background: #060912;
            margin-top: 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .footer-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-socials {
            display: flex;
            gap: 12px;
        }

        .footer-social {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            color: var(--text-light);
            transition: background 0.2s;
        }

        .footer-social:hover { background: var(--primary); }
        .footer-social svg { width: 16px; height: 16px; }

        .footer-col h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 10px; }

        .footer-col a {
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .footer-col a:hover { color: var(--text-white); }

        .footer-bottom {
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ─── RESPONSIVE ──────────────────────────── */
        @media (max-width: 1024px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .navbar-links { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
            .policy-hero h1 { font-size: 1.75rem; }
            .policy-hero { padding: 100px 0 30px; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ───────────────────────────────── -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 36px;"></a>

        <ul class="navbar-links">
            <li><a href="{{ route('about-us') }}">About Us</a></li>
            <li><a href="{{ route('events-categories') }}">Events</a></li>
            <li><a href="/#how-it-works">How It Works</a></li>
            <li><a href="/#pricing">Pricing</a></li>
            <li><a href="/#faq">FAQ</a></li>
        </ul>

        <div class="navbar-actions">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-blue btn-sm">Join as Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-red btn-sm">Hire a Professional</a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                @endif
            @endauth
        </div>

        <button class="mobile-menu-btn" onclick="this.nextElementSibling.classList.toggle('show')" aria-label="Menu">&#9776;</button>
        <div class="mobile-nav" style="display:none;"></div>
    </div>
</nav>

<!-- ─── HERO ───────────────────────────────── -->
<section class="policy-hero">
    <div class="container">
        <h1><span class="gradient-text">{{ $policy->title ?? $fallbackTitle }}</span></h1>
        <p class="policy-date">Last updated: {{ $policy ? $policy->updated_at->format('F j, Y') : now()->format('F j, Y') }}</p>
    </div>
</section>

<!-- ─── CONTENT ───────────────────────────────── -->
<div class="policy-body">
    @if($policy && $policy->content)
        {!! $policy->content !!}
    @else
        <p style="text-align:center; color: var(--text-muted);">This policy page has not been set up yet. Please check back later.</p>
    @endif
</div>

<!-- ─── FOOTER ─────────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 32px;"></div>
                <p class="footer-desc">
                    Connecting Professionals & Clients for Perfect Events.
                    Create unforgettable experiences with our curated network of verified experts.
                </p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/gigresource/" target="_blank" class="footer-social" title="Facebook">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/gigresource2025/" target="_blank" class="footer-social" title="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="https://www.tiktok.com/@gigresource123/" target="_blank" class="footer-social" title="TikTok">
                        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.71a8.21 8.21 0 0 0 4.76 1.52V6.69h-1z"/></svg>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <ul>
                    <li><a href="{{ route('about-us') }}">About Us</a></li>
                    <li><a href="{{ route('events-categories') }}">Events</a></li>
                    <li><a href="/#how-it-works">How It Works</a></li>
                    <li><a href="/#pricing">Pricing</a></li>
                    <li><a href="/#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get Started</h4>
                <ul>
                    @guest
                        <li><a href="{{ route('register') }}">Join as Professional</a></li>
                        <li><a href="{{ route('register') }}">Hire Talent</a></li>
                        <li><a href="{{ route('login') }}">Log In</a></li>
                    @else
                        <li><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    @endguest
                </ul>
            </div>
            <div class="footer-col">
                <h4>Policies</h4>
                <ul>
                    <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('payment-policy') }}">Payment Policy</a></li>
                    <li><a href="{{ route('cancellation-policy') }}">Cancellation & Refund</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} GigResource. All rights reserved.</span>
            <span>
                <a href="{{ route('privacy-policy') }}" style="color: var(--text-muted);">Privacy</a> &middot;
                <a href="{{ route('payment-policy') }}" style="color: var(--text-muted);">Payment</a> &middot;
                <a href="{{ route('cancellation-policy') }}" style="color: var(--text-muted);">Cancellation</a>
            </span>
        </div>
    </div>
</footer>

</body>
</html>
