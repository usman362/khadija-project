<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - {{ config('app.name', 'GigResource') }}</title>
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

        /* ─── HERO SECTION ──────────────────────────── */
        .about-hero {
            position: relative;
            padding: 140px 0 80px;
            text-align: center;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, rgba(139,92,246,0.08) 40%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .about-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(59,130,246,0.1);
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 24px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .hero-badge svg {
            width: 14px;
            height: 14px;
        }

        .about-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.15;
            letter-spacing: -1px;
        }

        .about-hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end), #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-hero p {
            color: var(--text-light);
            font-size: 1.2rem;
            max-width: 640px;
            margin: 0 auto 40px;
            line-height: 1.7;
        }

        /* ─── STATS BAR ──────────────────────────── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2px;
            max-width: 900px;
            margin: 0 auto;
            background: var(--border-color);
            border-radius: 16px;
            overflow: hidden;
        }

        .stat-item {
            background: var(--bg-card);
            padding: 28px 16px;
            text-align: center;
            transition: background 0.3s;
        }

        .stat-item:hover {
            background: var(--bg-card-hover);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ─── MISSION SECTION ──────────────────────────── */
        .mission-section {
            padding: 80px 0;
            position: relative;
        }

        .mission-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .mission-content h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .mission-content h2 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mission-content p {
            color: var(--text-light);
            font-size: 1.05rem;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .mission-features {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .mission-feature {
            display: flex;
            gap: 16px;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s;
        }

        .mission-feature:hover {
            border-color: rgba(59,130,246,0.3);
            transform: translateX(4px);
        }

        .mission-feature-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(139,92,246,0.15));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mission-feature-icon svg {
            width: 22px;
            height: 22px;
            color: var(--primary);
        }

        .mission-feature h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .mission-feature p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        /* ─── HOW IT WORKS - TIMELINE ──────────────────────────── */
        .timeline-section {
            padding: 80px 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-header h2 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 520px;
            margin: 0 auto;
        }

        .timeline {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--gradient-start), var(--gradient-end), rgba(139,92,246,0.2));
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 40px;
            margin-bottom: 48px;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            flex: 1;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            transition: all 0.3s;
            position: relative;
        }

        .timeline-content:hover {
            border-color: rgba(59,130,246,0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(59,130,246,0.3);
        }

        .timeline-dot svg {
            width: 22px;
            height: 22px;
            color: #fff;
        }

        .timeline-spacer {
            flex: 1;
        }

        .timeline-content .step-tag {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            background: rgba(59,130,246,0.1);
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .timeline-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .timeline-detail {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }

        .timeline-detail:last-child {
            margin-bottom: 0;
        }

        .detail-bullet {
            min-width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary);
            margin-top: 8px;
        }

        .timeline-detail p {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.7;
            margin: 0;
        }

        .timeline-detail strong {
            color: var(--text-white);
            font-weight: 600;
        }

        /* ─── CTA SECTION ──────────────────────────── */
        .cta-section {
            padding: 80px 0;
        }

        .cta-box {
            position: relative;
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1));
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            overflow: hidden;
        }

        .cta-box::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(139,92,246,0.1), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-box::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(59,130,246,0.08), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-box h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 16px;
            position: relative;
        }

        .cta-box p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 32px;
            line-height: 1.7;
            position: relative;
        }

        .cta-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(59,130,246,0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(59,130,246,0.4);
        }

        .btn-ghost {
            background: rgba(255,255,255,0.05);
            color: var(--text-white);
            border: 1.5px solid rgba(255,255,255,0.15);
            font-weight: 600;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .btn-ghost:hover {
            border-color: rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.08);
        }

        /* ─── ANIMATE ON SCROLL ──────────────────────────── */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-left {
            opacity: 0;
            transform: translateX(-30px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .fade-right {
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-right.visible {
            opacity: 1;
            transform: translateX(0);
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

        .footer-col ul {
            list-style: none;
        }

        .footer-col li {
            margin-bottom: 10px;
        }

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
            .mission-grid { grid-template-columns: 1fr; gap: 40px; }
            .timeline::before { left: 24px; }
            .timeline-item,
            .timeline-item:nth-child(even) {
                flex-direction: column;
                padding-left: 72px;
                gap: 0;
            }
            .timeline-dot {
                left: 24px;
                top: 0;
            }
            .timeline-spacer { display: none; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .navbar-links { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
            .about-hero h1 { font-size: 2.25rem; }
            .about-hero { padding: 110px 0 50px; }
            .about-hero p { font-size: 1rem; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); border-radius: 12px; }
            .stat-item { padding: 20px 12px; }
            .stat-number { font-size: 1.5rem; }
            .section-header h2 { font-size: 1.75rem; }
            .timeline-section { padding: 60px 0; }
            .mission-section { padding: 60px 0; }
            .cta-box { padding: 40px 24px; }
            .cta-box h2 { font-size: 1.5rem; }
            .cta-actions { flex-direction: column; }
            .cta-actions a { width: 100%; text-align: center; }
        }

        @media (max-width: 480px) {
            .about-hero h1 { font-size: 1.75rem; }
            .stats-bar { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ───────────────────────────────── -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 36px;"></a>

        <ul class="navbar-links">
            <li><a href="{{ route('about-us') }}" style="color: var(--text-white);">About Us</a></li>
            <li><a href="{{ route('events-categories') }}">Events & Categories</a></li>
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
<section class="about-hero">
    <div class="container">
        <div class="fade-up">
            <div class="hero-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Our Story
            </div>
            <h1>Connecting Talent with<br><span class="gradient-text">Unforgettable Events</span></h1>
            <p>GigResource bridges the gap between clients, professionals, and influencers — making event planning seamless, reliable, and rewarding for everyone involved.</p>
        </div>

        <div class="stats-bar fade-up" style="transition-delay: 0.15s;">
            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Professionals</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">1K+</div>
                <div class="stat-label">Events Booked</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50+</div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- ─── MISSION SECTION ───────────────────────────────── -->
<section class="mission-section">
    <div class="container">
        <div class="mission-grid">
            <div class="mission-content fade-left">
                <h2>Our Mission: <span class="gradient-text">Empowering the Event Industry</span></h2>
                <p>The GigResource platform is designed to create seamless interactions between clients, professionals, and GigResource Influencers. We bring together the best talent in the industry with the clients who need them most.</p>
                <p>Whether you're planning a wedding, corporate event, birthday party, or any special occasion — GigResource makes finding and booking the right professionals effortless.</p>
            </div>
            <div class="mission-features fade-right">
                <div class="mission-feature">
                    <div class="mission-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <h4>Verified Professionals</h4>
                        <p>Every professional goes through a strict verification process to ensure quality and reliability.</p>
                    </div>
                </div>
                <div class="mission-feature">
                    <div class="mission-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <h4>Direct Communication</h4>
                        <p>Chat directly with vendors to discuss details, ask questions, and finalize agreements.</p>
                    </div>
                </div>
                <div class="mission-feature">
                    <div class="mission-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <h4>Earn as Influencer</h4>
                        <p>Promote the platform and earn commissions for every successful referral you bring.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── TIMELINE SECTION ───────────────────────────────── -->
<section class="timeline-section" style="background: var(--bg-section);">
    <div class="container">
        <div class="section-header fade-up">
            <h2>How <span class="gradient-text">GigResource</span> Works</h2>
            <p>A step-by-step guide to how our platform connects everyone in the event planning ecosystem.</p>
        </div>

        <div class="timeline">
            <!-- Step 1 -->
            <div class="timeline-item fade-up">
                <div class="timeline-content">
                    <span class="step-tag">Step 1</span>
                    <h3>Client Discovery</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Exploration:</strong> Clients visit the platform to explore an extensive range of professionals — DJs, caterers, photographers, makeup artists, and event planners.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Comparison:</strong> Compare vendors based on services, pricing, reviews, and availability to make well-informed decisions.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 2 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.1s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 2</span>
                    <h3>Professional Registration</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Sign-Up:</strong> Professionals create comprehensive profiles showcasing their services, experience, and portfolios.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Verification:</strong> Each professional undergoes a verification process to ensure quality and reliability, building trust with potential clients.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 3 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.15s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 3</span>
                    <h3>Influencer Promotion</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Referral Links:</strong> GigResource Influencers receive unique referral links. They share these through social media, email campaigns, and personal networks to attract new clients and professionals.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Earnings:</strong> Influencers earn commissions for each successful sign-up made through their referral links, creating an incentive to actively promote the platform.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 4 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.2s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 4</span>
                    <h3>Matching & Booking</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Client Sign-Up:</strong> After discovering a suitable vendor, clients sign up and access the dashboard where they can book vendors directly.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Direct Communication:</strong> Clients communicate with professionals through the platform to discuss event details, ask questions, and finalize agreements.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 5 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.25s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 5</span>
                    <h3>Event Execution</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Collaboration:</strong> Once a booking is confirmed, professionals work directly with clients to plan and execute the event as per the client's specifications.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Support System:</strong> Throughout the planning process, GigResource provides support to both clients and professionals, facilitating a smooth event experience.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 6 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.3s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 6</span>
                    <h3>Feedback & Growth</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Review System:</strong> After the event, clients are encouraged to leave reviews and ratings. This feedback helps future clients make informed decisions and assists professionals in building their reputations.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Continuous Improvement:</strong> GigResource utilises feedback to continuously enhance the platform, making it more user-friendly and effective for all participants.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            <!-- Step 7 -->
            <div class="timeline-item fade-up" style="transition-delay: 0.35s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 7</span>
                    <h3>Influencer Earnings</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Commission Payout:</strong> Influencers receive payments for their earned commissions once the referred clients or professionals meet the eligibility criteria.</p>
                    </div>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Performance Tracking:</strong> Influencers can log into their dashboard at any time to monitor performance stats, earnings, and the status of their referral activities.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>
        </div>
    </div>
</section>

<!-- ─── CTA SECTION ───────────────────────────────── -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box fade-up">
            <h2>Ready to Create Something <span class="gradient-text">Amazing</span>?</h2>
            <p>Join GigResource today and become part of a thriving event planning community. Whether you're a client, professional, or influencer — there's a place for you.</p>
            <div class="cta-actions">
                @guest
                    <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-gradient">Join as Professional</a>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-ghost">Hire a Professional</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="btn btn-gradient">Go to Dashboard</a>
                @endguest
            </div>
        </div>
    </div>
</section>

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
                    <li><a href="{{ route('events-categories') }}">Events & Categories</a></li>
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

<!-- ─── SCROLL ANIMATIONS ───────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.fade-up, .fade-left, .fade-right').forEach(function(el) {
        observer.observe(el);
    });
});
</script>

</body>
</html>
