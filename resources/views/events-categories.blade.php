<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - {{ config('app.name', 'GigResource') }}</title>
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
            --orange: #f97316;
            --green: #22c55e;
            --pink: #ec4899;
            --cyan: #06b6d4;
            --amber: #f59e0b;
            --rose: #f43f5e;
            --indigo: #6366f1;
            --emerald: #10b981;
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

        /* ─── HERO ──────────────────────────── */
        .ec-hero {
            position: relative;
            padding: 130px 0 60px;
            text-align: center;
            overflow: hidden;
        }

        .ec-hero::before {
            content: '';
            position: absolute;
            top: -40%;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, rgba(139,92,246,0.06) 40%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .ec-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .ec-hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end), #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .ec-hero p {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 550px;
            margin: 0 auto 36px;
        }

        /* ─── SEARCH BAR ──────────────────────────── */
        .search-bar {
            max-width: 640px;
            margin: 0 auto;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            color: var(--text-white);
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }

        .search-bar input::placeholder {
            color: var(--text-muted);
        }

        .search-bar input:focus {
            border-color: var(--primary);
        }

        .search-bar .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-bar .search-icon svg {
            width: 20px;
            height: 20px;
        }

        /* ─── FILTER TABS ──────────────────────────── */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 40px auto 48px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .filter-tab:hover {
            border-color: rgba(59,130,246,0.3);
            color: var(--text-light);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            border-color: transparent;
        }

        /* ─── CATEGORIES GRID ──────────────────────────── */
        .ec-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 60px;
        }

        .ec-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            display: flex;
            gap: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .ec-card:hover {
            border-color: rgba(59,130,246,0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        .ec-card-icon {
            width: 64px;
            height: 64px;
            min-width: 64px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .ec-card-body {
            flex: 1;
        }

        .ec-card-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .tag-main {
            background: rgba(249,115,22,0.15);
            color: var(--orange);
        }

        .tag-sub {
            background: rgba(139,92,246,0.15);
            color: var(--accent);
        }

        .ec-card-body h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .ec-card-body p {
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 14px;
        }

        .ec-card-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .meta-badge svg {
            width: 13px;
            height: 13px;
            color: var(--primary);
        }

        /* ─── EVENT TYPES SECTION ──────────────────────────── */
        .event-types {
            padding: 60px 0 80px;
            background: var(--bg-section);
        }

        .event-types .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .event-types .section-header h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .event-types .section-header h2 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .event-types .section-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .event-tile {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            height: 220px;
            cursor: pointer;
            background: var(--bg-card);
        }

        .event-tile-bg {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            opacity: 0.2;
            transition: opacity 0.3s, transform 0.3s;
        }

        .event-tile:hover .event-tile-bg {
            opacity: 0.3;
            transform: scale(1.1);
        }

        .event-tile-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 30%, rgba(0,0,0,0.85) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px;
            transition: background 0.3s;
        }

        .event-tile:hover .event-tile-overlay {
            background: linear-gradient(180deg, transparent 10%, rgba(59,130,246,0.5) 100%);
        }

        .event-tile h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .event-tile span {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* ─── CTA ──────────────────────────── */
        .ec-cta {
            padding: 80px 0;
        }

        .ec-cta-box {
            position: relative;
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1));
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 24px;
            padding: 56px 40px;
            text-align: center;
            overflow: hidden;
        }

        .ec-cta-box::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(139,92,246,0.1), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .ec-cta-box h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
        }

        .ec-cta-box p {
            color: var(--text-light);
            font-size: 1.05rem;
            max-width: 560px;
            margin: 0 auto 28px;
            line-height: 1.7;
            position: relative;
        }

        .ec-cta-actions {
            display: flex;
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

        /* ─── ANIMATIONS ──────────────────────────── */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
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
            .event-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .navbar-links { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
            .ec-hero h1 { font-size: 2rem; }
            .ec-hero { padding: 110px 0 40px; }
            .ec-grid { grid-template-columns: 1fr; }
            .event-grid { grid-template-columns: 1fr; }
            .ec-cta-box { padding: 40px 24px; }
            .ec-cta-box h2 { font-size: 1.5rem; }
            .ec-cta-actions { flex-direction: column; }
            .ec-cta-actions a { width: 100%; text-align: center; }
            .filter-tabs { gap: 6px; }
            .filter-tab { padding: 8px 16px; font-size: 0.8rem; }
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
            <li><a href="{{ route('events-categories') }}" style="color: var(--text-white);">Events</a></li>
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
<section class="ec-hero">
    <div class="container">
        <div class="fade-up">
            <h1>Browse <span class="gradient-text">Categories</span></h1>
            <p>Explore different categories to find the perfect events and services for any occasion.</p>

            <div class="search-bar">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search categories..." oninput="filterCards()">
            </div>
        </div>

        <div class="filter-tabs fade-up" style="transition-delay:0.1s;">
            <button class="filter-tab active" onclick="setFilter('all', this)">All Categories</button>
            <button class="filter-tab" onclick="setFilter('main', this)">Main Categories</button>
            <button class="filter-tab" onclick="setFilter('sub', this)">Sub Categories</button>
        </div>
    </div>
</section>

<!-- ─── CATEGORIES GRID ───────────────────────────────── -->
<section style="padding-bottom: 60px;">
    <div class="container">
        <div class="ec-grid" id="categoriesGrid">

            <!-- Main Category: Baby Shower -->
            <div class="ec-card fade-up" data-type="main" data-name="baby shower">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(236,72,153,0.05));">
                    <span style="font-size:1.75rem;">&#127880;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-main">Main Category</span>
                    <h3>Baby Shower</h3>
                    <p>A joyful gathering celebrating the upcoming arrival of a new baby with love, gifts, and well-wishes.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 12 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 45 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Main Category: Wedding -->
            <div class="ec-card fade-up" data-type="main" data-name="wedding" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(244,63,94,0.15), rgba(244,63,94,0.05));">
                    <span style="font-size:1.75rem;">&#128141;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-main">Main Category</span>
                    <h3>Wedding Planning & Ceremony</h3>
                    <p>Complete wedding services including venue coordination, bridal attire, ceremony management, and reception planning.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 28 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 120 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Main Category: Corporate Event -->
            <div class="ec-card fade-up" data-type="main" data-name="corporate event conference">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.05));">
                    <span style="font-size:1.75rem;">&#127970;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-main">Main Category</span>
                    <h3>Corporate Events & Conferences</h3>
                    <p>Professional event management for business meetings, conferences, product launches, and corporate gatherings.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 18 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 80 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: DJ Services -->
            <div class="ec-card fade-up" data-type="sub" data-name="dj services music entertainment" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(6,182,212,0.05));">
                    <span style="font-size:1.75rem;">&#127911;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>DJ Services</h3>
                    <p>Professional DJs delivering high-energy music experiences with state-of-the-art equipment for any event.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 22 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 90 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Photography -->
            <div class="ec-card fade-up" data-type="sub" data-name="photography photographer wedding event">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05));">
                    <span style="font-size:1.75rem;">&#128247;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Photography & Videography</h3>
                    <p>Capture every special moment with professional photographers and videographers for weddings, events, and portraits.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 35 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 75 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Catering -->
            <div class="ec-card fade-up" data-type="sub" data-name="catering food beverage bar" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.05));">
                    <span style="font-size:1.75rem;">&#127869;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Catering & Food Services</h3>
                    <p>Exquisite catering solutions from gourmet dining to casual buffets, including bartending and beverage services.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 20 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 55 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Makeup -->
            <div class="ec-card fade-up" data-type="sub" data-name="makeup artist beauty bridal">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(236,72,153,0.05));">
                    <span style="font-size:1.75rem;">&#128132;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Makeup & Beauty Artists</h3>
                    <p>Professional makeup artists for bridal looks, event styling, and special occasion beauty services.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 15 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 48 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Decor & Floral -->
            <div class="ec-card fade-up" data-type="sub" data-name="decor floral decoration flowers" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(244,63,94,0.15), rgba(244,63,94,0.05));">
                    <span style="font-size:1.75rem;">&#127803;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Decor & Floral Services</h3>
                    <p>Stunning decorations and floral arrangements that transform venues into breathtaking spaces for any event.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 18 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 42 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Event Planning -->
            <div class="ec-card fade-up" data-type="sub" data-name="event planner planning coordination">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.05));">
                    <span style="font-size:1.75rem;">&#128203;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Event Planning & Coordination</h3>
                    <p>Full-service event planners who handle every detail from concept to execution, ensuring flawless events.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 25 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 60 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Live Bands -->
            <div class="ec-card fade-up" data-type="sub" data-name="live bands musical acts music entertainment" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(249,115,22,0.15), rgba(249,115,22,0.05));">
                    <span style="font-size:1.75rem;">&#127928;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Live Bands & Musical Acts</h3>
                    <p>Captivating live music performances that elevate the energy and ambiance of any event.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 14 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 38 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Awards & Recognition -->
            <div class="ec-card fade-up" data-type="sub" data-name="awards recognition branding gifting">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05));">
                    <span style="font-size:1.75rem;">&#127942;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Awards, Branding & Gifting</h3>
                    <p>Celebrating achievements with thoughtfully designed awards, custom branding, and meaningful recognition programs.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 8 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 22 Gigs</span>
                    </div>
                </div>
            </div>

            <!-- Sub Category: Event Staff -->
            <div class="ec-card fade-up" data-type="sub" data-name="event staff assistants staffing guest services" style="transition-delay:0.05s;">
                <div class="ec-card-icon" style="background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.05));">
                    <span style="font-size:1.75rem;">&#128101;</span>
                </div>
                <div class="ec-card-body">
                    <span class="ec-card-tag tag-sub">Sub Category</span>
                    <h3>Event Staff & Assistants</h3>
                    <p>Professional support teams to ensure smooth and efficient event operations from start to finish.</p>
                    <div class="ec-card-meta">
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> 10 Events</span>
                        <span class="meta-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> 30 Gigs</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ─── POPULAR EVENT TYPES ───────────────────────────────── -->
