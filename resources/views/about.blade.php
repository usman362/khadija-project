@extends('layouts.public')

@section('title', 'About Us - ' . config('app.name', 'Khadija'))

@push('styles')
<style>
    /* ─── HERO SECTION ──────────────────────────── */
    .about-hero {
        position: relative;
        padding: 180px 0 100px;
        text-align: center;
        overflow: hidden;
    }

    .about-hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }
    .about-hero-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.35;
    }
    .about-hero-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(11,15,26,0.6) 0%, rgba(11,15,26,0.85) 70%, var(--bg-dark) 100%);
    }
    .about-hero .container { position: relative; z-index: 1; }

    .about-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: 50%;
        transform: translateX(-50%);
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, rgba(139,92,246,0.1) 40%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
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

    .hero-badge svg { width: 14px; height: 14px; }

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

    .stat-item:hover { background: var(--bg-card-hover); }

    .stat-number {
        font-size: 2rem;
        font-weight: 800;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }

    .stat-item:nth-child(1) .stat-number { background: linear-gradient(135deg, #3b82f6, #06b6d4); -webkit-background-clip: text; background-clip: text; }
    .stat-item:nth-child(2) .stat-number { background: linear-gradient(135deg, #22c55e, #14b8a6); -webkit-background-clip: text; background-clip: text; }
    .stat-item:nth-child(3) .stat-number { background: linear-gradient(135deg, #f97316, #f59e0b); -webkit-background-clip: text; background-clip: text; }
    .stat-item:nth-child(4) .stat-number { background: linear-gradient(135deg, #ec4899, #8b5cf6); -webkit-background-clip: text; background-clip: text; }

    .stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 500;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ─── MISSION SECTION ──────────────────────────── */
    .mission-section { padding: 80px 0; position: relative; }

    .mission-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }

    /* Prevent grid children from overflowing their column due to
       the default min-width: auto on grid items. */
    .mission-grid > * { min-width: 0; }

    .mission-image-wrap {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        aspect-ratio: 16 / 10;
        max-height: 360px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.5);
    }
    .mission-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .mission-image-badge {
        position: absolute;
        left: 20px;
        bottom: 20px;
        padding: 14px 20px;
        background: rgba(11,15,26,0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff;
    }
    .mission-image-badge-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .mission-image-badge-icon svg { width: 20px; height: 20px; color: #fff; }
    .mission-image-badge-text { font-size: 0.85rem; }
    .mission-image-badge-text strong { display: block; font-weight: 700; font-size: 1rem; }
    .mission-image-badge-text span { color: var(--text-muted); }

    /* ─── TEAM / VALUES ──────────────────────────── */
    .team-section { padding: 80px 0; background: var(--bg-section); }
    .team-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
    }
    .team-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .team-card:hover {
        transform: translateY(-6px);
        border-color: rgba(59,130,246,0.3);
        box-shadow: 0 20px 50px rgba(0,0,0,0.4);
    }
    .team-photo {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
    }
    .team-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s;
    }
    .team-card:hover .team-photo img { transform: scale(1.06); }
    .team-info { padding: 18px 20px; }
    .team-info h4 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
    .team-info span { font-size: 0.85rem; color: var(--text-muted); }
    @media (max-width: 900px) { .team-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .team-grid { grid-template-columns: 1fr; } }

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

    .mission-features { display: flex; flex-direction: column; gap: 20px; }

    .mission-feature {
        display: flex;
        gap: 16px;
        padding: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.3s;
        width: 100%;
    }

    .mission-feature > div:last-child { min-width: 0; flex: 1; }

    .mission-feature:hover {
        border-color: rgba(59,130,246,0.3);
        transform: translateX(4px);
    }

    .mission-feature-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s;
    }

    .mission-feature:nth-child(1) .mission-feature-icon { background: linear-gradient(135deg, #22c55e, #14b8a6); box-shadow: 0 4px 15px rgba(34,197,94,0.25); }
    .mission-feature:nth-child(2) .mission-feature-icon { background: linear-gradient(135deg, #8b5cf6, #ec4899); box-shadow: 0 4px 15px rgba(139,92,246,0.25); }
    .mission-feature:nth-child(3) .mission-feature-icon { background: linear-gradient(135deg, #f59e0b, #f97316); box-shadow: 0 4px 15px rgba(245,158,11,0.25); }

    .mission-feature:hover .mission-feature-icon { transform: scale(1.1); }

    .mission-feature-icon svg { width: 22px; height: 22px; color: #fff; }

    .mission-feature h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 4px; }
    .mission-feature p { font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.5; }

    /* ─── HOW IT WORKS - TIMELINE ──────────────────────────── */
    .timeline-section { padding: 80px 0; position: relative; }

    .section-header { text-align: center; margin-bottom: 60px; }
    .section-header h2 { font-size: 2.25rem; font-weight: 800; margin-bottom: 12px; }

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

    .timeline { position: relative; max-width: 1000px; margin: 0 auto; }

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

    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-item:nth-child(odd)  { flex-direction: row; }
    .timeline-item:nth-child(even) { flex-direction: row-reverse; }

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
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(59,130,246,0.3);
        transition: transform 0.3s;
    }

    .timeline-item:hover .timeline-dot { transform: translateX(-50%) scale(1.12); }

    .timeline-item:nth-child(1) .timeline-dot { background: linear-gradient(135deg, #3b82f6, #06b6d4); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(6,182,212,0.35); }
    .timeline-item:nth-child(2) .timeline-dot { background: linear-gradient(135deg, #22c55e, #14b8a6); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(34,197,94,0.35); }
    .timeline-item:nth-child(3) .timeline-dot { background: linear-gradient(135deg, #f97316, #f59e0b); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(249,115,22,0.35); }
    .timeline-item:nth-child(4) .timeline-dot { background: linear-gradient(135deg, #8b5cf6, #ec4899); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(139,92,246,0.35); }
    .timeline-item:nth-child(5) .timeline-dot { background: linear-gradient(135deg, #ef4444, #f97316); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(239,68,68,0.35); }
    .timeline-item:nth-child(6) .timeline-dot { background: linear-gradient(135deg, #06b6d4, #3b82f6); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(6,182,212,0.35); }
    .timeline-item:nth-child(7) .timeline-dot { background: linear-gradient(135deg, #22c55e, #84cc16); box-shadow: 0 0 0 6px var(--bg-dark), 0 0 20px rgba(34,197,94,0.35); }

    .timeline-dot svg { width: 22px; height: 22px; color: #fff; }
    .timeline-spacer { flex: 1; }

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

    .timeline-item:nth-child(1) .step-tag { color: #06b6d4; background: rgba(6,182,212,0.12); }
    .timeline-item:nth-child(2) .step-tag { color: #22c55e; background: rgba(34,197,94,0.12); }
    .timeline-item:nth-child(3) .step-tag { color: #f97316; background: rgba(249,115,22,0.12); }
    .timeline-item:nth-child(4) .step-tag { color: #8b5cf6; background: rgba(139,92,246,0.12); }
    .timeline-item:nth-child(5) .step-tag { color: #ef4444; background: rgba(239,68,68,0.12); }
    .timeline-item:nth-child(6) .step-tag { color: #3b82f6; background: rgba(59,130,246,0.12); }
    .timeline-item:nth-child(7) .step-tag { color: #22c55e; background: rgba(34,197,94,0.12); }

    .timeline-content h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 14px; }

    .timeline-detail {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
    }

    .timeline-detail:last-child { margin-bottom: 0; }

    .detail-bullet {
        min-width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--primary);
        margin-top: 8px;
    }

    .timeline-detail p { color: var(--text-light); font-size: 0.9rem; line-height: 1.7; margin: 0; }
    .timeline-detail strong { color: var(--text-white); font-weight: 600; }

    /* ─── CTA SECTION ──────────────────────────── */
    .cta-section { padding: 80px 0; }

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

    .cta-box h2 { font-size: 2rem; font-weight: 800; margin-bottom: 16px; position: relative; }

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
    .fade-up.visible { opacity: 1; transform: translateY(0); }

    .fade-left {
        opacity: 0;
        transform: translateX(-30px);
        transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fade-left.visible { opacity: 1; transform: translateX(0); }

    .fade-right {
        opacity: 0;
        transform: translateX(30px);
        transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fade-right.visible { opacity: 1; transform: translateX(0); }

    /* ─── RESPONSIVE ──────────────────────────── */
    @media (max-width: 1024px) {
        .mission-grid { grid-template-columns: 1fr; gap: 40px; }
        .timeline::before { left: 24px; }
        .timeline-item,
        .timeline-item:nth-child(even) {
            flex-direction: column;
            padding-left: 72px;
            gap: 0;
        }
        .timeline-dot { left: 24px; top: 0; }
        .timeline-spacer { display: none; }
        .stats-bar { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .about-hero h1 { font-size: 2.25rem; }
        .about-hero { padding: 140px 0 50px; }
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
@endpush

@section('content')

<!-- ─── HERO ───────────────────────────────── -->
<section class="about-hero">
    <div class="about-hero-bg">
        <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1600&q=80&auto=format&fit=crop" alt="Elegant event setup" loading="eager">
    </div>
    <div class="container">
        <div class="fade-up">
            <div class="hero-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Our Story
            </div>
            <h1>Connecting Talent with<br><span class="gradient-text">Unforgettable Events</span></h1>
            <p>{{ config('app.name', 'GigResource') }} bridges the gap between clients, professionals, and influencers — making event planning seamless, reliable, and rewarding for everyone involved.</p>
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
                <div class="mission-image-wrap" style="margin-bottom: 28px;">
                    <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=900&q=80&auto=format&fit=crop" alt="Event planners collaborating" loading="lazy">
                    <div class="mission-image-badge">
                        <div class="mission-image-badge-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div class="mission-image-badge-text">
                            <strong>98% Satisfaction</strong>
                            <span>Across 1,200+ events</span>
                        </div>
                    </div>
                </div>
                <h2>Our Mission: <span class="gradient-text">Empowering the Event Industry</span></h2>
                <p>The {{ config('app.name', 'GigResource') }} platform is designed to create seamless interactions between clients, professionals, and influencers. We bring together the best talent in the industry with the clients who need them most.</p>
                <p>Whether you're planning a wedding, corporate event, birthday party, or any special occasion — we make finding and booking the right professionals effortless.</p>
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
            <h2>How <span class="gradient-text">It Works</span></h2>
            <p>A step-by-step guide to how our platform connects everyone in the event planning ecosystem.</p>
        </div>

        <div class="timeline">
            {{-- Step 1 --}}
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

            {{-- Step 2 --}}
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

            {{-- Step 3 --}}
            <div class="timeline-item fade-up" style="transition-delay: 0.15s;">
                <div class="timeline-content">
                    <span class="step-tag">Step 3</span>
                    <h3>Influencer Promotion</h3>
                    <div class="timeline-detail">
                        <div class="detail-bullet"></div>
                        <p><strong>Referral Links:</strong> Influencers receive unique referral links. They share these through social media, email campaigns, and personal networks to attract new clients and professionals.</p>
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

            {{-- Step 4 --}}
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

            {{-- Step 5 --}}
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
                        <p><strong>Support System:</strong> Throughout the planning process, our team provides support to both clients and professionals, facilitating a smooth event experience.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            {{-- Step 6 --}}
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
                        <p><strong>Continuous Improvement:</strong> We utilise feedback to continuously enhance the platform, making it more user-friendly and effective for all participants.</p>
                    </div>
                </div>
                <div class="timeline-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div class="timeline-spacer"></div>
            </div>

            {{-- Step 7 --}}
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

<!-- ─── TEAM / PEOPLE ───────────────────────────────── -->
<section class="team-section">
    <div class="container">
        <div class="section-header fade-up">
            <h2>The People <span class="gradient-text">Behind GigResource</span></h2>
            <p>A small, passionate team dedicated to making event planning effortless and rewarding.</p>
        </div>
        <div class="team-grid">
            <div class="team-card fade-up">
                <div class="team-photo">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&q=80&auto=format&fit=crop" alt="Team member" loading="lazy">
                </div>
                <div class="team-info">
                    <h4>Khadija Rahman</h4>
                    <span>Founder &amp; CEO</span>
                </div>
            </div>
            <div class="team-card fade-up" style="transition-delay:0.08s;">
                <div class="team-photo">
                    <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=500&q=80&auto=format&fit=crop" alt="Team member" loading="lazy">
                </div>
                <div class="team-info">
                    <h4>David Chen</h4>
                    <span>Head of Product</span>
                </div>
            </div>
            <div class="team-card fade-up" style="transition-delay:0.16s;">
                <div class="team-photo">
                    <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=500&q=80&auto=format&fit=crop" alt="Team member" loading="lazy">
                </div>
                <div class="team-info">
                    <h4>Priya Nair</h4>
                    <span>Community Lead</span>
                </div>
            </div>
            <div class="team-card fade-up" style="transition-delay:0.24s;">
                <div class="team-photo">
                    <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=500&q=80&auto=format&fit=crop" alt="Team member" loading="lazy">
                </div>
                <div class="team-info">
                    <h4>Marcus Bell</h4>
                    <span>Trust &amp; Safety</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── CTA SECTION ───────────────────────────────── -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box fade-up">
            <h2>Ready to Create Something <span class="gradient-text">Amazing</span>?</h2>
            <p>Join today and become part of a thriving event planning community. Whether you're a client, professional, or influencer — there's a place for you.</p>
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

@endsection

@push('scripts')
<script>
    // ── Scroll-triggered animations ──
    document.addEventListener('DOMContentLoaded', function () {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.fade-up, .fade-left, .fade-right').forEach(function (el) {
            observer.observe(el);
        });
    });
</script>
@endpush
