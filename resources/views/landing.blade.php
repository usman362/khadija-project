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

    /* ─── HOW IT WORKS BG ─────────────────── */
    #how-it-works { position: relative; overflow: hidden; }
    #how-it-works::before {
        content: '';
        position: absolute;
        top: 10%; right: -200px;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(139,92,246,0.08), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
</style>
@endpush

@section('content')


<!-- ─── HERO ─────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg">
        <img src="https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1600&q=80&auto=format&fit=crop" alt="Outdoor event festival with colorful lights and staging" loading="eager">
    </div>
    <div class="container">
        <h1>Find The Right<br><span class="gradient-text">Professional</span> For<br>Every Event</h1>
        <p class="hero-subtitle">
            GigResource connects event organizers with verified professionals. Book photographers, DJs, caterers,
            decorators, and more &mdash; all in one platform.
        </p>
        <div class="hero-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-blue btn-lg">Join as Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-red btn-lg">Hire Now</a>
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

<!-- ─── HOW IT WORKS ──────────────────────────── -->
<section class="section" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2>Getting Started is Easy</h2>
            <p>A simple, transparent process for planners and professionals.</p>
        </div>

        {{--
            Journey layout: four numbered steps threaded by a dashed path,
            with illustration and copy alternating sides. See `.journey*`
            rules in _public_styles.blade.php for responsive behaviour.
        --}}
        <div class="journey">
            <div class="journey-step step-1">
                <div class="journey-copy">
                    <h3>Post Your Event</h3>
                    <p>Tell us what you're planning — dates, venue, guest count, the vibe. We'll translate that into a brief the right pros can actually respond to.</p>
                    <span class="journey-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Takes about 2 minutes
                    </span>
                </div>
                <div class="journey-num">1</div>
                <div class="journey-art" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
            </div>

            <div class="journey-step step-2">
                <div class="journey-copy">
                    <h3>Get Matched</h3>
                    <p>Our AI matchmaking surfaces pros who fit your budget, date, and style — with verified trade licenses, liability insurance, and real reviews right on the card.</p>
                    <span class="journey-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.5 6.5L21 9l-5 4.5L17.5 21 12 17.5 6.5 21 8 13.5 3 9l6.5-.5z"/></svg>
                        AI-powered suggestions
                    </span>
                </div>
                <div class="journey-num">2</div>
                <div class="journey-art" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>

            <div class="journey-step step-3">
                <div class="journey-copy">
                    <h3>Chat &amp; Agree</h3>
                    <p>Message pros in real time, share references, and let our AI turn the conversation into a professional service agreement — no lawyer required.</p>
                    <span class="journey-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        Built-in agreements
                    </span>
                </div>
                <div class="journey-num">3</div>
                <div class="journey-art" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
            </div>

            <div class="journey-step step-4">
                <div class="journey-copy">
                    <h3>Book Safely</h3>
                    <p>Pay securely through the platform, keep every message and file in one place, and get payout protection on every confirmed booking.</p>
                    <span class="journey-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        Escrow-style protection
                    </span>
                </div>
                <div class="journey-num">4</div>
                <div class="journey-art" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 48px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Join as Professional</a>
                <a href="{{ route('register') }}" class="btn btn-outline btn-lg">Hire a Professional</a>
            @endauth
        </div>
    </div>
</section>

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
            <h2>Trusted by Planners & Professionals</h2>
            <p>Here's what our community says about {{ config('app.name', 'Khadija') }}.</p>
        </div>

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
                        <img src="https://images.unsplash.com/photo-1531384441138-2736e62e0919?w=200&q=80&auto=format&fit=crop&crop=faces" alt="Ahmed J.">
                    </div>
                    <div>
                        <div class="testimonial-author-name">Ahmed J.</div>
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
