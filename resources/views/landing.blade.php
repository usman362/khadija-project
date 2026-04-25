@extends('layouts.public')

@section('title', config('app.name', 'Khadija') . ' - Host Unforgettable Events With Confidence')

@push('styles')
<style>
    /* ─── FEATURED CATEGORIES ─────────────────── */
    .featured-cats { padding: 80px 0; }
    .featured-cats .section-header { text-align: center; margin-bottom: 56px; }
    .featured-cats .section-header h2 {
        font-size: 2.25rem;
        font-weight: 800;
        margin-bottom: 12px;
    }
    .featured-cats .section-header h2 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .featured-cats .section-header p { color: var(--text-muted); font-size: 1.05rem; }

    .cats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .cat-tile {
        position: relative;
        border-radius: 18px;
        overflow: hidden;
        aspect-ratio: 4 / 5;
        cursor: pointer;
        background: var(--bg-card);
        transition: transform 0.35s, box-shadow 0.35s;
    }
    .cat-tile:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 50px rgba(0,0,0,0.45);
    }
    .cat-tile img {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    .cat-tile:hover img { transform: scale(1.08); }
    .cat-tile-gradient {
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(11,15,26,0.25) 45%, rgba(11,15,26,0.95) 100%);
    }
    .cat-tile-content {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        padding: 24px;
        color: #fff;
    }
    .cat-tile-tag {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        background: rgba(59,130,246,0.9);
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        margin-bottom: 10px;
        backdrop-filter: blur(6px);
    }
    .cat-tile h3 {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .cat-tile-meta {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.85);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cat-tile-meta svg { width: 14px; height: 14px; }
    @media (max-width: 1024px) { .cats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .cats-grid { grid-template-columns: 1fr; } .cat-tile { aspect-ratio: 5 / 4; } }

    /* ─── GALLERY / MOMENTS ─────────────────── */
    .moments-section {
        padding: 80px 0;
        background: var(--bg-section);
    }
    .moments-section .section-header { text-align: center; margin-bottom: 48px; }
    .moments-section .section-header h2 { font-size: 2.25rem; font-weight: 800; margin-bottom: 12px; }
    .moments-section .section-header h2 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .moments-section .section-header p { color: var(--text-muted); font-size: 1.05rem; }

    .moments-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: 220px 220px;
        gap: 12px;
    }
    .moment {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: var(--bg-card);
    }
    .moment img {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    .moment:hover img { transform: scale(1.1); }
    .moment::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0), rgba(11,15,26,0.7) 100%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .moment:hover::after { opacity: 1; }
    .moment-label {
        position: absolute;
        left: 14px; bottom: 14px;
        color: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        opacity: 0;
        transform: translateY(8px);
        transition: all 0.3s;
        z-index: 2;
        text-shadow: 0 1px 8px rgba(0,0,0,0.6);
    }
    .moment:hover .moment-label { opacity: 1; transform: translateY(0); }
    .moment--wide { grid-column: span 2; }
    .moment--tall { grid-row: span 2; }
    @media (max-width: 900px) {
        .moments-grid { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(4, 180px); }
        .moment--wide, .moment--tall { grid-column: auto; grid-row: auto; }
    }

    /* ─── NEWSLETTER DECO ─────────────────── */
    .newsletter {
        position: relative;
        overflow: hidden;
    }
    .newsletter::before {
        content: '';
        position: absolute;
        top: -120px; left: -80px;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(59,130,246,0.18), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .newsletter::after {
        content: '';
        position: absolute;
        bottom: -120px; right: -80px;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(139,92,246,0.18), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .newsletter .container { position: relative; z-index: 1; }

    /* ─── CTA BANNER IMAGE ─────────────────── */
    .cta-image { padding: 0 !important; background: transparent !important; }
    .cta-image-wrap {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 300px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,0.4);
    }
    .cta-image-wrap img {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .cta-image-wrap::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(139,92,246,0.15));
        pointer-events: none;
    }

    /* ─── TESTIMONIAL AVATARS ─────────────────── */
    .testimonial-avatar.real-avatar {
        padding: 0;
        overflow: hidden;
    }
    .testimonial-avatar.real-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ══════════════════════════════════════════════════════
       GIGSALAD-INSPIRED ADDITIONS
       Hero trust pill + search + category chips, and the
       A-Z category expander.
       Dark theme is preserved — warm coral/peach is used as
       the playful accent against the existing navy base.
       ══════════════════════════════════════════════════════ */
    :root {
        --warm-coral: #ff7a59;
        --warm-coral-dark: #e8583a;
        --warm-peach: #ffb08a;
        --warm-cream: #fff4eb;
    }

    /* Trust pill — frosted glass chip. Heavier blur, thinner bg,
       subtle inset highlight on the top edge (classic glass trick),
       white text held up by a soft text-shadow so it reads on any
       image behind it. Coral is reserved for a whisper-thin ring. */
    .hero-trust-pill {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1.5px solid rgba(255, 122, 89, 0.55);
        backdrop-filter: blur(20px) saturate(1.4);
        -webkit-backdrop-filter: blur(20px) saturate(1.4);
        border-radius: 999px;
        font-size: 0.92rem;
        font-weight: 500;
        letter-spacing: 0.2px;
        color: #fff;
        margin-bottom: 24px;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.22),
            inset 0 -1px 0 rgba(0, 0, 0, 0.08),
            0 0 0 4px rgba(255, 122, 89, 0.1),
            0 8px 28px rgba(0, 0, 0, 0.25);
        text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
    }
    .hero-trust-pill .stars {
        display: inline-flex;
        gap: 2px;
        padding-right: 8px;
        border-right: 1px solid rgba(255, 122, 89, 0.3);
    }
    .hero-trust-pill .stars svg {
        width: 13px; height: 13px;
        fill: #ffc15c;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
    }
    .hero-trust-pill > svg:not(.stars svg) {
        width: 15px; height: 15px;
        color: #ffc15c;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
    }

    /* Unified search + category chip strip in hero */
    .hero-finder {
        margin-top: 36px;
        max-width: 720px;
    }
    .hero-finder-search {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.08);
        border: 1.5px solid rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(16px);
        border-radius: 16px;
        padding: 8px 8px 8px 20px;
        transition: border-color 0.2s, background 0.2s;
    }
    .hero-finder-search:focus-within {
        border-color: var(--warm-coral);
        background: rgba(255, 255, 255, 0.12);
    }
    .hero-finder-search svg.search-icon {
        width: 20px; height: 20px;
        color: rgba(255, 255, 255, 0.6);
        flex-shrink: 0;
    }
    .hero-finder-search input {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        color: #fff;
        font-size: 1rem;
        padding: 14px 4px;
        font-family: inherit;
    }
    .hero-finder-search input::placeholder { color: rgba(255, 255, 255, 0.55); }
    .hero-finder-search button {
        background: linear-gradient(135deg, var(--warm-coral), var(--warm-coral-dark));
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        flex-shrink: 0;
    }
    .hero-finder-search button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(255, 122, 89, 0.35);
    }
    .hero-finder-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }
    .hero-finder-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }
    .hero-finder-chip:hover {
        background: rgba(255, 122, 89, 0.18);
        border-color: rgba(255, 122, 89, 0.5);
        color: #fff;
        transform: translateY(-1px);
    }
    .hero-finder-chip svg { width: 14px; height: 14px; }

    /* Photo-collage strip for hero — floating tiles, GigSalad's signature */
    .hero-collage {
        position: absolute;
        right: -40px;
        top: 50%;
        transform: translateY(-50%);
        display: grid;
        grid-template-columns: 160px 160px;
        grid-auto-rows: 200px;
        gap: 16px;
        pointer-events: none;
        opacity: 0.9;
        max-width: 380px;
    }
    .hero-collage .tile {
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .hero-collage .tile img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .hero-collage .tile.t1 { transform: rotate(-3deg); }
    .hero-collage .tile.t2 { transform: rotate(4deg) translateY(20px); }
    .hero-collage .tile.t3 { transform: rotate(2deg) translateY(-10px); }
    .hero-collage .tile.t4 { transform: rotate(-5deg) translateY(10px); }
    @media (max-width: 1100px) { .hero-collage { display: none; } }

    /* ── A-Z CATEGORY BROWSE GRID ── */
    .az-section { padding: 100px 0; position: relative; overflow: hidden; }
    .az-section::before {
        content: '';
        position: absolute;
        top: -200px; left: -200px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(255, 122, 89, 0.08), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .az-section::after {
        content: '';
        position: absolute;
        bottom: -200px; right: -200px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.06), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .az-section .container { position: relative; z-index: 1; }
    .az-section .section-header { text-align: center; margin-bottom: 56px; }
    .az-section h2 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 14px;
        max-width: 820px;
        margin-left: auto; margin-right: auto;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    .az-section h2 .gradient-text {
        background: linear-gradient(135deg, var(--warm-coral), var(--warm-peach));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .az-section .section-header p { color: var(--text-muted); font-size: 1.05rem; }

    .az-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    @media (max-width: 1100px) { .az-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; } }
    @media (max-width: 600px) { .az-grid { grid-template-columns: 1fr; } }

    /* Per-column accent hues cycle through coral / violet / teal / amber
       so each card has its own personality without drifting from brand. */
    .az-column {
        position: relative;
        background: linear-gradient(180deg, var(--bg-card) 0%, rgba(15, 22, 41, 0.5) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 22px;
        padding: 28px 24px 20px;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.3, 1),
                    border-color 0.3s,
                    box-shadow 0.3s;
    }
    .az-column::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--az-accent, var(--warm-coral)), transparent);
        opacity: 0.85;
    }
    .az-column::after {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 120px; height: 120px;
        background: radial-gradient(circle, var(--az-accent, var(--warm-coral)) 0%, transparent 70%);
        opacity: 0.12;
        pointer-events: none;
        transition: opacity 0.3s, transform 0.3s;
    }
    .az-column:hover {
        transform: translateY(-6px);
        border-color: rgba(255, 255, 255, 0.16);
        box-shadow:
            0 16px 40px rgba(0, 0, 0, 0.35),
            0 0 0 1px var(--az-accent, var(--warm-coral));
    }
    .az-column:hover::after { opacity: 0.25; transform: scale(1.1); }

    /* Color tokens per column index — cycled via :nth-child */
    .az-column:nth-child(1) { --az-accent: #ff7a59; }   /* coral */
    .az-column:nth-child(2) { --az-accent: #8b5cf6; }   /* violet */
    .az-column:nth-child(3) { --az-accent: #06b6d4; }   /* teal */
    .az-column:nth-child(4) { --az-accent: #f59e0b; }   /* amber */

    .az-column-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 18px;
        margin-bottom: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .az-column-head .ic {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--az-accent, #ff7a59) 18%, transparent);
        border: 1px solid color-mix(in srgb, var(--az-accent, #ff7a59) 35%, transparent);
        display: flex; align-items: center; justify-content: center;
        color: var(--az-accent, #ff7a59);
        font-weight: 800;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
        flex-shrink: 0;
    }
    .az-column-head .meta { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .az-column-head .label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .az-column-head .range {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
    }

    .az-links {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .az-links a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        margin: 0 -12px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.93rem;
        font-weight: 500;
        text-decoration: none;
        border-radius: 10px;
        transition: background 0.18s, color 0.18s, transform 0.18s;
    }
    .az-links a .name {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .az-links a .name .emoji {
        width: 26px;
        height: 26px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0;
        line-height: 1;
        color: color-mix(in srgb, var(--az-accent, #ff7a59) 90%, #fff);
        background: color-mix(in srgb, var(--az-accent, #ff7a59) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--az-accent, #ff7a59) 28%, transparent);
        border-radius: 7px;
        overflow: hidden;
        text-transform: uppercase;
    }
    .az-links a:hover .name .emoji {
        background: color-mix(in srgb, var(--az-accent, #ff7a59) 24%, transparent);
        border-color: color-mix(in srgb, var(--az-accent, #ff7a59) 45%, transparent);
    }
    .az-links a .name .label-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .az-links a .arrow {
        opacity: 0;
        transform: translateX(-4px);
        color: var(--az-accent, var(--warm-coral));
        transition: opacity 0.18s, transform 0.18s;
        flex-shrink: 0;
    }
    .az-links a:hover {
        background: color-mix(in srgb, var(--az-accent, #ff7a59) 10%, transparent);
        color: #fff;
    }
    .az-links a:hover .arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .az-column-foot {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px dashed rgba(255, 255, 255, 0.1);
        text-align: right;
    }
    .az-column-foot a {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--az-accent, var(--warm-coral));
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: gap 0.18s;
    }
    .az-column-foot a:hover { gap: 8px; }
</style>
@endpush

@section('content')


<!-- ─── HERO ─────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg">
        <img src="https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1600&q=80&auto=format&fit=crop" alt="Outdoor event festival with colorful lights and staging" loading="eager">
    </div>
    <div class="container" style="position: relative;">
        {{-- Trust pill — GigSalad's signature opener. Uses REAL review count
             from the DB so it's never a lie. Falls back to a generic line
             until the first few reviews land. --}}
        @if($stats['reviews_count'] >= 5)
            <div class="hero-trust-pill">
                <span class="stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </span>
                Over {{ number_format($stats['reviews_count']) }} five-star reviews
            </div>
        @else
            <div class="hero-trust-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Verified professionals, secure bookings
            </div>
        @endif

        <h1>Find The Right<br><span class="gradient-text">Professional</span> For<br>Every Event</h1>
        <p class="hero-subtitle">
            GigResource connects event organizers with verified professionals. Book photographers, DJs, caterers,
            decorators, and more &mdash; all in one platform.
        </p>

        {{-- GigSalad-style search: big unified bar + category quick-pick
             chips. The form submits to the public categories page with a
             search query so discovery still works even without JS. --}}
        <form class="hero-finder" action="{{ route('public.browse') }}" method="GET">
            <div class="hero-finder-search">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" placeholder="Find photographers, DJs, caterers, venues..." autocomplete="off">
                <button type="submit">Search</button>
            </div>
            @if($categories->isNotEmpty())
                <div class="hero-finder-chips">
                    @foreach($categories->take(6) as $cat)
                        <a href="{{ route('public.browse', ['q' => $cat->name]) }}" class="hero-finder-chip">
                            @if($cat->icon)
                                <span style="font-size: 0.95rem;">{{ $cat->icon }}</span>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                            @endif
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        <div class="hero-buttons" style="margin-top: 32px;">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-blue btn-lg">Start Planning</a>
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-red btn-lg">List Your Services</a>
            @endauth
        </div>

        <div class="trust-badges">
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h4>Verified Experts</h4>
                <p>Vetted professionals only</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <h4>Secure Payments</h4>
                <p>Safe & trusted transactions</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h4>Event Categories</h4>
                <p>Browse all types of events</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h4>24/7 Support</h4>
                <p>We're here to help anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- ─── ABOUT US ─────────────────────────────── -->
<section class="section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h3>About Us</h3>
                <h2>We Connect <span class="gradient-text">Talent</span> With Opportunity</h2>
                <p>
                    GigResource is a next-generation marketplace designed to bridge the gap between skilled event
                    professionals and clients who need them. Whether you're planning a wedding, corporate event,
                    or private celebration, we make it effortless to find, book, and collaborate with top-tier talent.
                </p>
                <p>
                    Our platform handles everything from discovery to secure payments, real-time messaging,
                    and professional service agreements &mdash; so you can focus on what matters: creating
                    unforgettable experiences.
                </p>
                <div class="about-stats">
                    <div class="about-stat">
                        <h4>500+</h4>
                        <p>Professionals</p>
                    </div>
                    <div class="about-stat">
                        <h4>1,200+</h4>
                        <p>Events Booked</p>
                    </div>
                    <div class="about-stat">
                        <h4>98%</h4>
                        <p>Satisfaction</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&q=80" alt="Event planning team" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ─── FEATURED CATEGORIES ─────────────────── -->
<section class="featured-cats" id="categories-preview">
    <div class="container">
        <div class="section-header">
            <h2>Explore <span class="gradient-text">Event Categories</span></h2>
            <p>From intimate celebrations to grand corporate gatherings — find specialists for every occasion.</p>
        </div>
        <div class="cats-grid">
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80&auto=format&fit=crop" alt="Elegant wedding reception" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag">Trending</span>
                    <h3>Weddings</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        120+ professionals
                    </div>
                </div>
            </a>
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&q=80&auto=format&fit=crop" alt="Corporate conference" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag" style="background: rgba(139,92,246,0.9);">Corporate</span>
                    <h3>Conferences &amp; Summits</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                        80+ professionals
                    </div>
                </div>
            </a>
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=800&q=80&auto=format&fit=crop" alt="Birthday party with balloons" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag" style="background: rgba(236,72,153,0.9);">Popular</span>
                    <h3>Birthday Parties</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                        60+ professionals
                    </div>
                </div>
            </a>
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=800&q=80&auto=format&fit=crop" alt="Music concert stage lights" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag" style="background: rgba(6,182,212,0.9);">Live</span>
                    <h3>Concerts &amp; DJ Sets</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                        90+ professionals
                    </div>
                </div>
            </a>
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800&q=80&auto=format&fit=crop" alt="Graduation ceremony caps in the air" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag" style="background: rgba(245,158,11,0.9);">Milestone</span>
                    <h3>Graduations</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        35+ professionals
                    </div>
                </div>
            </a>
            <a href="{{ route('events-categories') }}" class="cat-tile">
                <img src="https://images.unsplash.com/photo-1507924538820-ede94a04019d?w=800&q=80&auto=format&fit=crop" alt="Holiday celebration dinner" loading="lazy">
                <div class="cat-tile-gradient"></div>
                <div class="cat-tile-content">
                    <span class="cat-tile-tag" style="background: rgba(34,197,94,0.9);">Seasonal</span>
                    <h3>Holiday &amp; Private</h3>
                    <div class="cat-tile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.39 7.36h7.73l-6.25 4.54L18.26 22 12 17.27 5.74 22l2.39-8.1L1.88 9.36h7.73z"/></svg>
                        50+ professionals
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- ─── A–Z CATEGORY EXPANDER ────────────────── -->
{{--
    GigSalad's signature "browse by category" strip, adapted to our
    Category model. We chunk the active categories into 4 columns so
    the grid reads like a directory — big SEO + discovery win.
    Hidden completely if the database has no active categories.
--}}
@if($categoryBuckets->isNotEmpty() && $categories->count() > 0)
<section class="az-section">
    <div class="container">
        <div class="section-header">
            <h2>From <span class="gradient-text">acoustic sets to zero-waste catering</span>, we've got every booking need covered.</h2>
            <p>Browse our full directory of event professionals, A to Z.</p>
        </div>
        <div class="az-grid">
            @foreach($categoryBuckets as $bucket)
                @php
                    $firstLetter = strtoupper($bucket->first()->name[0] ?? 'A');
                    $lastLetter  = strtoupper($bucket->last()->name[0] ?? 'Z');
                    $rangeLabel  = $firstLetter === $lastLetter ? $firstLetter : "{$firstLetter}–{$lastLetter}";
                @endphp
                <div class="az-column">
                    <div class="az-column-head">
                        <span class="ic">{{ $rangeLabel }}</span>
                        <div class="meta">
                            <span class="label">Categories</span>
                            <span class="range">{{ $bucket->count() }} {{ Str::plural('type', $bucket->count()) }}</span>
                        </div>
                    </div>
                    <ul class="az-links">
                        @foreach($bucket->take(6) as $cat)
                            <li>
                                <a href="{{ route('public.browse', ['q' => $cat->name]) }}">
                                    <span class="name">
                                        <span class="emoji">{{ strtoupper(mb_substr($cat->name, 0, 1)) }}</span>
                                        <span class="label-text">{{ $cat->name }}</span>
                                    </span>
                                    <span class="arrow">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if($bucket->count() > 6)
                        <div class="az-column-foot">
                            <a href="{{ route('public.browse') }}">
                                See all {{ $bucket->count() }}
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="{{ route('public.browse') }}" class="btn btn-outline btn-lg">Browse all professionals</a>
        </div>
    </div>
</section>
@endif

<!-- ─── CTA BANNER ────────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-content">
                <h2>Become a {{ config('app.name', 'Khadija') }} Professional</h2>
                <p>Partner with a leading platform, help others create amazing events, and earn competitive commissions for every successful referral.</p>
                <ul class="cta-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Earning Potential — Set your own rates
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Simple Tracking & Payments
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Grow your client base organically
                    </li>
                </ul>
                <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn btn-primary btn-lg">Start Today</a>
            </div>
            <div class="cta-image">
                <div class="cta-image-wrap">
                    <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=900&q=80&auto=format&fit=crop" alt="Event professionals collaborating" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── PRICING ───────────────────────────────── -->
<section class="section section-alt" id="pricing">
    <div class="container">
        <div class="section-header">
            <h2>Flexible Pricing for Every Need</h2>
            <p>Choose the perfect plan to launch your events to the next level.</p>
        </div>

        <div class="pricing-tabs">
            <div class="pricing-tab active">For Professionals</div>
            <div class="pricing-tab">For Clients</div>
        </div>

        <div class="pricing-toggle">
            <span class="toggle-label active" id="monthlyLabel">Monthly</span>
            <div class="toggle-switch" id="billingToggle" onclick="this.classList.toggle('yearly')"></div>
            <span class="toggle-label" id="yearlyLabel">Yearly</span>
            <span class="pricing-save">Save 15%</span>
        </div>

        <div class="pricing-grid">
            @php
                $planIcons = [
                    0 => ['bg' => 'rgba(107,114,128,0.15)', 'color' => '#9ca3af'],
                    1 => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                    2 => ['bg' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6'],
                    3 => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                ];
            @endphp

            @foreach($plans as $index => $plan)
                @php
                    $icon = $planIcons[$index % 4];
                @endphp
                <div class="pricing-card {{ $plan->is_featured ? 'featured' : '' }}">
                    @if($plan->badge_text)
                        <div class="pricing-badge badge-{{ $plan->badge_color ?? 'primary' }}">{{ $plan->badge_text }}</div>
                    @endif

                    <div class="pricing-card-icon" style="background: {{ $icon['bg'] }};">
                        @if($index === 0)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        @elseif($index === 1)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        @elseif($index === 2)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/><circle cx="12" cy="12" r="3"/></svg>
                        @endif
                    </div>

                    <div class="pricing-plan-name">{{ $plan->name }}</div>
                    <div class="pricing-plan-desc">{{ $plan->description ?? 'Perfect for your needs' }}</div>

                    <div class="pricing-amount">
                        <span class="pricing-currency">$</span>
                        <span class="pricing-value">{{ intval($plan->price) }}</span>
                        @if(!$plan->isFree())
                            <span class="pricing-cycle">{{ $plan->billingLabel() }}</span>
                        @endif
                    </div>

                    <ul class="pricing-features">
                        @if($plan->max_events)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_events }} events
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited events
                            </li>
                        @endif
                        @if($plan->max_bookings)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_bookings }} bookings
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited bookings
                            </li>
                        @endif
                        @foreach($plan->features as $feature)
                            <li class="{{ !$feature->is_included ? 'excluded' : '' }}">
                                @if($feature->is_included)
                                    <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    <svg class="cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                @endif
                                {{ $feature->feature }}
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <a href="{{ route('app.membership-plans.index') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ─── TESTIMONIALS ──────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2>What our customers are saying</h2>
            <p>Real reviews from planners and professionals — straight from the platform.</p>
        </div>

        {{-- If we have a real 5-star review in the DB, surface it as the
             big pull-quote above the hand-written fallback cards. The
             comment gets truncated to avoid a wall of text, and we fall
             back gracefully if the column is empty. --}}
        @if($featuredReview)
            <div class="testimonial-card" style="max-width: 720px; margin: 0 auto 48px; text-align: center;">
                <div class="testimonial-stars" style="justify-content: center;">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote style="font-size: 1.2rem; line-height: 1.55;">
                    &ldquo;{{ \Illuminate\Support\Str::limit($featuredReview->comment, 240) }}&rdquo;
                </blockquote>
                <div class="testimonial-author" style="justify-content: center;">
                    <div>
                        <div class="testimonial-author-name">{{ $featuredReview->reviewer?->name ?? 'A GigResource customer' }}</div>
                        <div class="testimonial-author-role">on booking with {{ $featuredReview->reviewee?->name ?? 'a professional' }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"{{ config('app.name') }} revolutionized how I manage events. It's intuitive, fast, and I found the perfect photographer for a last-minute wedding."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar real-avatar">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&q=80&auto=format&fit=crop&crop=faces" alt="Sarah K.">
                    </div>
                    <div>
                        <div class="testimonial-author-name">Sarah K.</div>
                        <div class="testimonial-author-role">Wedding Planner</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"The quality of professionals here is unmatched. The hiring process is as fair as it can get and it was flawless from start to finish."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar real-avatar">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80&auto=format&fit=crop&crop=faces" alt="Mike R.">
                    </div>
                    <div>
                        <div class="testimonial-author-name">Mike R.</div>
                        <div class="testimonial-author-role">Corporate Event Manager</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"As a DJ, I've doubled my bookings since joining. The platform makes it easy to showcase my work and connect with clients directly."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar real-avatar">
                        <img src="https://images.unsplash.com/photo-1531384441138-2736e62e0919?w=200&q=80&auto=format&fit=crop&crop=faces" alt="James T.">
                    </div>
                    <div>
                        <div class="testimonial-author-name">James T.</div>
                        <div class="testimonial-author-role">Professional DJ & Musician</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── MOMENTS GALLERY ───────────────────────── -->
<section class="moments-section">
    <div class="container">
        <div class="section-header">
            <h2>Crafted for <span class="gradient-text">Every Moment</span></h2>
            <p>A glimpse at events brought to life by our community of professionals.</p>
        </div>
        <div class="moments-grid">
            <div class="moment moment--wide moment--tall">
                <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=900&q=80&auto=format&fit=crop" alt="Graduation celebration" loading="lazy">
                <span class="moment-label">Graduation ceremonies</span>
            </div>
            <div class="moment">
                <img src="https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=600&q=80&auto=format&fit=crop" alt="Dance floor" loading="lazy">
                <span class="moment-label">Reception nights</span>
            </div>
            <div class="moment">
                <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=600&q=80&auto=format&fit=crop" alt="Floral decor" loading="lazy">
                <span class="moment-label">Decor &amp; florals</span>
            </div>
            <div class="moment moment--wide">
                <img src="https://images.unsplash.com/photo-1540317580384-e5d43616b9aa?w=900&q=80&auto=format&fit=crop" alt="Catering spread" loading="lazy">
                <span class="moment-label">Gourmet catering</span>
            </div>
        </div>
    </div>
</section>

<!-- ─── FAQ ───────────────────────────────────── -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-header">
            <h2>Frequently Asked <span class="gradient-text">Questions</span></h2>
            <p>Everything you need to know about using GigResource.</p>
        </div>
        <div class="faq-grid">
            @forelse($faqs as $faq)
                <div class="faq-item {{ $loop->first ? 'active' : '' }}">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ $faq->question }}</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">{!! $faq->answer !!}</div>
                    </div>
                </div>
            @empty
                {{-- Fallback if no FAQs in database yet --}}
                <div class="faq-item active">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>How does GigResource work?</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            GigResource connects event organizers (clients) with verified service professionals (suppliers). Simply create an account, browse available professionals by category, send booking requests, discuss details through our built-in chat, and confirm your booking.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- ─── NEWSLETTER ─────────────────────────────── -->
<section class="section section-alt newsletter">
    <div class="container">
        <h2>Get Eventful Updates!</h2>
        <p>Subscribe to our newsletter for the latest industry news, planning tips, and exclusive offers.</p>
        <div class="newsletter-form">
            <input type="email" placeholder="Enter your email address">
            <button class="btn btn-primary">Subscribe</button>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.pricing-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.pricing-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const isActive = item.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        if (!isActive) item.classList.add('active');
    }
</script>
@endpush
