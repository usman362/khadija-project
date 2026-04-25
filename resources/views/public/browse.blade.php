@extends('layouts.public')

@section('title', 'Browse Professionals | ' . config('app.name'))

@push('styles')
<style>
    /* ─────────────────────────────────────────────────────────────
       BROWSE PROFESSIONALS — advanced marketplace UI.
       Layered hero banner · category chip rail · active-filter pills ·
       grid/list view toggle · richer cards · collapsible sidebar.
       ───────────────────────────────────────────────────────────── */

    /* ─── HERO BANNER ─── */
    .browse-hero {
        position: relative;
        padding: 180px 0 70px;
        overflow: hidden;
    }
    /* Photographic cover image behind the hero. Dimmed + gradient
       overlaid so the search bar and headline remain perfectly legible
       against any image. */
    .browse-hero-bg {
        position: absolute; inset: 0; z-index: 0;
    }
    .browse-hero-bg img {
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0.30;
    }
    .browse-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(900px 420px at 18% 10%, rgba(59,130,246,0.24), transparent 55%),
            radial-gradient(800px 400px at 85% 0%, rgba(139,92,246,0.24), transparent 55%),
            radial-gradient(600px 300px at 50% 100%, rgba(249,115,22,0.12), transparent 60%),
            linear-gradient(180deg, rgba(11,15,26,0.55) 0%, rgba(11,15,26,0.90) 80%, var(--bg-dark) 100%);
    }
    .browse-hero .container { position: relative; z-index: 1; }
    .browse-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 16px; border-radius: 999px;
        background: rgba(139,92,246,0.14);
        border: 1px solid rgba(139,92,246,0.32);
        font-size: 11px; font-weight: 800; letter-spacing: 1.2px;
        text-transform: uppercase; color: #c4b5fd;
        margin-bottom: 18px;
    }
    .browse-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 0 8px rgba(139,92,246,0.6);
    }
    .browse-hero h1 {
        font-size: 3rem; font-weight: 900;
        letter-spacing: -0.02em; line-height: 1.1;
        margin-bottom: 14px;
    }
    .browse-hero h1 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .browse-hero p.lede {
        color: var(--text-muted);
        font-size: 1.05rem;
        max-width: 640px;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    @media (max-width: 700px) {
        .browse-hero { padding: 140px 0 50px; }
        .browse-hero h1 { font-size: 2rem; }
        .browse-hero p.lede { font-size: 0.95rem; }
    }

    /* ─── BIG SEARCH BAR (in hero) ─── */
    .browse-mega-search {
        max-width: 720px;
        background: rgba(8, 12, 22, 0.7);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 18px;
        padding: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 30px 70px rgba(0,0,0,0.35);
    }
    .browse-mega-search .search-field {
        flex: 1;
        position: relative;
        display: flex; align-items: center;
        padding: 0 14px;
    }
    .browse-mega-search .search-field svg {
        width: 18px; height: 18px;
        color: var(--text-muted);
        flex-shrink: 0;
        margin-right: 10px;
    }
    .browse-mega-search input {
        flex: 1;
        padding: 14px 0;
        background: transparent;
        border: none;
        outline: none;
        color: #fff;
        font-family: inherit;
        font-size: 0.98rem;
    }
    .browse-mega-search input::placeholder { color: var(--text-muted); }
    .browse-mega-search .search-divider {
        width: 1px; height: 32px;
        background: rgba(255,255,255,0.10);
        flex-shrink: 0;
    }
    .browse-mega-search .city-select {
        padding: 12px 36px 12px 14px;
        background: transparent
            url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23c8cdd8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>")
            right 12px center/14px 14px no-repeat;
        border: none;
        color: #fff;
        font-family: inherit;
        font-size: 0.92rem;
        font-weight: 600;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        outline: none;
        max-width: 180px;
    }
    .browse-mega-search .city-select option { background: #0f1529; color: #fff; }
    .browse-mega-search .submit-btn {
        padding: 13px 22px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 8px 22px rgba(139,92,246,0.40);
        transition: transform 0.2s, opacity 0.2s;
    }
    .browse-mega-search .submit-btn:hover { transform: translateY(-1px); opacity: 0.95; }
    @media (max-width: 700px) {
        .browse-mega-search { flex-direction: column; align-items: stretch; padding: 8px; gap: 6px; }
        .browse-mega-search .search-divider { display: none; }
        .browse-mega-search .city-select { max-width: none; padding: 12px 36px 12px 14px; border-top: 1px solid rgba(255,255,255,0.08); }
        .browse-mega-search .submit-btn { width: 100%; justify-content: center; padding: 14px; }
    }

    /* ─── HERO QUICK STATS ─── */
    .browse-quickstats {
        display: flex; flex-wrap: wrap; gap: 24px;
        margin-top: 22px;
        font-size: 13px; color: var(--text-muted);
    }
    .browse-quickstats .qs {
        display: inline-flex; align-items: center; gap: 8px;
    }
    .browse-quickstats .qs strong {
        color: #fff; font-weight: 800;
    }
    .browse-quickstats svg { width: 14px; height: 14px; opacity: 0.7; }

    /* ─── CATEGORY CHIP RAIL ─── */
    .browse-cat-rail-wrap {
        position: relative;
        background: rgba(255,255,255,0.025);
        border-top: 1px solid rgba(255,255,255,0.06);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        margin-bottom: 32px;
    }
    .browse-cat-rail {
        display: flex; align-items: center; gap: 10px;
        overflow-x: auto;
        padding: 16px 0;
        scrollbar-width: none;
    }
    .browse-cat-rail::-webkit-scrollbar { display: none; }
    .browse-cat-chip {
        flex: 0 0 auto;
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 16px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 999px;
        color: var(--text-light);
        font-size: 13px; font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .browse-cat-chip:hover {
        background: rgba(139,92,246,0.10);
        border-color: rgba(139,92,246,0.40);
        color: #fff;
        transform: translateY(-1px);
    }
    .browse-cat-chip.is-active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 6px 16px rgba(139,92,246,0.35);
    }

    /* ─── ACTIVE FILTER PILLS ─── */
    .browse-active-filters {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: 8px; margin-bottom: 22px;
    }
    .browse-active-filters .label {
        font-size: 12px; font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.6px;
        margin-right: 6px;
    }
    .browse-filter-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 8px 6px 14px;
        background: rgba(139,92,246,0.10);
        border: 1px solid rgba(139,92,246,0.30);
        border-radius: 999px;
        color: #fff; font-size: 12.5px; font-weight: 700;
        text-decoration: none;
    }
    .browse-filter-pill .x {
        display: flex; align-items: center; justify-content: center;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: rgba(255,255,255,0.10);
        font-size: 12px; line-height: 1;
        cursor: pointer;
    }
    .browse-filter-pill .x:hover { background: rgba(255,255,255,0.20); }
    .browse-filter-pill.clear-all {
        background: transparent;
        border-color: rgba(239,68,68,0.30);
        color: #fca5a5;
    }
    .browse-filter-pill.clear-all:hover { background: rgba(239,68,68,0.10); }

    /* ─── TOOLBAR (results count + sort + view toggle) ─── */
    .browse-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 22px;
        padding: 14px 18px;
        background: rgba(255,255,255,0.025);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 14px;
    }
    .browse-count {
        font-size: 0.92rem;
        color: var(--text-muted);
    }
    .browse-count strong { color: #fff; font-weight: 800; font-size: 1.05rem; }

    .browse-toolbar-right {
        display: flex; align-items: center;
        gap: 12px;
        margin-left: auto;
        flex-wrap: wrap;
    }
    .browse-sort {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--text-muted);
    }
    .browse-sort select {
        padding: 9px 32px 9px 12px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04)
            url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23c8cdd8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>")
            right 10px center/12px 12px no-repeat;
        border: 1px solid rgba(255, 255, 255, 0.10);
        color: #fff;
        font-family: inherit;
        font-size: 0.88rem;
        font-weight: 600;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }
    .browse-sort select:focus { outline: none; border-color: rgba(139,92,246,0.45); }
    .browse-sort select option { background: #0f1529; }

    .view-toggle {
        display: inline-flex;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 10px;
        padding: 3px;
    }
    .view-toggle button {
        padding: 7px 10px;
        background: transparent;
        border: none;
        border-radius: 7px;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .view-toggle button.is-active {
        background: rgba(139,92,246,0.20);
        color: #fff;
    }
    .view-toggle button:hover { color: #fff; }
    .view-toggle svg { width: 16px; height: 16px; }

    .filters-mobile-trigger {
        display: none;
        padding: 9px 16px;
        background: rgba(139,92,246,0.10);
        border: 1px solid rgba(139,92,246,0.30);
        border-radius: 10px;
        color: #fff;
        font-family: inherit;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        align-items: center;
        gap: 8px;
    }
    .filters-mobile-trigger svg { width: 14px; height: 14px; }
    @media (max-width: 960px) { .filters-mobile-trigger { display: inline-flex; } }

    /* ─── LAYOUT: SIDEBAR + GRID ─── */
    .browse-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 28px;
        padding-bottom: 100px;
    }
    @media (max-width: 960px) {
        .browse-layout { grid-template-columns: 1fr; gap: 20px; }
    }

    /* ─── SIDEBAR FILTERS ─── */
    .browse-filters {
        position: sticky;
        top: 110px;
        align-self: start;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 18px;
        padding: 0;
        max-height: calc(100vh - 130px);
        overflow-y: auto;
        backdrop-filter: blur(8px);
    }
    /* Mobile drawer backdrop. Hidden by default at every viewport
       so it never claims a slot in the desktop grid layout. */
    .browse-filters-backdrop { display: none; }
    @media (max-width: 960px) {
        .browse-filters {
            position: fixed; top: 0; left: 0;
            width: 86%; max-width: 360px; height: 100vh;
            max-height: 100vh;
            border-radius: 0 18px 18px 0;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
        }
        .browse-filters.is-open { transform: translateX(0); }
        .browse-filters-backdrop.is-visible {
            display: block;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            z-index: 999;
        }
    }
    .browse-filters-head {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex; align-items: center; justify-content: space-between;
    }
    .browse-filters-head h2 {
        font-size: 0.95rem; font-weight: 800;
        margin: 0; display: inline-flex; align-items: center; gap: 8px;
    }
    .browse-filters-head h2 svg { width: 16px; height: 16px; color: #a78bfa; }
    .filters-mobile-close {
        display: none;
        background: transparent; border: none;
        color: var(--text-muted); cursor: pointer;
        padding: 6px;
    }
    @media (max-width: 960px) { .filters-mobile-close { display: inline-flex; } }

    .browse-filters-body {
        padding: 18px 22px 22px;
    }

    .filter-group + .filter-group {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    .filter-group h3 {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.4px;
        color: var(--text-muted);
        margin: 0 0 12px;
        display: flex; align-items: center; justify-content: space-between;
    }

    .browse-filters label,
    .browse-filters .filter-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        font-size: 0.9rem;
        color: var(--text-light);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .browse-filters label:hover,
    .browse-filters .filter-option:hover {
        background: rgba(255, 255, 255, 0.04);
        color: #fff;
    }
    .browse-filters input[type="radio"],
    .browse-filters input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 16px; height: 16px;
        border-radius: 50%;
        border: 1.5px solid rgba(255, 255, 255, 0.25);
        background: transparent;
        cursor: pointer;
        flex-shrink: 0;
        position: relative;
        transition: border-color 0.15s, background 0.15s;
    }
    .browse-filters input[type="checkbox"] { border-radius: 4px; }
    .browse-filters input[type="radio"]:checked,
    .browse-filters input[type="checkbox"]:checked {
        border-color: var(--primary);
        background: var(--primary);
    }
    .browse-filters input[type="radio"]:checked::after {
        content: '';
        position: absolute;
        inset: 3px;
        background: #fff;
        border-radius: 50%;
    }
    .browse-filters input[type="checkbox"]:checked::after {
        content: '✓';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 11px;
        font-weight: 900;
    }
    .browse-filters select,
    .browse-filters input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 10px;
        color: #fff;
        font-family: inherit;
        font-size: 0.88rem;
        outline: none;
    }
    .browse-filters select option { background: #0f1529; }
    .browse-filters select:focus,
    .browse-filters input[type="text"]:focus {
        border-color: rgba(139, 92, 246, 0.45);
    }
    .filter-actions {
        display: flex;
        gap: 8px;
        margin-top: 22px;
    }
    .btn-apply {
        flex: 1;
        padding: 11px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: filter 0.2s, transform 0.2s;
        font-family: inherit;
    }
    .btn-apply:hover { filter: brightness(1.08); transform: translateY(-1px); }
    .btn-clear {
        padding: 10px 14px;
        background: transparent;
        color: var(--text-muted);
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: color 0.15s, border-color 0.15s;
    }
    .btn-clear:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

    .filter-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .filter-chips a {
        font-size: 0.78rem;
        color: var(--text-light);
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.10);
        padding: 6px 12px;
        border-radius: 999px;
        text-decoration: none;
        transition: all 0.15s;
    }
    .filter-chips a:hover {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.40);
        color: #fff;
    }
    .filter-chips a.is-active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border-color: transparent;
        color: #fff;
    }

    /* ─── PROFESSIONAL GRID ─── */
    .pro-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 22px;
    }
    .pro-grid.is-list {
        grid-template-columns: 1fr;
    }
    .pro-grid.is-list .pro-card {
        flex-direction: row;
        align-items: stretch;
    }
    .pro-grid.is-list .pro-card-cover {
        flex: 0 0 220px;
        height: auto;
        min-height: 200px;
    }
    .pro-grid.is-list .pro-card-body {
        margin-top: 0;
        padding: 22px 26px;
    }
    .pro-grid.is-list .pro-card-avatar {
        position: absolute;
        top: 22px; left: 22px;
        margin-bottom: 0;
    }
    .pro-grid.is-list .pro-card-name,
    .pro-grid.is-list .pro-card-headline,
    .pro-grid.is-list .pro-card-meta,
    .pro-grid.is-list .pro-card-skills,
    .pro-grid.is-list .pro-card-foot {
        margin-left: 80px;
    }
    @media (max-width: 700px) {
        .pro-grid.is-list .pro-card { flex-direction: column; }
        .pro-grid.is-list .pro-card-cover { flex: 0 0 120px; min-height: 120px; }
        .pro-grid.is-list .pro-card-body { padding: 0 20px 20px; margin-top: -32px; }
        .pro-grid.is-list .pro-card-avatar { position: relative; top: auto; left: auto; margin-bottom: 12px; }
        .pro-grid.is-list .pro-card-name,
        .pro-grid.is-list .pro-card-headline,
        .pro-grid.is-list .pro-card-meta,
        .pro-grid.is-list .pro-card-skills,
        .pro-grid.is-list .pro-card-foot { margin-left: 0; }
    }

    .pro-card {
        position: relative;
        display: flex;
        flex-direction: column;
        background: var(--bg-card);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        overflow: hidden;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        text-decoration: none;
        color: inherit;
    }
    .pro-card:hover {
        transform: translateY(-6px);
        border-color: rgba(139, 92, 246, 0.40);
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.40);
    }
    .pro-card-cover {
        position: relative;
        height: 130px;
        background: linear-gradient(135deg, rgba(59,130,246,0.40), rgba(139,92,246,0.40));
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }
    .pro-card-cover::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 30%, rgba(21, 29, 53, 0.92) 100%);
    }
    .pro-card-badges {
        position: absolute;
        top: 10px;
        left: 10px;
        display: flex;
        gap: 6px;
        z-index: 2;
    }
    .pro-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 9px;
        background: rgba(8, 12, 22, 0.78);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        color: #fff;
    }
    .pro-badge.top {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(239, 68, 68, 0.95));
        border-color: rgba(255, 255, 255, 0.25);
    }
    .pro-badge.verified {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.95), rgba(16, 185, 129, 0.95));
        border-color: rgba(255, 255, 255, 0.20);
    }
    .pro-badge.new {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(139, 92, 246, 0.95));
        border-color: rgba(255, 255, 255, 0.20);
    }
    .pro-badge svg { width: 11px; height: 11px; }

    /* Save / favourite button (visual only — non-functional w/o auth integration) */
    .pro-card-save {
        position: absolute;
        top: 10px; right: 10px;
        z-index: 3;
        width: 34px; height: 34px;
        border-radius: 50%;
        background: rgba(8, 12, 22, 0.65);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pro-card-save svg {
        width: 16px; height: 16px;
        color: #fff;
        transition: fill 0.2s;
    }
    .pro-card-save:hover {
        background: rgba(239, 68, 68, 0.85);
        transform: scale(1.08);
    }
    .pro-card-save.is-saved svg { fill: #ef4444; color: #ef4444; }

    .pro-card-body {
        position: relative;
        padding: 0 20px 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
        margin-top: -32px;
    }
    .pro-card-avatar-wrap {
        position: relative;
        width: 64px; height: 64px;
        margin-bottom: 12px;
    }
    .pro-card-avatar {
        position: relative;
        z-index: 2;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--bg-card);
        background: var(--bg-card);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.30);
    }
    .pro-card-online {
        position: absolute;
        bottom: 2px; right: 2px;
        width: 14px; height: 14px;
        border-radius: 50%;
        background: #22c55e;
        border: 2.5px solid var(--bg-card);
        z-index: 3;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5);
        animation: pulse-dot 2.4s ease infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
        50%      { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .pro-card-online { animation: none; }
    }

    .pro-card-name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #fff;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pro-card-name .check {
        width: 15px; height: 15px;
        color: #60a5fa;
        flex-shrink: 0;
    }
    .pro-card-headline {
        font-size: 0.86rem;
        color: var(--text-light);
        line-height: 1.45;
        margin-bottom: 12px;
        min-height: 2.4em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Skills chips on each card */
    .pro-card-skills {
        display: flex; flex-wrap: wrap; gap: 4px;
        margin-bottom: 12px;
        min-height: 24px;
    }
    .pro-card-skills .skill {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(139,92,246,0.12);
        border: 1px solid rgba(139,92,246,0.25);
        color: #c4b5fd;
    }
    .pro-card-skills .skill.more {
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.10);
        color: var(--text-muted);
    }

    .pro-card-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .pro-card-meta .sep { opacity: 0.4; }
    .pro-card-meta .rating {
        display: inline-flex; align-items: center; gap: 4px;
        color: #ffb648;
        font-weight: 800;
    }
    .pro-card-meta .rating svg { width: 13px; height: 13px; fill: currentColor; stroke: none; }
    .pro-card-meta .rating .count { color: var(--text-muted); font-weight: 500; margin-left: 2px; }
    .pro-card-meta .loc {
        display: inline-flex; align-items: center; gap: 4px;
    }
    .pro-card-meta .loc svg { width: 12px; height: 12px; }

    .pro-card-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 14px;
        border-top: 1px dashed rgba(255, 255, 255, 0.10);
        margin-top: auto;
    }
    .pro-card-price {
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .pro-card-price strong {
        color: #fff;
        font-size: 1rem;
        font-weight: 900;
    }
    .pro-card-cta {
        font-size: 0.8rem;
        font-weight: 800;
        color: #a78bfa;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: gap 0.2s, color 0.2s;
    }
    .pro-card:hover .pro-card-cta { gap: 8px; color: #fff; }

    /* ─── EMPTY STATE ─── */
    .browse-empty {
        text-align: center;
        padding: 80px 20px;
        background: rgba(255,255,255,0.025);
        border: 1px dashed rgba(255, 255, 255, 0.10);
        border-radius: 18px;
    }
    .browse-empty .icon {
        width: 72px; height: 72px;
        margin: 0 auto 18px;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(59,130,246,0.20), rgba(139,92,246,0.20));
        border: 1px solid rgba(139,92,246,0.30);
        display: flex; align-items: center; justify-content: center;
        color: #a78bfa;
    }
    .browse-empty .icon svg { width: 32px; height: 32px; }
    .browse-empty h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 10px; color: #fff; }
    .browse-empty p { color: var(--text-muted); margin-bottom: 22px; max-width: 460px; margin-left: auto; margin-right: auto; line-height: 1.6; }
    .browse-empty .empty-actions {
        display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;
    }
    .browse-empty .btn {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
        transition: all 0.2s;
    }
    .browse-empty .btn-primary {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
    }
    .browse-empty .btn-primary:hover { transform: translateY(-1px); opacity: 0.95; }
    .browse-empty .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--text-light);
    }
    .browse-empty .btn-ghost:hover { border-color: rgba(255,255,255,0.30); color: #fff; }

    /* ─── PAGINATION ─── */
    .browse-pagination {
        margin-top: 36px;
        display: flex;
        justify-content: center;
    }
    .browse-pagination nav > div { display: flex !important; gap: 6px; }
    .browse-pagination span,
    .browse-pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 10px;
        color: var(--text-light);
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .browse-pagination a:hover {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.40);
        color: #fff;
    }
    .browse-pagination .page-item.active > span,
    .browse-pagination span[aria-current] {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 14px rgba(139,92,246,0.35);
    }
    .browse-pagination .disabled > span { opacity: 0.4; cursor: not-allowed; }
