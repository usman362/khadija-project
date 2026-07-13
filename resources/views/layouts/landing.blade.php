<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    @include('partials._seo_meta')
    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="dns-prefetch" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── Light public theme (self-contained — does not touch the dark
              shared public partials used by the not-yet-redesigned pages) ── */
        :root {
            --blue: #2563eb;
            --blue-light: #3b82f6;
            --blue-dark: #1d4ed8;
            --orange: #f97316;
            --orange-dark: #ea580c;
            --ink: #0f1b35;
            --ink-2: #1e293b;
            --text: #475569;
            --muted: #64748b;
            --faint: #94a3b8;
            --line: #e6eaf1;
            --line-soft: #eef2f7;
            --bg: #ffffff;
            --bg-soft: #f7f9fc;
            --bg-soft-2: #eef3fb;
            --radius: 16px;
            --radius-lg: 22px;
            --shadow-sm: 0 2px 8px rgba(15, 27, 53, 0.05);
            --shadow: 0 14px 40px rgba(15, 27, 53, 0.08);
            --shadow-lg: 0 24px 60px rgba(15, 27, 53, 0.12);
            --ff: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --ff-head: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: var(--ff);
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }
        h1, h2, h3, h4 { font-family: var(--ff-head); color: var(--ink); margin: 0; line-height: 1.15; }
        .lp-container { max-width: 1320px; margin: 0 auto; padding: 0 24px; }
        .skip-to-content { position: absolute; left: -9999px; top: 0; background: var(--blue); color: #fff; padding: 10px 16px; border-radius: 0 0 8px 0; z-index: 2000; }
        .skip-to-content:focus { left: 0; }

        /* ── Navbar ───────────────────────────────────────────── */
        .lpn { position: sticky; top: 0; z-index: 900; background: rgba(255, 255, 255, 0.9); backdrop-filter: saturate(180%) blur(14px); border-bottom: 1px solid var(--line); }
        .lpn-inner { display: flex; align-items: center; gap: 26px; height: 74px; }
        .lpn-brand { display: flex; align-items: center; gap: 11px; flex-shrink: 0; }
        .lpn-logo-img { height: 50px; width: auto; display: block; }
        .lpf-logo-img { height: 50px; width: auto; display: block; }
        .lpn-mark { width: 40px; height: 44px; flex-shrink: 0; }
        .lpn-word { display: flex; flex-direction: column; line-height: 1; }
        .lpn-word b { font-family: var(--ff-head); font-size: 20px; font-weight: 800; color: var(--ink); letter-spacing: -0.4px; }
        .lpn-word b i { font-style: normal; color: var(--blue); }
        .lpn-word span { font-size: 8.5px; font-weight: 700; letter-spacing: 1.6px; color: var(--faint); margin-top: 3px; }
        .lpn-links { display: flex; align-items: center; gap: 4px; margin-left: 8px; }
        .lpn-item { position: relative; }
        .lpn-link { display: inline-flex; align-items: center; gap: 5px; padding: 9px 12px; font-size: 14.5px; font-weight: 600; color: var(--ink-2); border-radius: 9px; cursor: pointer; transition: background .15s, color .15s; white-space: nowrap; }
        .lpn-link:hover { background: var(--bg-soft-2); color: var(--blue); }
        .lpn-link svg { width: 13px; height: 13px; opacity: .7; transition: transform .18s; }
        .lpn-item:hover .lpn-link svg { transform: rotate(180deg); }
        .lpn-menu { position: absolute; top: calc(100% + 8px); left: 0; min-width: 210px; background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow); padding: 8px; opacity: 0; visibility: hidden; transform: translateY(6px); transition: all .16s ease; }
        .lpn-item:hover .lpn-menu, .lpn-item.open .lpn-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .lpn-menu a { display: flex; align-items: center; gap: 10px; padding: 9px 11px; border-radius: 9px; font-size: 13.5px; font-weight: 600; color: var(--ink-2); }
        .lpn-menu a:hover { background: var(--bg-soft-2); color: var(--blue); }
        .lpn-menu a svg { width: 16px; height: 16px; color: var(--blue); flex-shrink: 0; }
        .lpn-actions { display: flex; align-items: center; gap: 14px; margin-left: auto; flex-shrink: 0; }
        .lpn-ic { display: inline-flex; flex-direction: column; align-items: center; gap: 2px; color: var(--ink-2); font-size: 10.5px; font-weight: 600; position: relative; }
        .lpn-ic:hover { color: var(--blue); }
        .lpn-ic svg { width: 21px; height: 21px; }
        .lpn-ic .dot { position: absolute; top: -3px; right: 6px; min-width: 15px; height: 15px; background: var(--orange); color: #fff; font-size: 9px; font-weight: 800; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 3px; border: 2px solid #fff; }
        .lp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; font-family: var(--ff); font-weight: 700; font-size: 14px; border-radius: 10px; padding: 10px 18px; cursor: pointer; border: 1.5px solid transparent; transition: transform .12s, box-shadow .15s, background .15s; white-space: nowrap; }
        .lp-btn:active { transform: translateY(1px); }
        .lp-btn-outline { background: #fff; border-color: var(--line); color: var(--ink); }
        .lp-btn-outline:hover { border-color: var(--blue); color: var(--blue); }
        .lp-btn-orange { background: linear-gradient(135deg, #fb923c, var(--orange-dark)); color: #fff; box-shadow: 0 8px 20px rgba(234, 88, 12, 0.28); }
        .lp-btn-orange:hover { box-shadow: 0 10px 26px rgba(234, 88, 12, 0.38); }
        .lp-btn-blue { background: linear-gradient(135deg, var(--blue-light), var(--blue-dark)); color: #fff; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.28); }
        .lp-btn-blue:hover { box-shadow: 0 10px 26px rgba(37, 99, 235, 0.38); }
        .lpn-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--line); display: block; }
        .lpn-avatar-btn { background: none; border: none; padding: 0; cursor: pointer; display: flex; border-radius: 50%; }
        .lpn-avatar-btn:hover .lpn-avatar { border-color: var(--blue); }
        .lpn-menu-right { left: auto; right: 0; min-width: 180px; }
        .lpn-menu-logout { width: 100%; display: flex; align-items: center; gap: 10px; padding: 9px 11px; border-radius: 9px; font-size: 13.5px; font-weight: 600; color: #ef4444; background: none; border: none; font-family: inherit; cursor: pointer; text-align: left; }
        .lpn-menu-logout:hover { background: rgba(239,68,68,0.08); }
        .lpn-menu-logout svg { width: 16px; height: 16px; color: #ef4444; flex-shrink: 0; }
        .lpn-burger { display: none; background: none; border: 1px solid var(--line); border-radius: 9px; width: 42px; height: 40px; font-size: 20px; color: var(--ink); cursor: pointer; }

        /* ── Footer ───────────────────────────────────────────── */
        .lpf { background: var(--ink); color: #cdd5e4; padding: 64px 0 30px; margin-top: 0; }
        .lpf-grid { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr 1fr; gap: 36px; }
        .lpf-brand-mark { display: flex; align-items: center; gap: 11px; margin-bottom: 16px; }
        .lpf-brand-mark .lpn-word b { color: #fff; }
        .lpf-brand-mark .lpn-word span { color: #6b7896; }
        .lpf-about { font-size: 13.5px; line-height: 1.7; color: #94a0ba; max-width: 260px; margin: 0 0 18px; }
        .lpf-socials { display: flex; gap: 10px; }
        .lpf-social { width: 36px; height: 36px; border-radius: 9px; background: rgba(255, 255, 255, 0.07); display: flex; align-items: center; justify-content: center; color: #cdd5e4; transition: background .15s, color .15s, transform .15s; }
        .lpf-social:hover { background: var(--blue); color: #fff; transform: translateY(-2px); }
        .lpf-social svg { width: 17px; height: 17px; }
        .lpf-col h4 { font-family: var(--ff); color: #fff; font-size: 13px; font-weight: 800; letter-spacing: .3px; margin: 0 0 16px; text-transform: capitalize; }
        .lpf-col ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 11px; }
        .lpf-col a { font-size: 13.5px; color: #94a0ba; transition: color .15s; display: inline-flex; align-items: center; gap: 7px; }
        .lpf-col a:hover { color: #fff; }
        .lpf-new-badge { font-size: 8.5px; font-weight: 800; background: var(--orange); color: #fff; padding: 1px 6px; border-radius: 5px; letter-spacing: .5px; }
        .lpf-news p { font-size: 13.5px; color: #94a0ba; line-height: 1.6; margin: 0 0 14px; }
        .lpf-news-form { display: flex; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 11px; padding: 5px; }
        .lpf-news-form input { flex: 1; min-width: 0; background: none; border: none; outline: none; color: #fff; font-size: 13px; padding: 8px 10px; font-family: inherit; }
        .lpf-news-form input::placeholder { color: #6b7896; }
        .lpf-news-form button { background: var(--blue); border: none; border-radius: 8px; width: 38px; height: 36px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .lpf-news-form button:hover { background: var(--blue-dark); }
        .lpf-news-ok { font-size: 12.5px; color: #34d399; margin: 10px 0 0; display: none; }
        .lpf-bottom { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; border-top: 1px solid rgba(255, 255, 255, 0.08); margin-top: 44px; padding-top: 24px; font-size: 12.5px; color: #6b7896; }
        .lpf-bottom a { color: #94a0ba; }
        .lpf-bottom a:hover { color: #fff; }

        @media (max-width: 980px) {
            .lpn-links { display: none; }
            .lpn-burger { display: inline-flex; align-items: center; justify-content: center; }
            .lpn-ic span { display: none; }
            .lpf-grid { grid-template-columns: 1fr 1fr; gap: 28px; }
        }
        @media (max-width: 560px) {
            .lpf-grid { grid-template-columns: 1fr; }
            .lpn-actions .lp-btn-outline { display: none; }
        }
    </style>

    @include('partials._a11y')
    @stack('styles')
</head>
<body>
<a href="#main-content" class="skip-to-content">Skip to main content</a>

@php
    // Reusable inline brand mark (blue hexagon + orange spark).
    $lpMark = '<svg class="lpn-mark" viewBox="0 0 40 44" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="lpHex" x1="0" y1="0" x2="40" y2="44" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><path d="M18.5 1.2a3 3 0 0 1 3 0l14.5 8.37a3 3 0 0 1 1.5 2.6v16.66a3 3 0 0 1-1.5 2.6L21.5 42.8a3 3 0 0 1-3 0L4 34.43a3 3 0 0 1-1.5-2.6V15.17a3 3 0 0 1 1.5-2.6L18.5 1.2Z" fill="url(#lpHex)"/><path d="M20 12.5l2.4 5.1 5.6.7-4.1 3.9 1.05 5.5L20 25.1l-4.95 2.6L16.1 22.2 12 18.3l5.6-.7L20 12.5Z" fill="#f97316"/><path d="M20 12.5l2.4 5.1 5.6.7-4.1 3.9 1.05 5.5L20 25.1V12.5Z" fill="#fb923c"/></svg>';
@endphp

<nav class="lpn" aria-label="Main navigation">
    <div class="lp-container lpn-inner">
        <a href="{{ route('landing') }}" class="lpn-brand" aria-label="{{ config('app.name') }} home">
            <img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="{{ config('app.name') }}" class="lpn-logo-img">
        </a>

        <div class="lpn-links">
            <div class="lpn-item">
                @php
                    $__fgActive = auth()->user()?->activeRole();
                    // Clients browse professional PACKAGES; only professionals "find gigs" (work).
                    $__fgHref = $__fgActive === 'supplier'
                        ? route('professional.bidding-board.index')
                        : ($__fgActive === 'client' ? route('public.packages') : route('public.browse'));
                    $__fgLabel = $__fgActive === 'client' ? 'Browse Packages' : 'Find Gigs';
                @endphp
                <a href="{{ $__fgHref }}" class="lpn-link">{{ $__fgLabel }}</a>
            </div>
            <div class="lpn-item">
                <span class="lpn-link">Find Professionals <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg></span>
                <div class="lpn-menu">
                    <a href="{{ route('public.browse') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Browse Professionals</a>
                    <a href="{{ route('register', ['role' => 'client']) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>Post Your Event</a>
                </div>
            </div>
            <div class="lpn-item">
                <span class="lpn-link">Categories <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg></span>
                <div class="lpn-menu">
                    <a href="{{ route('public.packages') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg><span style="display:flex;flex-direction:column;gap:1px;"><span style="font-weight:600;">Shop Packages</span><span style="font-size:11px;color:var(--muted);font-weight:500;">Browse complete event solutions</span></span></a>
                    <a href="{{ route('public.event-types') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2c0 2 .5 3.5 2 5"/><path d="M11.5 13.5 21 4"/></svg><span style="display:flex;flex-direction:column;gap:1px;"><span style="font-weight:600;">Event Types</span><span style="font-size:11px;color:var(--muted);font-weight:500;">Browse by occasion</span></span></a>
                    <a href="{{ route('public.packages', ['sort' => 'trending']) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.07-2.14-.22-4.05 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.15.43-2.29 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg><span style="display:flex;flex-direction:column;gap:1px;"><span style="font-weight:600;">Trending Packages</span><span style="font-size:11px;color:var(--muted);font-weight:500;">See what's popular this week</span></span></a>
                    <a href="{{ route('events-categories') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><span style="display:flex;flex-direction:column;gap:1px;"><span style="font-weight:600;">View All Categories</span><span style="font-size:11px;color:var(--muted);font-weight:500;">Explore everything</span></span></a>
                </div>
            </div>
            <div class="lpn-item">
                <span class="lpn-link">Resources <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg></span>
                <div class="lpn-menu">
                    <a href="{{ route('blog.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Blog</a>
                    <a href="{{ route('public.how-it-works') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>How It Works</a>
                    <a href="{{ route('public.faq') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>FAQ</a>
                    <a href="{{ route('about-us') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>About Us</a>
                </div>
            </div>
            <div class="lpn-item">
                <a href="{{ route('landing') }}#pricing" class="lpn-link">Pricing</a>
            </div>
        </div>

        <div class="lpn-actions">
            @auth
                <a href="{{ url('/dashboard') }}" class="lpn-ic" title="Saved">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span>Favorites</span>
                </a>
                <a href="{{ url('/dashboard') }}" class="lpn-ic" title="Messages">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Messages</span>
                </a>
                <div class="lpn-item lpn-profile">
                    <button type="button" class="lpn-avatar-btn" aria-label="Account menu" aria-haspopup="true">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="lpn-avatar">
                    </button>
                    <div class="lpn-menu lpn-menu-right">
                        <a href="{{ url('/dashboard') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" class="lpn-menu-logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="lpn-ic" title="Favorites">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span>Favorites</span>
                </a>
                <a href="{{ route('login') }}" class="lpn-ic" title="Messages">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Messages</span>
                </a>
                <a href="{{ route('login') }}" class="lp-btn lp-btn-outline">Log In</a>
                <a href="{{ route('register') }}" class="lp-btn lp-btn-orange">Sign Up</a>
            @endauth
            <button type="button" class="lpn-burger" id="lpnBurger" aria-label="Menu">&#9776;</button>
        </div>
    </div>
    {{-- Mobile dropdown --}}
    <div id="lpnMobile" style="display:none;background:#fff;border-top:1px solid var(--line);padding:12px 24px 18px;">
        <a href="{{ route('public.browse') }}" style="display:block;padding:11px 4px;font-weight:600;color:var(--ink-2);border-bottom:1px solid var(--line-soft);">Find Gigs</a>
        <a href="{{ route('public.browse') }}" style="display:block;padding:11px 4px;font-weight:600;color:var(--ink-2);border-bottom:1px solid var(--line-soft);">Find Professionals</a>
        <a href="{{ route('events-categories') }}" style="display:block;padding:11px 4px;font-weight:600;color:var(--ink-2);border-bottom:1px solid var(--line-soft);">Categories</a>
        <a href="{{ route('blog.index') }}" style="display:block;padding:11px 4px;font-weight:600;color:var(--ink-2);border-bottom:1px solid var(--line-soft);">Resources</a>
        <a href="{{ route('landing') }}#pricing" style="display:block;padding:11px 4px;font-weight:600;color:var(--ink-2);">Pricing</a>
    </div>
</nav>

<main id="main-content" tabindex="-1">
    @yield('content')
</main>

<footer class="lpf" role="contentinfo">
    <div class="lp-container">
        <div class="lpf-grid">
            <div>
                <div class="lpf-brand-mark">
                    <img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="{{ config('app.name') }}" class="lpf-logo-img">
                </div>
                <p class="lpf-about">The all-in-one marketplace connecting clients with verified event professionals to plan, manage, and deliver unforgettable experiences.</p>
                <div class="lpf-socials">
                    <a href="https://www.facebook.com/gigresource/" target="_blank" rel="noopener" class="lpf-social" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="https://www.instagram.com/gigresource2025/" target="_blank" rel="noopener" class="lpf-social" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    <a href="https://www.linkedin.com/company/gigresource/" target="_blank" rel="noopener" class="lpf-social" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.4c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V21h-4z"/></svg></a>
                    <a href="https://www.tiktok.com/@gigresource123/" target="_blank" rel="noopener" class="lpf-social" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 1 1 .79-5.68v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 1 0 15.81 15V8.71a8.21 8.21 0 0 0 4.76 1.52V6.69h-.98z"/></svg></a>
                </div>
            </div>
            <div class="lpf-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="{{ route('public.browse') }}">Find Gigs</a></li>
                    <li><a href="{{ route('public.browse') }}">Find Professionals</a></li>
                    <li><a href="{{ route('events-categories') }}">Categories</a></li>
                    <li><a href="{{ url('/dashboard') }}">AI Toolkit <span class="lpf-new-badge">NEW</span></a></li>
                    <li><a href="{{ route('landing') }}#pricing">Pricing</a></li>
                </ul>
            </div>
            <div class="lpf-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a href="{{ route('public.faq') }}">Help Center</a></li>
                    <li><a href="{{ route('public.how-it-works') }}">Guides</a></li>
                    <li><a href="{{ route('public.how-it-works') }}">Templates</a></li>
                    <li><a href="{{ route('about-us') }}">Success Stories</a></li>
                </ul>
            </div>
            <div class="lpf-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('about-us') }}">About Us</a></li>
                    <li><a href="{{ route('about-us') }}">Careers</a></li>
                    <li><a href="{{ route('blog.index') }}">Press</a></li>
                    <li><a href="{{ route('about-us') }}">Contact Us</a></li>
                </ul>
            </div>
            <div class="lpf-col lpf-news">
                <h4>Stay in the Loop</h4>
                <p>Get event tips, trends, and updates straight to your inbox.</p>
                <form class="lpf-news-form" id="lpfNews" onsubmit="return false;">
                    <input type="email" placeholder="Enter your email" aria-label="Email address" required>
                    <button type="submit" aria-label="Subscribe"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
                </form>
                <p class="lpf-news-ok" id="lpfNewsOk">Thanks — you're on the list! 🎉</p>
            </div>
        </div>
        <div class="lpf-bottom">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <span>
                <a href="{{ route('privacy-policy') }}">Privacy Policy</a> &middot;
                <a href="{{ route('payment-policy') }}">Payment Policy</a> &middot;
                <a href="{{ route('cancellation-policy') }}">Cancellation &amp; Refund</a> &middot;
                <a href="{{ route('dmca-policy') }}">DMCA</a> &middot;
                <a href="{{ route('platform-disclaimer') }}">Disclaimer</a>
            </span>
        </div>
    </div>
</footer>

<script>
    (function () {
        // Mobile menu toggle
        var burger = document.getElementById('lpnBurger');
        var mobile = document.getElementById('lpnMobile');
        if (burger && mobile) {
            burger.addEventListener('click', function () {
                mobile.style.display = mobile.style.display === 'none' ? 'block' : 'none';
            });
        }
        // Touch-friendly dropdown toggles
        document.querySelectorAll('.lpn-item').forEach(function (item) {
            var link = item.querySelector('.lpn-link');
            var menu = item.querySelector('.lpn-menu');
            if (link && menu) {
                link.addEventListener('click', function (e) {
                    if (window.matchMedia('(hover: none)').matches) {
                        e.preventDefault();
                        item.classList.toggle('open');
                    }
                });
            }
        });
        // Profile avatar dropdown — toggle on click (works on desktop + touch).
        var profileBtn = document.querySelector('.lpn-avatar-btn');
        if (profileBtn) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                profileBtn.closest('.lpn-item').classList.toggle('open');
            });
        }
        document.addEventListener('click', function (e) {
            document.querySelectorAll('.lpn-item.open').forEach(function (i) {
                if (!i.contains(e.target)) i.classList.remove('open');
            });
        });
        // Newsletter fake-submit (no backend list yet)
        var news = document.getElementById('lpfNews');
        var ok = document.getElementById('lpfNewsOk');
        if (news && ok) {
            news.addEventListener('submit', function () {
                ok.style.display = 'block';
                news.reset();
            });
        }
        // decoding hint
        document.querySelectorAll('img:not([decoding])').forEach(function (img) { img.setAttribute('decoding', 'async'); });
    })();
</script>
@stack('scripts')
@include('partials._mobile_fixes')
</body>
</html>
