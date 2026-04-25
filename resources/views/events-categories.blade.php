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
        padding: 180px 0 80px;
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

    /*
     * Right column wrapper: holds the filter-tabs head row above the
     * actual sub-category grid. Splits the column vertically so the
     * tabs stay flush at the top while the grid scrolls/grows below.
     */
    .mega-subs-col {
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-width: 0;
    }

    /* Head row: "In {category}" label on the left, filter pill bar on the right */
    .mega-subs-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .mega-subs-label {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .mega-subs-label::before {
        content: '';
        width: 4px; height: 14px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--gradient-start), var(--gradient-end));
    }
    .mega-subs-label .cat-name {
        color: var(--text-white);
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Segmented pill tab bar — Popular / Top Rated / Newest / Trending */
    .mega-subs-tabs {
        display: inline-flex;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 10px;
        padding: 3px;
        gap: 2px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .mega-subs-tabs::-webkit-scrollbar { display: none; }
    .mega-subs-tab {
        padding: 7px 14px;
        background: transparent;
        border: none;
        border-radius: 7px;
        color: var(--text-muted);
        font-family: inherit;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.18s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .mega-subs-tab:hover { color: #fff; }
    .mega-subs-tab.is-active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        box-shadow: 0 4px 12px rgba(139,92,246,0.30);
    }
    .mega-subs-tab svg { width: 11px; height: 11px; }

    /* When a filter is active, non-matching tiles are removed from flow.
       `is-empty` is added by JS when nothing matches so we can render a
       small empty hint without leaving the panel looking broken. */
    .mega-sub-tile.is-hidden { display: none !important; }
    .mega-subs-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 28px 16px;
        background: rgba(255,255,255,0.02);
        border: 1px dashed rgba(255,255,255,0.10);
        border-radius: 12px;
        color: var(--text-muted);
        font-size: 0.85rem;
    }

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
        .ec-hero { padding: 140px 0 40px; }
        .ts-grid { grid-template-columns: repeat(2, 1fr); }
        .mega-subs { grid-template-columns: 1fr; }
        .event-grid { grid-template-columns: 1fr; }
        .ec-cta-box { padding: 40px 24px; }
        .ec-cta-box h2 { font-size: 1.5rem; }
        .ec-cta-actions { flex-direction: column; }
        .ec-cta-actions a { width: 100%; text-align: center; }
    }

    /* ═══════════════════════════════════════════════════════════════
       ADVANCED FILTERS — per-audience mega filter bar.
       Two distinct filter sets (clients / professionals) selectable
       via an audience toggle. Each filter category opens a dropdown
       with sub-grouped options in columns. Active selections show
       as removable chips above the mega panel.
       ═══════════════════════════════════════════════════════════════ */
    .adv-filter-section {
        padding: 36px 0 8px;
        position: relative;
    }
    .adv-filter-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .adv-filter-head-left h2 {
        font-size: 1.3rem; font-weight: 800;
        margin: 0; display: flex; align-items: center; gap: 10px;
    }
    .adv-filter-head-left h2::before {
        content: '';
        width: 4px; height: 22px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--gradient-start), var(--gradient-end));
    }
    .adv-filter-head-left p {
        margin: 6px 0 0;
        font-size: 13px; color: var(--text-muted);
        line-height: 1.5;
    }

    /* ─── AUDIENCE TOGGLE ─── */
    .adv-aud-toggle {
        display: inline-flex;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 999px;
        padding: 4px;
    }
    .adv-aud-toggle button {
        padding: 9px 22px;
        background: transparent;
        border: none;
        border-radius: 999px;
        color: var(--text-muted);
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 7px;
    }
    .adv-aud-toggle button:hover { color: #fff; }
    .adv-aud-toggle button.is-active {
        color: #fff;
        box-shadow: 0 6px 16px rgba(139,92,246,0.30);
    }
    .adv-aud-toggle button.is-active[data-audience="client"] {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    }
    .adv-aud-toggle button.is-active[data-audience="pro"] {
        background: linear-gradient(135deg, #f97316, #f59e0b);
        box-shadow: 0 6px 16px rgba(249,115,22,0.35);
    }
    .adv-aud-toggle button svg { width: 13px; height: 13px; }

    /* ─── FILTER MEGA BAR ─── */
    .adv-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .adv-filter-bar.is-hidden { display: none; }

    .adv-filter-trigger {
        position: relative;
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 16px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 12px;
        color: var(--text-light);
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .adv-filter-trigger:hover {
        background: rgba(139,92,246,0.10);
        border-color: rgba(139,92,246,0.40);
        color: #fff;
    }
    .adv-filter-trigger .icon {
        font-size: 15px; line-height: 1;
        display: inline-flex; align-items: center;
    }
    .adv-filter-trigger .chev {
        width: 13px; height: 13px;
        opacity: 0.55;
        transition: transform 0.2s;
    }
    .adv-filter-trigger[aria-expanded="true"] {
        background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(139,92,246,0.18));
        border-color: rgba(139,92,246,0.50);
        color: #fff;
    }
    .adv-filter-trigger[aria-expanded="true"] .chev { transform: rotate(180deg); opacity: 1; }
    .adv-filter-trigger .count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 20px; height: 20px;
        padding: 0 6px;
        margin-left: 2px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        font-size: 11px; font-weight: 800;
    }

    /* Pro-side trigger uses warm coral accent on hover/expand */
    .adv-filter-bar[data-audience="pro"] .adv-filter-trigger:hover,
    .adv-filter-bar[data-audience="pro"] .adv-filter-trigger[aria-expanded="true"] {
        background: linear-gradient(135deg, rgba(249,115,22,0.14), rgba(245,158,11,0.14));
        border-color: rgba(249,115,22,0.45);
    }
    .adv-filter-bar[data-audience="pro"] .adv-filter-trigger .count-badge {
        background: linear-gradient(135deg, #f97316, #f59e0b);
    }

    /* ─── DROPDOWN PANEL ─── */
    /* The panel is rendered once per filter, hidden by default and
       toggled by aria-hidden. Positioned absolutely below the bar so
       it doesn't push the layout when opening. */
    .adv-dropdowns {
        position: relative;
        margin-bottom: 18px;
    }
    .adv-dropdown {
        position: absolute;
        top: 0; left: 0; right: 0;
        background: rgba(15, 21, 41, 0.96);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 18px;
        padding: 24px 28px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.50);
        z-index: 20;
        opacity: 0;
        transform: translateY(-6px);
        pointer-events: none;
        transition: opacity 0.2s, transform 0.2s;
    }
    .adv-dropdown.is-open {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .adv-dropdown-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px 32px;
    }
    @media (max-width: 720px) {
        .adv-dropdown { padding: 20px 22px; }
        .adv-dropdown-grid { grid-template-columns: 1fr 1fr; gap: 18px 16px; }
    }
    @media (max-width: 480px) {
        .adv-dropdown-grid { grid-template-columns: 1fr; }
    }

    .adv-dropdown-col h4 {
        font-size: 0.78rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.8px;
        color: var(--text-muted);
        margin: 0 0 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed rgba(255,255,255,0.10);
    }
    .adv-option {
        display: flex; align-items: center; gap: 10px;
        padding: 7px 8px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13.5px;
        color: var(--text-light);
        transition: background 0.15s, color 0.15s;
    }
    .adv-option:hover {
        background: rgba(139,92,246,0.10);
        color: #fff;
    }
    .adv-option input {
        appearance: none;
        -webkit-appearance: none;
        width: 16px; height: 16px;
        border-radius: 4px;
        border: 1.5px solid rgba(255,255,255,0.25);
        background: transparent;
        flex-shrink: 0;
        cursor: pointer;
        position: relative;
        transition: all 0.15s;
    }
    .adv-option input:checked {
        border-color: var(--primary);
        background: var(--primary);
    }
    .adv-option input:checked::after {
        content: '✓';
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 11px; font-weight: 900;
    }
    /* Pro-side checkbox uses coral accent */
    .adv-filter-bar[data-audience="pro"] ~ .adv-dropdowns .adv-option input:checked,
    .adv-dropdown[data-audience="pro"] .adv-option input:checked {
        border-color: #f97316;
        background: #f97316;
    }

    .adv-dropdown-foot {
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; flex-wrap: wrap;
        margin-top: 22px;
        padding-top: 16px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    .adv-dropdown-foot .selected-count {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
    }
    .adv-dropdown-foot .selected-count strong { color: #fff; }
    .adv-dropdown-foot-actions { display: inline-flex; gap: 8px; }
    .adv-btn-clear, .adv-btn-apply {
        padding: 9px 18px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        border: none;
    }
    .adv-btn-clear {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--text-muted);
    }
    .adv-btn-clear:hover { color: #fff; border-color: rgba(255,255,255,0.30); }
    .adv-btn-apply {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        box-shadow: 0 6px 16px rgba(139,92,246,0.30);
    }
    .adv-btn-apply:hover { transform: translateY(-1px); }
    .adv-dropdown[data-audience="pro"] .adv-btn-apply {
        background: linear-gradient(135deg, #f97316, #f59e0b);
        box-shadow: 0 6px 16px rgba(249,115,22,0.30);
    }

    /* ─── ACTIVE FILTER CHIPS ─── */
    .adv-active-row {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: 8px;
        margin-top: 4px;
    }
    .adv-active-row.is-empty { display: none; }
    .adv-active-label {
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--text-muted);
        margin-right: 6px;
    }
    .adv-active-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 6px 5px 12px;
        background: rgba(139,92,246,0.12);
        border: 1px solid rgba(139,92,246,0.30);
        border-radius: 999px;
        font-size: 12px; font-weight: 700;
        color: #fff;
    }
    .adv-active-chip .x {
        display: flex; align-items: center; justify-content: center;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: rgba(255,255,255,0.10);
        cursor: pointer;
        font-size: 11px; line-height: 1;
    }
    .adv-active-chip .x:hover { background: rgba(255,255,255,0.22); }
    .adv-active-chip[data-audience="pro"] {
        background: rgba(249,115,22,0.12);
        border-color: rgba(249,115,22,0.32);
    }
    .adv-active-clear {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 5px 12px;
        background: transparent;
        border: 1px dashed rgba(239,68,68,0.30);
        border-radius: 999px;
        color: #fca5a5;
        font-family: inherit;
        font-size: 12px; font-weight: 700;
        cursor: pointer;
    }
    .adv-active-clear:hover { background: rgba(239,68,68,0.10); border-style: solid; }
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
                <input type="text" id="searchInput" placeholder="Search categories, services, or professionals..." oninput="filterTiles()">
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════
     ADVANCED FILTERS — sits ABOVE the main category mega-panel.
     Two distinct filter sets:
       • Client view (Popular / Budget-Friendly / Top Rated / Near Me / New)
       • Professional view (Budget / Timing / Location / Scope / Lead Quality)
     Audience toggle swaps which bar is visible. Each filter category
     opens a dropdown with sub-grouped options in columns. Selections
     accumulate as removable chips and feed into the mega-subs grid
     below via data attributes.
     ═══════════════════════════════════════════════════════════════════ -->
@php
    /*
     * Filter taxonomy. Each top-level filter has columns; each column
     * has options. The `value` is the key used for chip tracking and
     * eventual matching against mega-sub-tile data attributes.
     */
    $advFilters = [
        'client' => [
            [
                'key'   => 'popular',
                'icon'  => '🔥',
                'label' => 'Popular',
                'columns' => [
                    ['title' => 'Top Categories', 'opts' => [
                        ['v' => 'wedding-djs',         'l' => 'Wedding DJs'],
                        ['v' => 'candid-photography',  'l' => 'Candid Photography'],
                        ['v' => 'gourmet-catering',    'l' => 'Gourmet Catering'],
                        ['v' => 'boutique-florists',   'l' => 'Boutique Florists'],
                    ]],
                    ['title' => 'Currently Trending', 'opts' => [
                        ['v' => 'photo-booths',        'l' => 'Photo Booths'],
                        ['v' => 'live-acoustic',       'l' => 'Live Acoustic Duos'],
                        ['v' => 'cocktail-bars',       'l' => 'Specialty Cocktail Bars'],
                    ]],
                    ['title' => 'Most Booked', 'opts' => [
                        ['v' => 'this-month',          'l' => 'This Month'],
                        ['v' => 'this-year',           'l' => 'This Year'],
                        ['v' => 'in-your-area',        'l' => 'In Your Area'],
                    ]],
                ],
            ],
            [
                'key'   => 'budget-friendly',
                'icon'  => '💲',
                'label' => 'Budget-Friendly',
                'columns' => [
                    ['title' => 'Pricing Tiers', 'opts' => [
                        ['v' => 'tier-economy',  'l' => '$ Economy'],
                        ['v' => 'tier-standard', 'l' => '$$ Standard'],
                        ['v' => 'tier-premium',  'l' => '$$$ Premium'],
                    ]],
                    ['title' => 'Package Types', 'opts' => [
                        ['v' => 'pkg-essential',  'l' => '"Essential" Only'],
                        ['v' => 'pkg-off-peak',   'l' => 'Off-Peak Discounts'],
                        ['v' => 'pkg-bundle',     'l' => 'All-Inclusive Bundles'],
                    ]],
                    ['title' => 'Flexible Options', 'opts' => [
                        ['v' => 'flex-hourly',    'l' => 'Hourly Rates Available'],
                        ['v' => 'flex-quote',     'l' => 'Custom Quote Allowed'],
                    ]],
                ],
            ],
            [
                'key'   => 'top-rated',
                'icon'  => '⭐',
                'label' => 'Top Rated',
                'columns' => [
                    ['title' => 'Review Highlights', 'opts' => [
                        ['v' => 'rating-48',           'l' => '4.8+ Star Rating'],
                        ['v' => 'reviews-100',         'l' => '100+ Total Reviews'],
                        ['v' => 'video-testimonials',  'l' => 'Video Testimonials Included'],
                    ]],
                    ['title' => 'Award Status', 'opts' => [
                        ['v' => 'platform-choice',     'l' => '"Platform Choice" 2024'],
                        ['v' => 'verified-pro',        'l' => 'Verified Professional'],
                        ['v' => 'background-check',    'l' => 'Background Checked'],
                    ]],
                    ['title' => 'Reliability', 'opts' => [
                        ['v' => 'response-100',        'l' => '100% Response Rate'],
                        ['v' => 'zero-cancel',         'l' => 'Zero Cancellations'],
                    ]],
                ],
            ],
            [
                'key'   => 'near-me',
                'icon'  => '📍',
                'label' => 'Near Me',
                'columns' => [
                    ['title' => 'Distance', 'opts' => [
                        ['v' => 'dist-walk',           'l' => 'Walking Distance (< 2 mi)'],
                        ['v' => 'dist-city',           'l' => 'Same City (< 15 mi)'],
                        ['v' => 'dist-50',             'l' => 'Within 50 Miles'],
                    ]],
                    ['title' => 'Local Knowledge', 'opts' => [
                        ['v' => 'frequent-venues',     'l' => 'Frequent at Local Venues'],
                        ['v' => 'hometown-pro',        'l' => 'Hometown Professional'],
                    ]],
                    ['title' => 'In-Person Availability', 'opts' => [
                        ['v' => 'site-visits',         'l' => 'Available for Site Visits'],
                        ['v' => 'local-studio',        'l' => 'Local Studio / Office'],
                    ]],
                ],
            ],
            [
                'key'   => 'new-arrivals',
                'icon'  => '✨',
                'label' => 'New Arrivals',
                'columns' => [
                    ['title' => 'Joining Date', 'opts' => [
                        ['v' => 'joined-7',            'l' => 'Last 7 Days'],
                        ['v' => 'joined-30',           'l' => 'Last 30 Days'],
                    ]],
                    ['title' => 'Introductory Offers', 'opts' => [
                        ['v' => 'newbie-rates',        'l' => 'Special "Newbie" Rates'],
                        ['v' => 'trial-sessions',      'l' => 'Trial Sessions'],
                    ]],
                    ['title' => 'Experience Level', 'opts' => [
                        ['v' => 'industry-vet',        'l' => 'Industry Vet (New to Platform)'],
                        ['v' => 'emerging-talent',     'l' => 'Emerging Talent'],
                    ]],
                ],
            ],
        ],

        'pro' => [
            [
                'key'   => 'budget-value',
                'icon'  => '💰',
                'label' => 'Budget &amp; Value',
                'columns' => [
                    ['title' => 'Price Range', 'opts' => [
                        ['v' => 'price-u500',          'l' => 'Under $500 (Small Gigs)'],
                        ['v' => 'price-500-2k',        'l' => '$500 – $2,000 (Mid-Range)'],
                        ['v' => 'price-2k-5k',         'l' => '$2,000 – $5,000 (Premium)'],
                        ['v' => 'price-5k+',           'l' => '$5,000+ (Luxury)'],
                    ]],
                    ['title' => 'Payment Terms', 'opts' => [
                        ['v' => 'pay-upfront',         'l' => 'Full Payment Upfront'],
                        ['v' => 'pay-deposit',         'l' => 'Deposit Required'],
                        ['v' => 'pay-installment',     'l' => 'Installment Plans'],
                    ]],
                ],
            ],
            [
                'key'   => 'timing-availability',
                'icon'  => '📅',
                'label' => 'Timing &amp; Availability',
                'columns' => [
                    ['title' => 'Event Date', 'opts' => [
                        ['v' => 'date-specific',       'l' => 'Specific Date Picker'],
                        ['v' => 'date-weekend',        'l' => 'This Weekend'],
                        ['v' => 'date-30d',            'l' => 'Next 30 Days'],
                        ['v' => 'date-last-min',       'l' => 'Last-Minute (Next 7 days)'],
                    ]],
                    ['title' => 'Time of Day', 'opts' => [
                        ['v' => 'time-morning',        'l' => 'Morning (Before 12 PM)'],
                        ['v' => 'time-afternoon',      'l' => 'Afternoon (12 – 5 PM)'],
                        ['v' => 'time-evening',        'l' => 'Evening (After 5 PM)'],
                        ['v' => 'time-multiday',       'l' => 'Multi-Day Event'],
                    ]],
                ],
            ],
            [
                'key'   => 'location-logistics',
                'icon'  => '📍',
                'label' => 'Location &amp; Logistics',
                'columns' => [
                    ['title' => 'Travel Distance', 'opts' => [
                        ['v' => 'travel-10',           'l' => 'Within 10 miles'],
                        ['v' => 'travel-50',           'l' => '10 – 50 miles'],
                        ['v' => 'travel-state',        'l' => 'Out of State / Travel Required'],
                        ['v' => 'travel-virtual',      'l' => 'Virtual / Remote Only'],
                    ]],
                    ['title' => 'Venue Type', 'opts' => [
                        ['v' => 'venue-indoor',        'l' => 'Indoor'],
                        ['v' => 'venue-outdoor',       'l' => 'Outdoor / Open Air'],
                        ['v' => 'venue-residence',     'l' => 'Private Residence'],
                        ['v' => 'venue-commercial',    'l' => 'Commercial Venue (Hotel/Hall)'],
                    ]],
                ],
            ],
            [
                'key'   => 'event-scope',
                'icon'  => '📈',
                'label' => 'Event Scope',
                'columns' => [
                    ['title' => 'Guest Count', 'opts' => [
                        ['v' => 'guests-intimate',     'l' => 'Intimate (Under 20)'],
                        ['v' => 'guests-small',        'l' => 'Small (20 – 75)'],
                        ['v' => 'guests-medium',       'l' => 'Medium (75 – 200)'],
                        ['v' => 'guests-large',        'l' => 'Large (200+)'],
                    ]],
                    ['title' => 'Event Category', 'opts' => [
                        ['v' => 'cat-wedding',         'l' => 'Wedding'],
                        ['v' => 'cat-corporate',       'l' => 'Corporate / Networking'],
                        ['v' => 'cat-social',          'l' => 'Social (Birthday/Anniversary)'],
                        ['v' => 'cat-community',       'l' => 'Community / Non-Profit'],
                    ]],
                ],
            ],
            [
                'key'   => 'lead-quality',
                'icon'  => '⚡',
                'label' => 'Lead Quality',
                'columns' => [
                    ['title' => 'Profile Strength', 'opts' => [
                        ['v' => 'strength-verified',   'l' => 'Verified Identity Only'],
                        ['v' => 'strength-repeat',     'l' => 'Repeat Clients Only'],
                        ['v' => 'strength-venue',      'l' => 'Has Specific Venue Booked'],
                    ]],
                    ['title' => 'Response Status', 'opts' => [
                        ['v' => 'resp-no-bids',        'l' => 'No Bids Yet (Be the first!)'],
                        ['v' => 'resp-under-3',        'l' => 'Under 3 Bids'],
                        ['v' => 'resp-followup',       'l' => 'Follow-up Needed'],
                    ]],
                ],
            ],
        ],
    ];
@endphp

<section class="adv-filter-section">
    <div class="container">
        {{-- HEAD: section title + audience toggle --}}
        <div class="adv-filter-head fade-up">
            <div class="adv-filter-head-left">
                <h2>Refine Your View</h2>
                <p>Filters work the way you do — switch between Client and Professional views for tailored options.</p>
            </div>
            <div class="adv-aud-toggle" role="tablist" aria-label="Filter audience">
                <button type="button" class="is-active" data-audience="client" role="tab" aria-selected="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    I'm a Client
                </button>
                <button type="button" data-audience="pro" role="tab" aria-selected="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    I'm a Professional
                </button>
            </div>
        </div>

        {{-- FILTER BARS — one per audience, only the active one is visible --}}
        @foreach($advFilters as $audKey => $filters)
            <div class="adv-filter-bar {{ $audKey !== 'client' ? 'is-hidden' : '' }}" data-audience="{{ $audKey }}">
                @foreach($filters as $f)
                    <button type="button" class="adv-filter-trigger"
                            data-filter-key="{{ $f['key'] }}"
                            data-audience="{{ $audKey }}"
                            aria-expanded="false"
                            aria-controls="adv-dd-{{ $audKey }}-{{ $f['key'] }}">
                        <span class="icon">{{ $f['icon'] }}</span>
                        {!! $f['label'] !!}
                        <span class="count-badge" data-count-for="{{ $audKey }}-{{ $f['key'] }}" hidden>0</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                @endforeach
            </div>
        @endforeach

        {{-- DROPDOWNS — absolute-positioned popovers for each filter --}}
        <div class="adv-dropdowns">
            @foreach($advFilters as $audKey => $filters)
                @foreach($filters as $f)
                    <div class="adv-dropdown"
                         id="adv-dd-{{ $audKey }}-{{ $f['key'] }}"
                         data-audience="{{ $audKey }}"
                         data-filter-key="{{ $f['key'] }}"
                         role="region"
                         aria-hidden="true">
                        <div class="adv-dropdown-grid">
                            @foreach($f['columns'] as $col)
                                <div class="adv-dropdown-col">
                                    <h4>{{ $col['title'] }}</h4>
                                    @foreach($col['opts'] as $opt)
                                        <label class="adv-option">
                                            <input type="checkbox"
                                                   data-audience="{{ $audKey }}"
                                                   data-filter-key="{{ $f['key'] }}"
                                                   data-value="{{ $opt['v'] }}"
                                                   data-label="{{ $opt['l'] }}">
                                            <span>{!! $opt['l'] !!}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="adv-dropdown-foot">
                            <span class="selected-count"><strong>0</strong> selected</span>
                            <div class="adv-dropdown-foot-actions">
                                <button type="button" class="adv-btn-clear" data-action="clear-this">Clear</button>
                                <button type="button" class="adv-btn-apply" data-action="close-dd">Done</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- ACTIVE FILTER CHIPS --}}
        <div class="adv-active-row is-empty" id="advActiveRow">
            <span class="adv-active-label">Active:</span>
            {{-- Chips injected here by JS --}}
            <button type="button" class="adv-active-clear" id="advClearAll">Clear all</button>
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
            {{--
                Per-panel data lives in the $megaPanels array below so the
                markup stays DRY and adding a new category is a single
                array entry. Each sub-tile carries a `tags` list that the
                tabs filter (popular / top-rated / newest / trending).
            --}}
            @php
                $megaPanels = [
                    [
                        'slug' => 'weddings',
                        'shortName' => 'Weddings',
                        'title' => 'Wedding Planning &amp; Ceremony',
                        'desc'  => 'Venues, bridal attire, officiants, ceremony &amp; reception coordination — built for every budget.',
                        'count' => 120,
                        'cover' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Floral &amp; Decor',     'count' => 42, 'img' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Wedding Photography',    'count' => 75, 'img' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Reception Catering',     'count' => 55, 'img' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Wedding DJs',            'count' => 90, 'img' => 'https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Wedding Planners',       'count' => 60, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','newest']],
                        ],
                    ],
                    [
                        'slug' => 'corporate',
                        'shortName' => 'Corporate',
                        'title' => 'Corporate Events &amp; Conferences',
                        'desc'  => 'Meetings, product launches, team off-sites — with AV, staffing, and venues handled end-to-end.',
                        'count' => 80,
                        'cover' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Conference AV',          'count' => 22, 'img' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Event Planners',         'count' => 60, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Event Staff',            'count' => 30, 'img' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Awards &amp; Branding',  'count' => 22, 'img' => 'https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Videography',            'count' => 35, 'img' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Corporate Catering',     'count' => 40, 'img' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                        ],
                    ],
                    [
                        'slug' => 'baby-shower',
                        'shortName' => 'Baby Showers',
                        'title' => 'Baby Shower Specialists',
                        'desc'  => 'Decor, custom cakes, photographers, and themed planning for a celebration everyone remembers.',
                        'count' => 45,
                        'cover' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Themed Decor',           'count' => 18, 'img' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Custom Cakes',           'count' => 12, 'img' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Lifestyle Photography',  'count' => 20, 'img' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Shower Planners',        'count' => 15, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Party Favors',           'count' => 10, 'img' => 'https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                    [
                        'slug' => 'birthday',
                        'shortName' => 'Birthdays',
                        'title' => 'Birthday Party Professionals',
                        'desc'  => 'Entertainers, cakes, photo booths, and venues for kids, milestones, and adults-only nights out.',
                        'count' => 60,
                        'cover' => 'https://images.unsplash.com/photo-1464347744102-11db6282f854?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Party DJs',              'count' => 30, 'img' => 'https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Birthday Cakes',         'count' => 18, 'img' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Photo Booths',           'count' => 14, 'img' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest','trending']],
                            ['name' => 'Balloon Decor',          'count' => 22, 'img' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Entertainers',           'count' => 16, 'img' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Party Planners',         'count' => 10, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                    [
                        'slug' => 'music',
                        'shortName' => 'Music',
                        'title' => 'Music &amp; Entertainment',
                        'desc'  => 'DJs, live bands, string quartets, emcees — hire the people who make the room move.',
                        'count' => 128,
                        'cover' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'DJ Services',            'count' => 90, 'img' => 'https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Live Bands',             'count' => 38, 'img' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Solo Artists',           'count' => 24, 'img' => 'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Emcees / Hosts',         'count' => 20, 'img' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Sound &amp; AV',         'count' => 18, 'img' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'String Quartets',        'count' => 12, 'img' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                    [
                        'slug' => 'visual',
                        'shortName' => 'Photo &amp; Video',
                        'title' => 'Photo &amp; Video',
                        'desc'  => 'Editorial, cinematic, same-day edit — the people who turn your event into a keepsake.',
                        'count' => 75,
                        'cover' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Wedding Photography',    'count' => 30, 'img' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Corporate Video',        'count' => 18, 'img' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Event Photography',      'count' => 22, 'img' => 'https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Drone / Aerial',         'count' =>  9, 'img' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest','trending']],
                            ['name' => 'Photo Booths',           'count' => 14, 'img' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Lifestyle Shoots',       'count' => 11, 'img' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                    [
                        'slug' => 'food',
                        'shortName' => 'Food &amp; Catering',
                        'title' => 'Food &amp; Catering',
                        'desc'  => 'Tasting menus, casual buffets, food trucks, and bartending crews — cuisines for every crowd.',
                        'count' => 55,
                        'cover' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Full-Service Catering',  'count' => 20, 'img' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Bartending',             'count' => 12, 'img' => 'https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Food Trucks',            'count' =>  9, 'img' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest','trending']],
                            ['name' => 'Cakes &amp; Desserts',   'count' => 14, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Coffee &amp; Espresso',  'count' =>  7, 'img' => 'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Private Chefs',          'count' =>  6, 'img' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest','top-rated']],
                        ],
                    ],
                    [
                        'slug' => 'decor',
                        'shortName' => 'Decor &amp; Floral',
                        'title' => 'Decor &amp; Floral',
                        'desc'  => 'Florists, balloon artists, backdrop designers — the people who build the look of the room.',
                        'count' => 42,
                        'cover' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Florists',               'count' => 18, 'img' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Balloon Artists',        'count' => 10, 'img' => 'https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Backdrops',              'count' =>  8, 'img' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest','trending']],
                            ['name' => 'Event Lighting',         'count' => 12, 'img' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Table &amp; Chair Rentals','count' => 9, 'img' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Custom Signage',         'count' =>  5, 'img' => 'https://images.unsplash.com/photo-1567360425618-1594206637d2?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                    [
                        'slug' => 'staff',
                        'shortName' => 'Planners &amp; Staff',
                        'title' => 'Planners &amp; Staff',
                        'desc'  => 'Day-of coordinators, servers, security, registration teams — the crew that keeps it moving.',
                        'count' => 90,
                        'cover' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=900&q=80&auto=format&fit=crop',
                        'subs'  => [
                            ['name' => 'Event Planners',         'count' => 60, 'img' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated']],
                            ['name' => 'Servers &amp; Staff',    'count' => 30, 'img' => 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Event Security',         'count' => 14, 'img' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','trending']],
                            ['name' => 'Registration Desks',     'count' =>  8, 'img' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                            ['name' => 'Day-Of Coordinators',    'count' => 25, 'img' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','top-rated','trending']],
                            ['name' => 'Valet Services',         'count' =>  6, 'img' => 'https://images.unsplash.com/photo-1464347744102-11db6282f854?w=400&q=80&auto=format&fit=crop', 'tags' => ['popular','newest']],
                        ],
                    ],
                ];

                // Filter tabs definition. Order = display order in the pill bar.
                $megaTabs = [
                    ['key' => 'popular',    'label' => 'Popular'],
                    ['key' => 'top-rated',  'label' => 'Top Rated'],
                    ['key' => 'newest',     'label' => 'Newest'],
                    ['key' => 'trending',   'label' => 'Trending'],
                ];
            @endphp

            <div class="mega-panel-wrap" id="megaPanelWrap">
                @foreach($megaPanels as $i => $panel)
                    <div class="mega-panel {{ $i === 0 ? 'active' : '' }}" data-panel="{{ $panel['slug'] }}">
                        <a class="mega-hero" href="#">
                            <img src="{{ $panel['cover'] }}" alt="{{ strip_tags($panel['title']) }}">
                            <div class="mega-hero-overlay">
                                <h3>{!! $panel['title'] !!}</h3>
                                <p>{!! $panel['desc'] !!}</p>
                                <span class="mega-hero-cta">Explore {{ $panel['count'] }} professionals <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </div>
                        </a>

                        <div class="mega-subs-col">
                            {{-- Tabs head: category label + segmented filter pill bar --}}
                            <div class="mega-subs-head">
                                <h4 class="mega-subs-label">In <span class="cat-name">{!! $panel['shortName'] !!}</span></h4>
                                <div class="mega-subs-tabs" role="tablist" aria-label="Filter by">
                                    @foreach($megaTabs as $j => $tab)
                                        <button type="button"
                                                class="mega-subs-tab {{ $j === 0 ? 'is-active' : '' }}"
                                                data-filter="{{ $tab['key'] }}"
                                                role="tab"
                                                aria-selected="{{ $j === 0 ? 'true' : 'false' }}">
                                            {{ $tab['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mega-subs">
                                @foreach($panel['subs'] as $sub)
                                    <a class="mega-sub-tile" href="#" data-tags="{{ implode(' ', $sub['tags']) }}">
                                        <div class="mega-sub-thumb"><img src="{{ $sub['img'] }}" alt="{{ strip_tags($sub['name']) }}"></div>
                                        <h4>{!! $sub['name'] !!}</h4>
                                        <span class="sub-count">{{ $sub['count'] }} professionals</span>
                                    </a>
                                @endforeach
                                <div class="mega-subs-empty" hidden>No matches in this filter — try another tab.</div>
                            </div>
                        </div>
                    </div>
                @endforeach
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
                <p>Most-booked categories this month — the professionals everyone is hiring.</p>
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
                    <div class="ts-tile-meta"><span>90+ professionals</span><span class="price">from $200</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="featured" data-name="photography wedding">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=600&q=80&auto=format&fit=crop" alt="Photography"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-featured">Featured</span>
                    <h3>Photography</h3>
                    <div class="ts-tile-meta"><span>75+ professionals</span><span class="price">from $350</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="all" data-name="catering food">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=600&q=80&auto=format&fit=crop" alt="Catering"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag">Popular</span>
                    <h3>Catering</h3>
                    <div class="ts-tile-meta"><span>55+ professionals</span><span class="price">from $25/head</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="new" data-name="floral decor flowers">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=600&q=80&auto=format&fit=crop" alt="Floral"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-new">New</span>
                    <h3>Floral Design</h3>
                    <div class="ts-tile-meta"><span>42+ professionals</span><span class="price">from $180</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="all" data-name="event planner coordination">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&q=80&auto=format&fit=crop" alt="Planner"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag">Reliable</span>
                    <h3>Event Planning</h3>
                    <div class="ts-tile-meta"><span>60+ professionals</span><span class="price">from $450</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="hot" data-name="live band music entertainment">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=600&q=80&auto=format&fit=crop" alt="Live band"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-hot">Hot</span>
                    <h3>Live Bands</h3>
                    <div class="ts-tile-meta"><span>38+ professionals</span><span class="price">from $600</span></div>
                </div>
            </a>
            <a class="ts-tile fade-up" href="#" data-tag="new" data-name="awards branding gifting recognition">
                <div class="ts-tile-img"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=600&q=80&auto=format&fit=crop" alt="Awards"></div>
                <div class="ts-tile-overlay">
                    <span class="ts-tile-tag tag-new">New</span>
                    <h3>Awards &amp; Gifting</h3>
                    <div class="ts-tile-meta"><span>22+ professionals</span><span class="price">from $40</span></div>
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

    // ── Mega-subs tab filter (Popular / Top Rated / Newest / Trending) ──
    // Each panel has its own independent tab state. Filtering toggles a
    // class on the sub-tile rather than touching inline `display` so the
    // global search filter (filterTiles) can layer on top without
    // conflicting. Empty-state hint shows when no tile matches both.
    (function () {
        var panels = document.querySelectorAll('.mega-panel');
        if (!panels.length) return;

        function applyPanelFilter(panel, filterKey) {
            var tiles = panel.querySelectorAll('.mega-sub-tile');
            var visibleByFilter = 0;
            tiles.forEach(function (tile) {
                var tags = (tile.getAttribute('data-tags') || '').split(/\s+/);
                var match = tags.indexOf(filterKey) !== -1;
                tile.classList.toggle('is-hidden', !match);
                if (match) visibleByFilter++;
            });

            // Empty hint shows only when this filter would yield no tiles.
            // Search-driven hides are tracked via inline display="none" — the
            // hint stays subtle and doesn't double-trigger on overlap.
            var empty = panel.querySelector('.mega-subs-empty');
            if (empty) empty.hidden = visibleByFilter > 0;
        }

        panels.forEach(function (panel) {
            var tabs = panel.querySelectorAll('.mega-subs-tab');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var key = this.getAttribute('data-filter');
                    tabs.forEach(function (t) {
                        var active = t === tab;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    applyPanelFilter(panel, key);
                });
            });
        });
    })();

    // ── Search filter (spans mega-subs + top-services tiles) ──
    // This still uses inline `display` so it composes with the
    // .is-hidden class set by the tab filter above.
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

    // ════════════════════════════════════════════════════════════════
    // ADVANCED FILTERS — audience toggle + dropdowns + active chips
    // ════════════════════════════════════════════════════════════════
    // The whole UI is one IIFE that mounts every behaviour at once:
    //   1. Audience toggle swaps which filter bar is visible.
    //   2. Each trigger button opens its dropdown (one open at a time).
    //   3. Click outside or Escape closes it.
    //   4. Each checkbox change updates: per-trigger count badge, the
    //      "selected" footer counter, and the global active chips row.
    //   5. Chips can be removed individually (uncheck the source box)
    //      or all at once via "Clear all".
    (function () {
        var audButtons   = document.querySelectorAll('.adv-aud-toggle button[data-audience]');
        var bars         = document.querySelectorAll('.adv-filter-bar');
        var triggers     = document.querySelectorAll('.adv-filter-trigger');
        var dropdowns    = document.querySelectorAll('.adv-dropdown');
        var activeRow    = document.getElementById('advActiveRow');
        var clearAllBtn  = document.getElementById('advClearAll');
        if (!triggers.length || !activeRow) return;

        // ── 1. Audience toggle ─────────────────────────────────────
        audButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var aud = btn.getAttribute('data-audience');

                audButtons.forEach(function (b) {
                    var active = b === btn;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                // Show only the bar matching this audience
                bars.forEach(function (bar) {
                    bar.classList.toggle('is-hidden', bar.getAttribute('data-audience') !== aud);
                });

                // Close any open dropdown when switching audiences
                closeAllDropdowns();
            });
        });

        // ── 2. Trigger → dropdown open/close ───────────────────────
        function closeAllDropdowns() {
            triggers.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
            dropdowns.forEach(function (dd) {
                dd.classList.remove('is-open');
                dd.setAttribute('aria-hidden', 'true');
            });
        }

        triggers.forEach(function (trig) {
            trig.addEventListener('click', function (e) {
                e.stopPropagation();
                var key   = trig.getAttribute('data-filter-key');
                var aud   = trig.getAttribute('data-audience');
                var ddId  = 'adv-dd-' + aud + '-' + key;
                var dd    = document.getElementById(ddId);
                if (!dd) return;

                var isOpen = trig.getAttribute('aria-expanded') === 'true';
                closeAllDropdowns();
                if (!isOpen) {
                    trig.setAttribute('aria-expanded', 'true');
                    dd.classList.add('is-open');
                    dd.setAttribute('aria-hidden', 'false');
                }
            });
        });

        // Click-outside closes
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.adv-filter-section')) closeAllDropdowns();
        });
        // Escape closes
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAllDropdowns();
        });

        // ── 3. Selection state ─────────────────────────────────────
        function selectionsFor(aud, key) {
            return document.querySelectorAll(
                '.adv-option input[type="checkbox"][data-audience="' + aud + '"][data-filter-key="' + key + '"]:checked'
            );
        }

        function updateBadgeAndFooter(aud, key) {
            var checked = selectionsFor(aud, key);
            var badge   = document.querySelector('[data-count-for="' + aud + '-' + key + '"]');
            if (badge) {
                badge.hidden = checked.length === 0;
                badge.textContent = checked.length;
            }
            var dd      = document.getElementById('adv-dd-' + aud + '-' + key);
            var counter = dd && dd.querySelector('.selected-count strong');
            if (counter) counter.textContent = checked.length;
        }

        function rebuildChips() {
            // Remove all existing chips, keep the label and clear-all button
            activeRow.querySelectorAll('.adv-active-chip').forEach(function (c) { c.remove(); });

            var checked = document.querySelectorAll('.adv-option input[type="checkbox"]:checked');
            if (checked.length === 0) {
                activeRow.classList.add('is-empty');
                return;
            }
            activeRow.classList.remove('is-empty');

            checked.forEach(function (input) {
                var chip = document.createElement('span');
                chip.className = 'adv-active-chip';
                chip.setAttribute('data-audience', input.getAttribute('data-audience'));
                chip.innerHTML = ''
                    + (input.getAttribute('data-label') || input.value)
                    + ' <span class="x" role="button" aria-label="Remove">&times;</span>';
                chip.querySelector('.x').addEventListener('click', function (e) {
                    e.stopPropagation();
                    input.checked = false;
                    updateBadgeAndFooter(input.getAttribute('data-audience'), input.getAttribute('data-filter-key'));
                    rebuildChips();
                });
                // Insert before the clear-all button so it stays at the end
                activeRow.insertBefore(chip, clearAllBtn);
            });
        }

        document.querySelectorAll('.adv-option input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                updateBadgeAndFooter(cb.getAttribute('data-audience'), cb.getAttribute('data-filter-key'));
                rebuildChips();
            });
        });

        // ── 4. Per-dropdown actions ───────────────────────────────
        dropdowns.forEach(function (dd) {
            var aud = dd.getAttribute('data-audience');
            var key = dd.getAttribute('data-filter-key');

            dd.querySelector('[data-action="clear-this"]')?.addEventListener('click', function (e) {
                e.stopPropagation();
                dd.querySelectorAll('.adv-option input[type="checkbox"]:checked').forEach(function (cb) { cb.checked = false; });
                updateBadgeAndFooter(aud, key);
                rebuildChips();
            });

            dd.querySelector('[data-action="close-dd"]')?.addEventListener('click', function (e) {
                e.stopPropagation();
                closeAllDropdowns();
            });
        });

        // ── 5. Clear all ──────────────────────────────────────────
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                document.querySelectorAll('.adv-option input[type="checkbox"]:checked').forEach(function (cb) {
                    cb.checked = false;
                    updateBadgeAndFooter(cb.getAttribute('data-audience'), cb.getAttribute('data-filter-key'));
                });
                rebuildChips();
            });
        }
    })();
</script>
@endpush