<section class="event-types">
    <div class="container">
        <div class="section-header fade-up">
            <h2>Popular <span class="gradient-text">Event Types</span></h2>
            <p>Find professionals for every kind of occasion</p>
        </div>

        <div class="event-grid">
            <div class="event-tile fade-up">
                <div class="event-tile-bg">&#127880;</div>
                <div class="event-tile-overlay">
                    <h3>Birthday Parties</h3>
                    <span>60+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.05s;">
                <div class="event-tile-bg">&#127911;</div>
                <div class="event-tile-overlay">
                    <h3>Music Concerts</h3>
                    <span>90+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.1s;">
                <div class="event-tile-bg">&#128141;</div>
                <div class="event-tile-overlay">
                    <h3>Weddings</h3>
                    <span>120+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.15s;">
                <div class="event-tile-bg">&#127970;</div>
                <div class="event-tile-overlay">
                    <h3>Corporate Events</h3>
                    <span>80+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.2s;">
                <div class="event-tile-bg">&#127891;</div>
                <div class="event-tile-overlay">
                    <h3>Graduation Ceremonies</h3>
                    <span>35+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.25s;">
                <div class="event-tile-bg">&#127878;</div>
                <div class="event-tile-overlay">
                    <h3>Holiday Celebrations</h3>
                    <span>50+ professionals available</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── CTA ───────────────────────────────── -->
