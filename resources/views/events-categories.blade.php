@extends('layouts.public')

@section('title', 'Browse Categories - ' . config('app.name', 'Khadija'))

@push('styles')
{{--
    Alibaba-style category browser.

    Structure:
      1. Hero + search  (unchanged)
      2. Mega panel     — left rail of main categories, right-hand
                          "showcase" that swaps content based on the
                          hovered/selected main category (like Alibaba's
                          left-hand category list that drives the right
                          mega-menu).
      3. Top Services   — dense 4-col image tile grid of popular sub-
                          categories, big and colourful.
      4. Event types    — carried over from the old page, visually
                          unchanged.
      5. CTA            — unchanged.

    Data: still inline / hardcoded here; when a Category model lands,
    this becomes a foreach over `$mainCategories` + `$mainCategory->subs`.
--}}
<style>
    /* ─── HERO ──────────────────────────── */
    .ec-hero {
        position: relative;
        padding: 150px 0 80px;
        text-align: center;
        overflow: hidden;
    }
    .ec-hero-bg { position: absolute; inset: 0; z-index: 0; }
    .ec-hero-bg img { width: 100%; height: 100%; object-fit: cover; opacity: 0.28; }
    .ec-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(11,15,26,0.55) 0%, rgba(11,15,26,0.9) 80%, var(--bg-dark) 100%);
    }
    .ec-hero .container { position: relative; z-index: 1; }
    .ec-hero::before {
        content: '';
        position: absolute;
        top: -40%; left: 50%;
        transform: translateX(-50%);
        width: 700px; height: 700px;
        background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, rgba(139,92,246,0.08) 40%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
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
    .search-bar { max-width: 640px; margin: 0 auto; position: relative; }
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
        position: absolute; left: 18px; top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }
    .search-bar .search-icon svg { width: 20px; height: 20px; }

    /* ─── MEGA PANEL ──────────────────────────── */
    /*
     * Two-column layout:
     *   .mega-rail   (left)  – sticky list of main categories
     *   .mega-panel  (right) – big featured image + sub-category grid,
     *                          swaps content when a rail item activates.
     *
     * We use CSS `display: grid` on .mega-layout, with the rail items
     * controlled by an `active` class (JS-toggled on hover/click). The
     * right panels all live inline inside .mega-panel and show/hide via
     * a matching `[data-panel="slug"]` selector.
     */
    .mega-section {
        padding: 10px 0 40px;
    }
    .mega-section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-white);
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mega-section-title::before {
        content: '';
        width: 4px; height: 22px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--gradient-start), var(--gradient-end));
    }

    .mega-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
    }

    .mega-rail {
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--border-color);
        background: rgba(255,255,255,0.015);
    }
    .mega-rail-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--text-light);
        font-family: inherit;
        font-size: 0.92rem;
        font-weight: 500;
        text-align: left;
        border-left: 3px solid transparent;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
        position: relative;
    }
    .mega-rail-item:hover {
        background: rgba(59,130,246,0.06);
        color: var(--text-white);
    }
    .mega-rail-item.active {
        background: linear-gradient(90deg, rgba(59,130,246,0.15), transparent 80%);
        color: var(--text-white);
        border-left-color: var(--primary);
    }
    .mega-rail-item .rail-icon {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: rgba(59,130,246,0.15);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .mega-rail-item .rail-icon svg { width: 18px; height: 18px; color: var(--primary); }
    .mega-rail-item .rail-count {
        margin-left: auto;
        font-size: 0.7rem;
        color: var(--text-muted);
        background: rgba(255,255,255,0.05);
        padding: 2px 8px;
        border-radius: 999px;
    }
    .mega-rail-item.active .rail-count {
        background: rgba(59,130,246,0.2);
        color: var(--primary);
    }

    /* Right-side panel — one .mega-panel per rail item, only the
       matching one is visible at a time. */
    .mega-panel-wrap { padding: 20px; min-width: 0; }
    .mega-panel {
        display: none;
        grid-template-columns: 280px 1fr;
        gap: 20px;
    }
    .mega-panel.active { display: grid; }

    .mega-hero {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #111827;
        min-height: 280px;
    }
    .mega-hero img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    .mega-hero:hover img { transform: scale(1.05); }
    .mega-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 30%, rgba(0,0,0,0.85));
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 20px;
    }
    .mega-hero-overlay h3 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 6px;
        color: #fff;
    }
    .mega-hero-overlay p {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.85);
        margin: 0 0 12px;
        line-height: 1.5;
    }
    .mega-hero-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        padding: 8px 14px;
        border-radius: 8px;
        align-self: flex-start;
        text-decoration: none;
    }
    .mega-hero-cta svg { width: 14px; height: 14px; }

    .mega-subs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        align-content: start;
    }
    .mega-sub-tile {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 12px;
        border-radius: 10px;
        background: rgba(255,255,255,0.025);
        border: 1px solid var(--border-color);
        text-decoration: none;
        color: var(--text-light);
        transition: transform 0.2s, border-color 0.2s, background 0.2s;
        min-height: 124px;
    }
    .mega-sub-tile:hover {
        transform: translateY(-3px);
        border-color: rgba(59,130,246,0.4);
        background: rgba(59,130,246,0.07);
    }
    .mega-sub-thumb {
        width: 100%;
        aspect-ratio: 16/10;
        border-radius: 6px;
        overflow: hidden;
        background: #1f2937;
    }
    .mega-sub-thumb img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .mega-sub-tile h4 {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-white);
        margin: 0;
        line-height: 1.3;
    }
    .mega-sub-tile .sub-count {
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    /* ─── TOP SERVICES TILE GRID ──────────────────────────── */
    /*
     * Alibaba-style "Top Categories / More to love" block: big square
     * image tiles in a 4-wide grid, with overlay copy. Each tile is
     * hero-sized and links to the filtered listing.
     */
    .top-services {
        padding: 60px 0;
    }
    .ts-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 24px;
        gap: 20px;
        flex-wrap: wrap;
    }
    .ts-header h2 {
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-white);
    }
    .ts-header h2 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .ts-header p {
        color: var(--text-muted);
        margin: 4px 0 0;
        font-size: 0.95rem;
    }
    .ts-filter {
        display: inline-flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .ts-filter-btn {
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }
    .ts-filter-btn:hover { color: var(--text-light); border-color: rgba(59,130,246,0.3); }
    .ts-filter-btn.active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-color: transparent;
    }

    .ts-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    .ts-tile {
        position: relative;
        aspect-ratio: 1 / 1;
        border-radius: 14px;
        overflow: hidden;
        background: var(--bg-card);
        text-decoration: none;
        color: inherit;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .ts-tile:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 40px rgba(0,0,0,0.35);
    }
    .ts-tile-img {
        position: absolute; inset: 0;
        overflow: hidden;
    }
    .ts-tile-img img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    .ts-tile:hover .ts-tile-img img { transform: scale(1.08); }
    .ts-tile-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0) 20%, rgba(0,0,0,0.9));
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }
    .ts-tile-tag {
        align-self: flex-start;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 6px;
        background: rgba(255,255,255,0.2);
        color: #fff;
        backdrop-filter: blur(6px);
        margin-bottom: auto;
    }
    .ts-tile-tag.tag-featured { background: #f59e0b; }
    .ts-tile-tag.tag-new      { background: #22c55e; }
    .ts-tile-tag.tag-hot      { background: #ef4444; }
    .ts-tile h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff;
        margin: 0 0 4px;
    }
    .ts-tile-meta {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ts-tile-meta .price {
        color: var(--primary);
        font-weight: 700;
    }

    /* ─── EVENT TYPES SECTION ──────────────────────────── */
    .event-types { padding: 60px 0 80px; background: var(--bg-section); }
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
    .event-tile-bg { position: absolute; inset: 0; overflow: hidden; }
    .event-tile-bg img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .event-tile:hover .event-tile-bg img { transform: scale(1.08); }
    .event-tile-overlay {
        position: absolute; inset: 0;
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
        top: -80px; right: -80px;
        width: 250px; height: 250px;
        background: radial-gradient(circle, rgba(139,92,246,0.1), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .ec-cta-box h2 { font-size: 2rem; font-weight: 800; margin-bottom: 12px; position: relative; }
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
    .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(59,130,246,0.4); }
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
    .btn-ghost:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.08); }

    /* ─── ANIMATIONS ──────────────────────────── */
    .fade-up {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fade-up.visible { opacity: 1; transform: translateY(0); }

    /* ─── RESPONSIVE ──────────────────────────── */
    @media (max-width: 1100px) {
        .ts-grid { grid-template-columns: repeat(3, 1fr); }
        .mega-panel { grid-template-columns: 1fr; }
        .mega-hero { min-height: 200px; max-height: 240px; }
        .mega-subs { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
        .mega-layout { grid-template-columns: 1fr; }
        .mega-rail { flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid var(--border-color); }
        .mega-rail-item { flex-shrink: 0; border-left: none; border-bottom: 3px solid transparent; }
        .mega-rail-item.active { border-left-color: transparent; border-bottom-color: var(--primary); background: rgba(59,130,246,0.08); }
        .mega-rail-item .rail-count { display: none; }
    }
    @media (max-width: 768px) {
        .ec-hero h1 { font-size: 2rem; }
        .ec-hero { padding: 110px 0 40px; }
        .ts-grid { grid-template-columns: repeat(2, 1fr); }
        .mega-subs { grid-template-columns: 1fr; }
        .event-grid { grid-template-columns: 1fr; }
        .ec-cta-box { padding: 40px 24px; }
        .ec-cta-box h2 { font-size: 1.5rem; }
        .ec-cta-actions { flex-direction: column; }
        .ec-cta-actions a { width: 100%; text-align: center; }
    }
</style>
@endpush

@section('content')

<!-- ─── HERO ───────────────────────────────── -->
<section class="ec-hero">
    <div class="ec-hero-bg">
        <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600&q=80&auto=format&fit=crop" alt="Event celebration confetti" loading="eager">
    </div>
    <div class="container">
        <div class="fade-up">
            <h1>Browse <span class="gradient-text">Categories</span></h1>
            <p>Explore every type of event and every kind of professional in one place — the way a real marketplace should feel.</p>

            <div class="search-bar">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search categories, services, or pros..." oninput="filterTiles()">
            </div>
        </div>
    </div>
</section>

<!-- ─── MEGA PANEL (Alibaba-style left rail + right showcase) ──────────────────────── -->
<section class="mega-section">
    <div class="container">
        <h2 class="mega-section-title fade-up">Shop by Category</h2>

        {{--
            Each .mega-rail-item carries a `data-target` matching a
            .mega-panel[data-panel]. JS swaps the `active` class on hover
            (desktop) and click (touch). First item is active by default.
        --}}
        <div class="mega-layout fade-up">

            <!-- LEFT RAIL -->
            <div class="mega-rail" id="megaRail">
                <button type="button" class="mega-rail-item active" data-target="weddings">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-7-10a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 5.65-7 10-7 10z"/></svg></span>
                    <span>Weddings &amp; Ceremonies</span>
                    <span class="rail-count">120</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="corporate">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                    <span>Corporate &amp; Conferences</span>
                    <span class="rail-count">80</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="baby-shower">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 10h.01M15 10h.01M9.5 15a3 3 0 0 0 5 0"/></svg></span>
                    <span>Baby Showers</span>
                    <span class="rail-count">45</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="birthday">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21V10a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v11"/><path d="M4 15h16"/><path d="M12 4v4"/><path d="M12 2a2 2 0 1 1-2 2 2 2 0 0 1 2-2z"/></svg></span>
                    <span>Birthday Parties</span>
                    <span class="rail-count">60</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="music">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
                    <span>Music &amp; Entertainment</span>
                    <span class="rail-count">128</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="visual">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19V6a2 2 0 0 0-2-2h-4l-2-2h-6l-2 2H3a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                    <span>Photo &amp; Video</span>
                    <span class="rail-count">75</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="food">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></span>
                    <span>Food &amp; Catering</span>
                    <span class="rail-count">55</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="decor">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.27 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                    <span>Decor &amp; Floral</span>
                    <span class="rail-count">42</span>
                </button>
                <button type="button" class="mega-rail-item" data-target="staff">
                    <span class="rail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Planners &amp; Staff</span>
                    <span class="rail-count">90</span>
                </button>
            </div>

            <!-- RIGHT SHOWCASE -->
            <div class="mega-panel-wrap" id="megaPanelWrap">

                {{-- Weddings --}}
                <div class="mega-panel active" data-panel="weddings">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80&auto=format&fit=crop" alt="Wedding ceremony">
                        <div class="mega-hero-overlay">
                            <h3>Wedding Planning &amp; Ceremony</h3>
                            <p>Venues, bridal attire, officiants, ceremony &amp; reception coordination — built for every budget.</p>
                            <span class="mega-hero-cta">Explore 120 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop" alt="Floral"></div><h4>Floral &amp; Decor</h4><span class="sub-count">42 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop" alt="Photographer"></div><h4>Wedding Photography</h4><span class="sub-count">75 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=400&q=80&auto=format&fit=crop" alt="Makeup"></div><h4>Bridal Makeup</h4><span class="sub-count">48 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop" alt="Catering"></div><h4>Reception Catering</h4><span class="sub-count">55 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop" alt="DJ"></div><h4>Wedding DJs</h4><span class="sub-count">90 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Planner"></div><h4>Wedding Planners</h4><span class="sub-count">60 pros</span></a>
                    </div>
                </div>

                {{-- Corporate --}}
                <div class="mega-panel" data-panel="corporate">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=900&q=80&auto=format&fit=crop" alt="Corporate conference">
                        <div class="mega-hero-overlay">
                            <h3>Corporate Events &amp; Conferences</h3>
                            <p>Meetings, product launches, team off-sites — with AV, staffing, and venues handled end-to-end.</p>
                            <span class="mega-hero-cta">Explore 80 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop" alt="Conference"></div><h4>Conference AV</h4><span class="sub-count">22 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Planner"></div><h4>Event Planners</h4><span class="sub-count">60 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop" alt="Staff"></div><h4>Event Staff</h4><span class="sub-count">30 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop" alt="Awards"></div><h4>Awards &amp; Branding</h4><span class="sub-count">22 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop" alt="Videographer"></div><h4>Videography</h4><span class="sub-count">35 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop" alt="Catering"></div><h4>Corporate Catering</h4><span class="sub-count">40 pros</span></a>
                    </div>
                </div>

                {{-- Baby Shower --}}
                <div class="mega-panel" data-panel="baby-shower">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=900&q=80&auto=format&fit=crop" alt="Baby shower">
                        <div class="mega-hero-overlay">
                            <h3>Baby Shower Specialists</h3>
                            <p>Decor, custom cakes, photographers, and themed planning for a celebration everyone remembers.</p>
                            <span class="mega-hero-cta">Explore 45 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop" alt="Decor"></div><h4>Themed Decor</h4><span class="sub-count">18 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop" alt="Cake"></div><h4>Custom Cakes</h4><span class="sub-count">12 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop" alt="Photo"></div><h4>Lifestyle Photography</h4><span class="sub-count">20 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Planner"></div><h4>Shower Planners</h4><span class="sub-count">15 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop" alt="Favors"></div><h4>Party Favors</h4><span class="sub-count">10 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=400&q=80&auto=format&fit=crop" alt="Makeup"></div><h4>Mama Makeup</h4><span class="sub-count">8 pros</span></a>
                    </div>
                </div>

                {{-- Birthday --}}
                <div class="mega-panel" data-panel="birthday">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=900&q=80&auto=format&fit=crop" alt="Birthday party">
                        <div class="mega-hero-overlay">
                            <h3>Birthday Party Pros</h3>
                            <p>Entertainers, cakes, photo booths, and venues for kids, milestones, and adults-only nights out.</p>
                            <span class="mega-hero-cta">Explore 60 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop" alt="DJ"></div><h4>Party DJs</h4><span class="sub-count">30 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop" alt="Cake"></div><h4>Birthday Cakes</h4><span class="sub-count">18 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop" alt="Photo"></div><h4>Photo Booths</h4><span class="sub-count">14 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop" alt="Decor"></div><h4>Balloon Decor</h4><span class="sub-count">22 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop" alt="Entertainers"></div><h4>Entertainers</h4><span class="sub-count">16 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Planner"></div><h4>Party Planners</h4><span class="sub-count">10 pros</span></a>
                    </div>
                </div>

                {{-- Music --}}
                <div class="mega-panel" data-panel="music">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=900&q=80&auto=format&fit=crop" alt="Live band">
                        <div class="mega-hero-overlay">
                            <h3>Music &amp; Entertainment</h3>
                            <p>DJs, live bands, string quartets, emcees — hire the people who make the room move.</p>
                            <span class="mega-hero-cta">Explore 128 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop" alt="DJ"></div><h4>DJ Services</h4><span class="sub-count">90 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=400&q=80&auto=format&fit=crop" alt="Band"></div><h4>Live Bands</h4><span class="sub-count">38 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=400&q=80&auto=format&fit=crop" alt="Solo"></div><h4>Solo Artists</h4><span class="sub-count">24 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop" alt="MC"></div><h4>Emcees / Hosts</h4><span class="sub-count">20 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop" alt="AV"></div><h4>Sound &amp; AV</h4><span class="sub-count">18 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop" alt="Classical"></div><h4>String Quartets</h4><span class="sub-count">12 pros</span></a>
                    </div>
                </div>

                {{-- Photo & Video --}}
                <div class="mega-panel" data-panel="visual">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=900&q=80&auto=format&fit=crop" alt="Photography">
                        <div class="mega-hero-overlay">
                            <h3>Photo &amp; Video</h3>
                            <p>Editorial, cinematic, same-day edit — the people who turn your event into a keepsake.</p>
                            <span class="mega-hero-cta">Explore 75 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop" alt="Wedding photo"></div><h4>Wedding Photography</h4><span class="sub-count">30 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop" alt="Corporate video"></div><h4>Corporate Video</h4><span class="sub-count">18 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop" alt="Event photo"></div><h4>Event Photography</h4><span class="sub-count">22 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=400&q=80&auto=format&fit=crop" alt="Drone"></div><h4>Drone / Aerial</h4><span class="sub-count">9 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop" alt="Booth"></div><h4>Photo Booths</h4><span class="sub-count">14 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80&auto=format&fit=crop" alt="Lifestyle"></div><h4>Lifestyle Shoots</h4><span class="sub-count">11 pros</span></a>
                    </div>
                </div>

                {{-- Food --}}
                <div class="mega-panel" data-panel="food">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=900&q=80&auto=format&fit=crop" alt="Catering spread">
                        <div class="mega-hero-overlay">
                            <h3>Food &amp; Catering</h3>
                            <p>Tasting menus, casual buffets, food trucks, and bartending crews — cuisines for every crowd.</p>
                            <span class="mega-hero-cta">Explore 55 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop" alt="Full service"></div><h4>Full-Service Catering</h4><span class="sub-count">20 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop" alt="Bar"></div><h4>Bartending</h4><span class="sub-count">12 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop" alt="Truck"></div><h4>Food Trucks</h4><span class="sub-count">9 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Cake"></div><h4>Cakes &amp; Desserts</h4><span class="sub-count">14 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=400&q=80&auto=format&fit=crop" alt="Coffee"></div><h4>Coffee &amp; Espresso</h4><span class="sub-count">7 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop" alt="Private chef"></div><h4>Private Chefs</h4><span class="sub-count">6 pros</span></a>
                    </div>
                </div>

                {{-- Decor --}}
                <div class="mega-panel" data-panel="decor">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=900&q=80&auto=format&fit=crop" alt="Floral decor">
                        <div class="mega-hero-overlay">
                            <h3>Decor &amp; Floral</h3>
                            <p>Florists, balloon artists, backdrop designers — the people who build the look of the room.</p>
                            <span class="mega-hero-cta">Explore 42 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop" alt="Florist"></div><h4>Florists</h4><span class="sub-count">18 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop" alt="Balloon"></div><h4>Balloon Artists</h4><span class="sub-count">10 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop" alt="Backdrop"></div><h4>Backdrops</h4><span class="sub-count">8 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop" alt="Lighting"></div><h4>Event Lighting</h4><span class="sub-count">12 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80&auto=format&fit=crop" alt="Rentals"></div><h4>Table &amp; Chair Rentals</h4><span class="sub-count">9 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop" alt="Signage"></div><h4>Custom Signage</h4><span class="sub-count">5 pros</span></a>
                    </div>
                </div>

                {{-- Staff --}}
                <div class="mega-panel" data-panel="staff">
                    <a class="mega-hero" href="#">
                        <img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=900&q=80&auto=format&fit=crop" alt="Event staff">
                        <div class="mega-hero-overlay">
                            <h3>Planners &amp; Staff</h3>
                            <p>Day-of coordinators, servers, security, registration teams — the crew that keeps it moving.</p>
                            <span class="mega-hero-cta">Explore 90 pros <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </a>
                    <div class="mega-subs">
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop" alt="Planner"></div><h4>Event Planners</h4><span class="sub-count">60 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop" alt="Staff"></div><h4>Servers &amp; Staff</h4><span class="sub-count">30 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop" alt="Security"></div><h4>Event Security</h4><span class="sub-count">14 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop" alt="Registration"></div><h4>Registration Desks</h4><span class="sub-count">8 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop" alt="Coordinator"></div><h4>Day-Of Coordinators</h4><span class="sub-count">25 pros</span></a>
                        <a class="mega-sub-tile" href="#"><div class="mega-sub-thumb"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop" alt="Valet"></div><h4>Valet Services</h4><span class="sub-count">6 pros</span></a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ─── TOP SERVICES TILE GRID (Alibaba "More to love" style) ──────────── -->
<section class="top-services">
    <div class="container">
        <div class="ts-header fade-up">
            <div>
                <h2>Top <span class="gradient-text">Services</span></h2>
                <p>Most-booked categories this month — the pros everyone is hiring.</p>
            </div>
            <div class="ts-filter" id="tsFilter">
                <button type="button" class="ts-filter-btn active" data-filter="all">All</button>
                <button type="button" class="ts-filter-btn" data-filter="featured">Featured</button>
                <button type="button" class="ts-filter-btn" data-filter="new">New</button>
                <button type="button" class="ts-filter-btn" data-filter="hot">Hot</button>
            </div>
        </div>

        <div class="ts-grid" id="tsGrid">
            <a class="ts-tile fade-up" href="#" data-tag="hot" data-name="dj services music">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=600&q=80&auto=format&fit=crop" alt="DJ"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-hot">Hot</span>
                    <h3>DJ Services</h3>
                    <div class="ts-tile-meta"><span>90+ pros</span><span class="price">from $200</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="featured" data-name="photography wedding">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=600&q=80&auto=format&fit=crop" alt="Photography"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-featured">Featured</span>
                    <h3>Photography</h3>
                    <div class="ts-tile-meta"><span>75+ pros</span><span class="price">from $350</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="all" data-name="catering food">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=600&q=80&auto=format&fit=crop" alt="Catering"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag">Popular</span>
                    <h3>Catering</h3>
                    <div class="ts-tile-meta"><span>55+ pros</span><span class="price">from $25/head</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="featured" data-name="makeup beauty bridal">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=600&q=80&auto=format&fit=crop" alt="Makeup"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-featured">Featured</span>
                    <h3>Makeup &amp; Beauty</h3>
                    <div class="ts-tile-meta"><span>48+ pros</span><span class="price">from $120</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="new" data-name="floral decor flowers">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=600&q=80&auto=format&fit=crop" alt="Floral"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-new">New</span>
                    <h3>Floral Design</h3>
                    <div class="ts-tile-meta"><span>42+ pros</span><span class="price">from $180</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="all" data-name="event planner coordination">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&q=80&auto=format&fit=crop" alt="Planner"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag">Reliable</span>
                    <h3>Event Planning</h3>
                    <div class="ts-tile-meta"><span>60+ pros</span><span class="price">from $450</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="hot" data-name="live band music entertainment">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=600&q=80&auto=format&fit=crop" alt="Live band"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-hot">Hot</span>
                    <h3>Live Bands</h3>
                    <div class="ts-tile-meta"><span>38+ pros</span><span class="price">from $600</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="new" data-name="awards branding gifting recognition">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=600&q=80&auto=format&fit=crop" alt="Awards"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-new">New</span>
                    <h3>Awards &amp; Gifting</h3>
                    <div class="ts-tile-meta"><span>22+ pros</span><span class="price">from $40</span></div>
                </div>
            </a>
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
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=800&q=80&auto=format&fit=crop" alt="Birthday party" loading="lazy">
                </div>
                <div class="event-tile-overlay">
                    <h3>Birthday Parties</h3>
                    <span>60+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.05s;">
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=800&q=80&auto=format&fit=crop" alt="Music concert" loading="lazy">
                </div>
                <div class="event-tile-overlay">
                    <h3>Music Concerts</h3>
                    <span>90+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.1s;">
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80&auto=format&fit=crop" alt="Wedding" loading="lazy">
                </div>
                <div class="event-tile-overlay">
                    <h3>Weddings</h3>
                    <span>120+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.15s;">
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80&auto=format&fit=crop" alt="Corporate event" loading="lazy">
                </div>
                <div class="event-tile-overlay">
                    <h3>Corporate Events</h3>
                    <span>80+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.2s;">
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800&q=80&auto=format&fit=crop" alt="Graduation" loading="lazy">
                </div>
                <div class="event-tile-overlay">
                    <h3>Graduation Ceremonies</h3>
                    <span>35+ professionals available</span>
                </div>
            </div>
            <div class="event-tile fade-up" style="transition-delay:0.25s;">
                <div class="event-tile-bg">
                    <img src="https://images.unsplash.com/photo-1482517967863-00e15c9b44be?w=800&q=80&auto=format&fit=crop" alt="Holiday celebration" loading="lazy">
                </div>
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
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.fade-up').forEach(function (el) { observer.observe(el); });
    });

    // ── Mega-panel switching ──
    // Hover drives it on desktop (like Alibaba); click also works for touch.
    (function () {
        var rail = document.getElementById('megaRail');
        var wrap = document.getElementById('megaPanelWrap');
        if (!rail || !wrap) return;

        function activate(target) {
            rail.querySelectorAll('.mega-rail-item').forEach(function (i) {
                i.classList.toggle('active', i.getAttribute('data-target') === target);
            });
            wrap.querySelectorAll('.mega-panel').forEach(function (p) {
                p.classList.toggle('active', p.getAttribute('data-panel') === target);
            });
        }

        rail.querySelectorAll('.mega-rail-item').forEach(function (item) {
            item.addEventListener('mouseenter', function () { activate(this.getAttribute('data-target')); });
            item.addEventListener('click',      function () { activate(this.getAttribute('data-target')); });
            item.addEventListener('focus',      function () { activate(this.getAttribute('data-target')); });
        });
    })();

    // ── Top services filter pills ──
    (function () {
        var filterRoot = document.getElementById('tsFilter');
        var grid       = document.getElementById('tsGrid');
        if (!filterRoot || !grid) return;

        filterRoot.querySelectorAll('.ts-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                filterRoot.querySelectorAll('.ts-filter-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                var filter = this.getAttribute('data-filter');
                grid.querySelectorAll('.ts-tile').forEach(function (tile) {
                    var tag = tile.getAttribute('data-tag');
                    tile.style.display = (filter === 'all' || tag === filter) ? '' : 'none';
                });
            });
        });
    })();

    // ── Search filter (spans mega-subs + top-services tiles) ──
    function filterTiles() {
        var input = document.getElementById('searchInput');
        if (!input) return;
        var q = input.value.toLowerCase().trim();

        // Top-services tiles: match against data-name + heading.
        document.querySelectorAll('#tsGrid .ts-tile').forEach(function (tile) {
            var name = (tile.getAttribute('data-name') || '').toLowerCase();
            var h3   = tile.querySelector('h3').textContent.toLowerCase();
            tile.style.display = (!q || name.includes(q) || h3.includes(q)) ? '' : 'none';
        });

        // Mega-sub tiles: match against heading.
        document.querySelectorAll('.mega-sub-tile').forEach(function (tile) {
            var h4 = tile.querySelector('h4').textContent.toLowerCase();
            tile.style.display = (!q || h4.includes(q)) ? '' : 'none';
        });
    }
</script>
@endpush