</style>
@endpush

@section('content')

@php
    // Compose readable labels for the active-filter pill row.
    $activeFilters = collect();
    if ($filters['q'])         $activeFilters->push(['key' => 'q',          'label' => '"' . $filters['q'] . '"']);
    if ($filters['city'])      $activeFilters->push(['key' => 'city',       'label' => 'in ' . $filters['city']]);
    if ($filters['rating_min'] > 0)
        $activeFilters->push(['key' => 'rating_min', 'label' => $filters['rating_min'] . '★ & up']);
    if ($filters['verified'])  $activeFilters->push(['key' => 'verified',   'label' => 'Verified only']);
@endphp

<!-- ── HERO BANNER ───────────────────────────────────────────── -->
<section class="browse-hero">
    {{-- Cover banner: a wedding reception / celebration scene that
         previews exactly what a verified professional helps create. --}}
    <div class="browse-hero-bg">
        <img src="https://images.unsplash.com/photo-1469371670807-013ccf25f16a?w=1800&q=80&auto=format&fit=crop" alt="" loading="eager">
    </div>
    <div class="container">
        <div class="browse-eyebrow">
            <span class="dot"></span> {{ $pros->total() }} verified professionals
        </div>
        <h1>Find the <span class="gradient-text">right professional</span> for your event</h1>
        <p class="lede">Photographers, caterers, DJs, planners — every verified professional in one place. Filter by city, rating, and trust badges to find your perfect match.</p>

        <form method="GET" action="{{ route('public.browse') }}" class="browse-mega-search" id="megaSearchForm">
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Try 'wedding photographer' or 'live band'…" autocomplete="off">
            </div>
            <span class="search-divider"></span>
            <select name="city" class="city-select" aria-label="City">
                <option value="">Any city</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}" {{ $filters['city'] === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
            <button type="submit" class="submit-btn">
                Search
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
            {{-- Carry over the rest of the filter state so quick searches don't lose them --}}
            <input type="hidden" name="rating_min" value="{{ $filters['rating_min'] }}">
            @if($filters['verified'])<input type="hidden" name="verified" value="1">@endif
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        </form>

        <div class="browse-quickstats">
            <span class="qs">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <strong>License-verified</strong> professionals
            </span>
            <span class="qs">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <strong>Escrow-protected</strong> bookings
            </span>
            <span class="qs">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Avg reply in <strong>2 hours</strong>
            </span>
        </div>
    </div>
</section>

<!-- ── CATEGORY CHIP RAIL ────────────────────────────────────── -->
@if($categories->isNotEmpty())
<div class="browse-cat-rail-wrap">
    <div class="container">
        <div class="browse-cat-rail" aria-label="Browse by category">
            <a href="{{ route('public.browse', array_filter(['city' => $filters['city'], 'sort' => $filters['sort']])) }}"
               class="browse-cat-chip {{ $filters['q'] === '' ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                All
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('public.browse', array_filter(['q' => $cat->name, 'city' => $filters['city'], 'sort' => $filters['sort']])) }}"
                   class="browse-cat-chip {{ $filters['q'] === $cat->name ? 'is-active' : '' }}">
                    @if($cat->icon){{ $cat->icon }}@endif
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- ── SIDEBAR + GRID ────────────────────────────────────────── -->
<div class="container">

    {{-- ACTIVE FILTER PILLS --}}
    @if($activeFilters->isNotEmpty())
        <div class="browse-active-filters">
            <span class="label">Active filters:</span>
            @foreach($activeFilters as $f)
                @php
                    // Build a URL with this filter removed so the X is meaningful.
                    $rest = $filters;
                    $rest[$f['key']] = '';
                @endphp
                <a href="{{ route('public.browse', array_filter($rest)) }}" class="browse-filter-pill">
                    {{ $f['label'] }}
                    <span class="x" aria-label="Remove filter">×</span>
                </a>
            @endforeach
            <a href="{{ route('public.browse') }}" class="browse-filter-pill clear-all">
                Clear all
            </a>
        </div>
    @endif

    {{-- Mobile drawer backdrop sits outside the grid so it can't ever
         claim a column on desktop. Click-through closes the drawer. --}}
    <div class="browse-filters-backdrop" id="filtersBackdrop"></div>

    <div class="browse-layout">

        {{-- ── SIDEBAR FILTERS ── --}}
        <aside id="filtersSidebar" class="browse-filters">
            <div class="browse-filters-head">
                <h2>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    Filters
                </h2>
                <button type="button" class="filters-mobile-close" aria-label="Close filters">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="GET" action="{{ route('public.browse') }}" class="browse-filters-body">
                {{-- Preserve search keyword and sort across filter submissions --}}
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

                {{-- Category quick chips --}}
                @if($categories->isNotEmpty())
                    <div class="filter-group">
                        <h3>Popular Categories</h3>
                        <div class="filter-chips">
                            @foreach($categories->take(10) as $cat)
                                <a href="{{ route('public.browse', array_filter(['q' => $cat->name, 'city' => $filters['city'], 'sort' => $filters['sort']])) }}"
                                   class="{{ $filters['q'] === $cat->name ? 'is-active' : '' }}">
                                    @if($cat->icon){{ $cat->icon }} @endif{{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- City filter --}}
                <div class="filter-group">
                    <h3>Location</h3>
                    <select name="city">
                        <option value="">Any city</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" {{ $filters['city'] === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rating filter --}}
                <div class="filter-group">
                    <h3>Minimum Rating</h3>
                    @foreach([0 => 'Any rating', 4 => '4.0★ & up', 4.5 => '4.5★ & up', 5 => '5.0★ only'] as $val => $label)
                        <label>
                            <input type="radio" name="rating_min" value="{{ $val }}" {{ (float) $filters['rating_min'] === (float) $val ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Trust filter --}}
                <div class="filter-group">
                    <h3>Trust &amp; Safety</h3>
                    <label>
                        <input type="checkbox" name="verified" value="1" {{ $filters['verified'] ? 'checked' : '' }}>
                        <span>Verified professionals only</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="filter-actions">
                    <button type="submit" class="btn-apply">Apply filters</button>
                    <a href="{{ route('public.browse') }}" class="btn-clear">Reset</a>
                </div>
            </form>
        </aside>

        {{-- ── RESULTS ── --}}
        <div>
            {{-- Toolbar: result count + sort + view toggle --}}
            <div class="browse-toolbar">
                <button type="button" class="filters-mobile-trigger" id="openFiltersBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    Filters
                </button>

                <div class="browse-count">
                    <strong>{{ $pros->total() }}</strong> {{ Str::plural('professional', $pros->total()) }} found
                </div>

                <div class="browse-toolbar-right">
                    <form method="GET" action="{{ route('public.browse') }}" class="browse-sort" id="sortForm">
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="city" value="{{ $filters['city'] }}">
                        <input type="hidden" name="rating_min" value="{{ $filters['rating_min'] }}">
                        @if($filters['verified'])<input type="hidden" name="verified" value="1">@endif
                        <span>Sort:</span>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="top"    {{ $filters['sort'] === 'top'    ? 'selected' : '' }}>Top-rated</option>
                            <option value="rating" {{ $filters['sort'] === 'rating' ? 'selected' : '' }}>Highest rating</option>
                            <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest first</option>
                        </select>
                    </form>

                    <div class="view-toggle" role="tablist" aria-label="View mode">
                        <button type="button" class="is-active" data-view="grid" aria-label="Grid view" title="Grid view">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                        <button type="button" data-view="list" aria-label="List view" title="List view">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Results --}}
            @if($pros->isEmpty())
                <div class="browse-empty">
                    <div class="icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <h3>No professionals match your filters</h3>
                    <p>Try a broader search, lower the minimum rating, or remove the city filter to see more results.</p>
                    <div class="empty-actions">
                        <a href="{{ route('public.browse') }}" class="btn btn-primary">Reset filters</a>
                        <a href="{{ route('events-categories') }}" class="btn btn-ghost">Browse categories</a>
                    </div>
                </div>
            @else
                <div class="pro-grid" id="proGrid">
                    @foreach($pros as $pro)
                        @php
                            $p = $pro->profile;
                            $isVerified = $p
                                && $p->trade_license_verified_at
                                && $p->liability_insurance_verified_at
                                && $p->workers_comp_verified_at;
                            $isTop = (float) ($pro->reviews_avg ?? 0) >= 4.5 && (int) ($pro->reviews_count ?? 0) >= 5;
                            $isNew = $pro->created_at && $pro->created_at->gt(now()->subDays(30));
                            $loc   = $p ? collect([$p->city, $p->state])->filter()->implode(', ') : null;
                            // Skills come back as a JSON array on UserProfile — show up to 3 chips.
                            $skills = is_array($p?->skills) ? array_slice($p->skills, 0, 3) : [];
                            $extraSkills = is_array($p?->skills) ? max(0, count($p->skills) - 3) : 0;
                        @endphp
                        <a href="{{ route('public.professional.show', $pro) }}" class="pro-card">
                            {{-- Cover with badges + save button --}}
                            <div class="pro-card-cover" @if($pro->cover_image_url) style="background-image: url('{{ $pro->cover_image_url }}');" @endif>
                                <div class="pro-card-badges">
                                    @if($isTop)
                                        <span class="pro-badge top">
                                            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            Top rated
                                        </span>
                                    @endif
                                    @if($isVerified)
                                        <span class="pro-badge verified">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Verified
                                        </span>
                                    @endif
                                    @if($isNew && !$isTop)
                                        <span class="pro-badge new">New</span>
                                    @endif
                                </div>

                                <button type="button" class="pro-card-save" aria-label="Save professional"
                                        onclick="event.preventDefault();event.stopPropagation();this.classList.toggle('is-saved');">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div class="pro-card-body">
                                <div class="pro-card-avatar-wrap">
                                    <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="pro-card-avatar" loading="lazy">
                                    <span class="pro-card-online" aria-label="Recently active"></span>
                                </div>

                                <div class="pro-card-name">
                                    {{ $pro->name }}
                                    @if($isVerified)
                                        <svg class="check" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 0l2.39 4.84 5.34.78-3.86 3.77.91 5.31L12 12.18l-4.78 2.52.91-5.31L4.27 5.62l5.34-.78z" transform="scale(0.95) translate(0.7 0)"/><path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                                    @endif
                                </div>

                                <p class="pro-card-headline">
                                    {{ $p->headline ?? $p->bio ?? 'Professional event service provider.' }}
                                </p>

                                @if(!empty($skills))
                                    <div class="pro-card-skills">
                                        @foreach($skills as $sk)
                                            <span class="skill">{{ $sk }}</span>
                                        @endforeach
                                        @if($extraSkills > 0)
                                            <span class="skill more">+{{ $extraSkills }}</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="pro-card-meta">
                                    @if($pro->reviews_count > 0)
                                        <span class="rating">
                                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            {{ number_format($pro->reviews_avg, 1) }}
                                            <span class="count">({{ $pro->reviews_count }})</span>
                                        </span>
                                    @else
                                        <span class="rating" style="color: var(--text-muted);">
                                            <svg viewBox="0 0 24 24" style="fill: currentColor;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            New
                                        </span>
                                    @endif

                                    @if($loc)
                                        <span class="sep">•</span>
                                        <span class="loc">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            {{ $loc }}
                                        </span>
                                    @endif
                                </div>

                                <div class="pro-card-foot">
                                    <div class="pro-card-price">
                                        @if($p && $p->hourly_rate)
                                            From <strong>${{ number_format((float) $p->hourly_rate, 0) }}</strong>/hr
                                        @else
                                            <span style="color: var(--text-muted);">Contact for pricing</span>
                                        @endif
                                    </div>
                                    <span class="pro-card-cta">
                                        View profile
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($pros->hasPages())
                    <div class="browse-pagination">
                        {{ $pros->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ─── View toggle (grid ↔ list) ──────────────────────────────
    // Persist choice in localStorage so it sticks across visits.
    (function() {
        const grid    = document.getElementById('proGrid');
        const buttons = document.querySelectorAll('.view-toggle [data-view]');
        if (!grid || !buttons.length) return;

        const STORAGE_KEY = 'browse.viewMode';
        const saved = localStorage.getItem(STORAGE_KEY);

        function setView(mode) {
            buttons.forEach(b => b.classList.toggle('is-active', b.dataset.view === mode));
            grid.classList.toggle('is-list', mode === 'list');
            try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) { /* ignore quota errors */ }
        }

        if (saved === 'list') setView('list');

        buttons.forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
    })();

    // ─── Mobile filters drawer ──────────────────────────────────
    (function() {
        const sidebar  = document.getElementById('filtersSidebar');
        const backdrop = document.getElementById('filtersBackdrop');
        const opener   = document.getElementById('openFiltersBtn');
        const closer   = document.querySelector('.filters-mobile-close');
        if (!sidebar || !backdrop || !opener) return;

        function open() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.style.overflow = '';
        }

        opener.addEventListener('click', open);
        backdrop.addEventListener('click', close);
        if (closer) closer.addEventListener('click', close);

        // Reset drawer state when crossing the breakpoint up to desktop
        const mq = window.matchMedia('(min-width: 961px)');
        mq.addEventListener?.('change', (e) => { if (e.matches) close(); });
    })();
</script>
@endpush
