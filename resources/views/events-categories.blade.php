@extends('layouts.public')

@section('title', 'Browse Categories - ' . config('app.name', 'Khadija'))

@push('styles')
<style>
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

    .search-bar input::placeholder { color: var(--text-muted); }
    .search-bar input:focus { border-color: var(--primary); }

    .search-bar .search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    .search-bar .search-icon svg { width: 20px; height: 20px; }

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

    .ec-card-body { flex: 1; }

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
        color: #f97316;
    }

    .tag-sub {
        background: rgba(139,92,246,0.15);
        color: var(--accent);
    }

    .ec-card-body h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }

    .ec-card-body p {
        color: var(--text-muted);
        font-size: 0.85rem;
        line-height: 1.6;
        margin-bottom: 14px;
    }

    .ec-card-meta { display: flex; gap: 12px; flex-wrap: wrap; }

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

    .meta-badge svg { width: 13px; height: 13px; color: var(--primary); }

    /* ─── EVENT TYPES SECTION ──────────────────────────── */
    .event-types {
        padding: 60px 0 80px;
        background: var(--bg-section);
    }

    .event-types .section-header { text-align: center; margin-bottom: 48px; }
    .event-types .section-header h2 { font-size: 2rem; font-weight: 800; margin-bottom: 10px; }

    .event-types .section-header h2 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .event-types .section-header p { color: var(--text-muted); font-size: 1rem; }

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

    .event-tile h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 4px; }
    .event-tile span { font-size: 0.8rem; color: var(--text-light); }

    /* ─── CTA ──────────────────────────── */
    .ec-cta { padding: 80px 0; }

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

    /* ─── RESPONSIVE ──────────────────────────── */
    @media (max-width: 1024px) {
        .event-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
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
@endpush

@section('content')

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

            {{-- Main Category: Baby Shower --}}
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

            {{-- Main Category: Wedding --}}
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

            {{-- Main Category: Corporate Event --}}
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

            {{-- Sub Category: DJ Services --}}
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

            {{-- Sub Category: Photography --}}
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

            {{-- Sub Category: Catering --}}
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

            {{-- Sub Category: Makeup --}}
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

            {{-- Sub Category: Decor & Floral --}}
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

            {{-- Sub Category: Event Planning --}}
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

            {{-- Sub Category: Live Bands --}}
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

            {{-- Sub Category: Awards & Recognition --}}
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

            {{-- Sub Category: Event Staff --}}
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

@endsection

@push('scripts')
<script>
    // ── Scroll animations ──
    document.addEventListener('DOMContentLoaded', function () {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.fade-up').forEach(function (el) {
            observer.observe(el);
        });
    });

    // ── Filter by type ──
    function setFilter(type, btn) {
        document.querySelectorAll('.filter-tab').forEach(function (t) { t.classList.remove('active'); });
        btn.classList.add('active');

        document.querySelectorAll('.ec-card').forEach(function (card) {
            var cardType = card.getAttribute('data-type');
            card.style.display = (type === 'all' || cardType === type) ? '' : 'none';
        });
    }

    // ── Search filter ──
    function filterCards() {
        var query = document.getElementById('searchInput').value.toLowerCase();

        document.querySelectorAll('.filter-tab').forEach(function (t) { t.classList.remove('active'); });
        document.querySelector('.filter-tab').classList.add('active');

        document.querySelectorAll('.ec-card').forEach(function (card) {
            var name  = card.getAttribute('data-name');
            var title = card.querySelector('h3').textContent.toLowerCase();
            var desc  = card.querySelector('p').textContent.toLowerCase();
            card.style.display = (name.includes(query) || title.includes(query) || desc.includes(query)) ? '' : 'none';
        });
    }
</script>
@endpush