<section class="ec-cta">
    <div class="container">
        <div class="ec-cta-box fade-up">
            <h2>Can't Find What You're Looking For?</h2>
            <p>Post your event and let professionals come to you. Describe your needs and receive proposals from verified experts.</p>
            <div class="ec-cta-actions">
                @guest
                    <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-gradient">Post an Event</a>
                    <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-ghost">Join as Professional</a>
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

<!-- ─── SCRIPTS ───────────────────────────────── -->
<script>
// Scroll animations
document.addEventListener('DOMContentLoaded', function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.fade-up').forEach(function(el) {
        observer.observe(el);
    });
});

// Filter by type
function setFilter(type, btn) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');

    // Filter cards
    var cards = document.querySelectorAll('.ec-card');
    cards.forEach(function(card) {
        var cardType = card.getAttribute('data-type');
        if (type === 'all' || cardType === type) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Search filter
function filterCards() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var cards = document.querySelectorAll('.ec-card');

    // Reset filter tabs to "All"
    document.querySelectorAll('.filter-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelector('.filter-tab').classList.add('active');

    cards.forEach(function(card) {
        var name = card.getAttribute('data-name');
        var title = card.querySelector('h3').textContent.toLowerCase();
        var desc = card.querySelector('p').textContent.toLowerCase();

        if (name.includes(query) || title.includes(query) || desc.includes(query)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
