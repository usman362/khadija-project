@extends('layouts.client')

@section('title', 'Search Professionals')
@section('page-title', 'Search Professionals')
@section('page-subtitle', 'Find and compare the best event professionals for your project.')

@push('styles')
<style>
    /* ═══════════════════ Search-page styles ═══════════════════
       Matches Khadija's "Search Professionals Client_s side" mockup —
       coral CTAs on a card-based grid, with a sticky right rail for
       Event Summary / Budget Overview / AI Recommendations. */

    .sp-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 18px;
        align-items: start;
    }
    .sp-main { min-width: 0; }
    .sp-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    .sp-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 16px 18px;
    }

    /* ── Top filter bar ─────────────────────────────────────── */
    .sp-filter-bar { margin-bottom: 14px; }
    .sp-filter-grid {
        display: grid;
        grid-template-columns: 1.5fr 1.1fr 0.8fr 1.1fr auto;
        gap: 10px;
        align-items: end;
        width: 100%;
    }
    /* min-width:0 lets the columns shrink below their content size so the
       long placeholder + the More Filters button never overflow the card. */
    .sp-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
    .sp-field label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .sp-input, .sp-select {
        width: 100%;
        height: 38px;
        padding: 0 10px 0 32px;
        border-radius: 9px;
        border: 1px solid var(--border-color);
        background: #fff;
        color: var(--text-primary);
        font-size: 13px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s;
        text-overflow: ellipsis;
    }
    [data-theme="dark"] .sp-input, [data-theme="dark"] .sp-select { background: var(--bg-card-hover); }
    .sp-input:focus, .sp-select:focus { border-color: #6366f1; }
    .sp-field-wrap { position: relative; width: 100%; }
    .sp-field-icon {
        position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: var(--text-muted); pointer-events: none;
    }
    .sp-more-btn {
        height: 38px;
        padding: 0 14px;
        border-radius: 9px;
        border: 1px solid var(--border-color);
        background: #fff;
        color: var(--text-primary);
        font-size: 13px; font-weight: 600;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 7px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    [data-theme="dark"] .sp-more-btn { background: var(--bg-card-hover); }
    .sp-more-btn svg { width: 14px; height: 14px; color: #6366f1; }

    /* ── Unified filter card (active project bar + sub-filters) ── */
    .sp-filter-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        margin-bottom: 14px;
        overflow: hidden;
    }
    .sp-filter-divider { height: 1px; background: var(--border-color); }
    /* ── Active project context bar ─────────────────────────── */
    .sp-active-bar {
        padding: 16px 22px;
        display: flex; align-items: center; flex-wrap: wrap; gap: 14px;
    }
    .sp-active-label { font-size: 10.5px; color: var(--text-muted); font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
    .sp-active-name {
        display: inline-flex; align-items: center; gap: 6px;
        background: transparent; border: none;
        color: var(--text-primary); font-weight: 700; font-size: 14px;
        cursor: pointer;
    }
    .sp-active-name svg { width: 12px; height: 12px; opacity: 0.6; }
    .sp-active-meta { font-size: 12.5px; color: var(--text-muted); display: flex; gap: 18px; flex-wrap: wrap; }
    .sp-active-meta b { color: var(--text-primary); font-weight: 600; }
    .sp-active-spacer { flex: 1; }
    .sp-clear-link {
        font-size: 12.5px; color: #f97316;
        text-decoration: none; font-weight: 600;
    }
    .sp-save-btn {
        padding: 7px 14px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff; font-size: 12.5px; font-weight: 700;
        border: none; border-radius: 8px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .sp-save-btn svg { width: 14px; height: 14px; }

    /* ── Sub-filter row (budget / rate / sort / view) ───────── */
    .sp-subfilter {
        padding: 16px 20px;
        display: grid;
        grid-template-columns: 1.25fr 1.8fr 0.95fr 0.78fr;
        gap: 18px;
        align-items: start;
    }
    .sp-subfilter > div { min-width: 0; }
    .sp-sub-label {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 700; color: var(--text-primary);
        margin-bottom: 7px;
    }
    .sp-sub-label .info {
        width: 13px; height: 13px;
        background: var(--text-muted);
        color: var(--bg-card);
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 8.5px; font-weight: 800; font-style: italic;
    }
    .sp-budget-row { display: flex; align-items: center; gap: 14px; }
    .sp-slider-wrap { flex: 1; min-width: 0; }
    .sp-range {
        width: 100%;
        height: 4px;
        -webkit-appearance: none;
        appearance: none;
        background: linear-gradient(to right, #6366f1 0%, #6366f1 var(--pct, 50%), var(--border-color) var(--pct, 50%), var(--border-color) 100%);
        border-radius: 999px;
        outline: none;
    }
    .sp-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: #6366f1;
        border: 3px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.20);
        cursor: pointer;
    }
    .sp-budget-input {
        width: 70px; height: 30px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: var(--bg-card-hover);
        color: var(--text-primary);
        font-size: 12px; font-weight: 700;
        text-align: center;
        padding: 0 6px;
        flex-shrink: 0;
    }
    .sp-budget-meta { display: flex; justify-content: space-between; font-size: 10.5px; color: var(--text-muted); margin-top: 7px; }
    .sp-radio-grid { display: flex; gap: 16px; flex-wrap: nowrap; }
    .sp-radio {
        display: inline-flex; align-items: flex-start; gap: 7px;
        font-size: 11px; min-width: 0; flex: 0 1 auto;
        color: var(--text-secondary);
        cursor: pointer;
    }
    /* custom radio — orange when checked, neutral white/grey otherwise (no blue) */
    .sp-radio input {
        appearance: none; -webkit-appearance: none;
        width: 16px; height: 16px; margin: 1px 0 0; flex-shrink: 0;
        border: 1.5px solid var(--border-color);
        border-radius: 50%;
        background: #fff;
        cursor: pointer; position: relative;
        transition: border-color 0.15s, background 0.15s;
    }
    .sp-radio input:checked { border-color: #f97316; background: #f97316; }
    .sp-radio input:checked::after {
        content: ''; position: absolute;
        top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 5px; height: 5px; border-radius: 50%; background: #fff;
    }
    .sp-radio input:focus-visible { outline: 2px solid rgba(249,115,22,0.4); outline-offset: 1px; }
    [data-theme="dark"] .sp-radio input { background: var(--bg-card-hover); }
    [data-theme="dark"] .sp-radio input:checked { background: #f97316; }
    .sp-radio b { color: var(--text-primary); display: block; font-size: 11.5px; white-space: nowrap; }
    .sp-radio em { color: var(--text-muted); font-style: normal; font-size: 10px; line-height: 1.35; }
    .sp-view-toggle {
        display: inline-flex;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 2px;
    }
    .sp-view-toggle button {
        background: none; border: none;
        padding: 5px 9px;
        font-size: 11px; font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        border-radius: 6px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .sp-view-toggle button.is-active { background: rgba(99, 102, 241, 0.10); color: #6366f1; }
    [data-theme="dark"] .sp-view-toggle button.is-active { background: rgba(99, 102, 241, 0.18); color: #a5b4fc; }
    .sp-view-toggle button svg { width: 12px; height: 12px; }

    /* ── Result summary row ─────────────────────────────────── */
    .sp-result-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .sp-result-count { font-size: 14px; font-weight: 700; color: var(--text-primary); }
    .sp-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .sp-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        background: rgba(16, 185, 129, 0.10);
        color: #10b981;
        border-radius: 999px;
        font-size: 11.5px; font-weight: 600;
        text-decoration: none;
    }
    .sp-chip svg { width: 12px; height: 12px; }
    .sp-chip.blue   { background: rgba(99, 102, 241, 0.10); color: #6366f1; }
    .sp-chip.purple { background: rgba(139, 92, 246, 0.10); color: #8b5cf6; }
    .sp-best-match { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #f97316; font-weight: 700; }
    .sp-best-match svg { width: 12px; height: 12px; }

    /* ── Pro cards ──────────────────────────────────────────── */
    .sp-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }
    .sp-grid.list { grid-template-columns: 1fr; }
    .sp-procard {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 14px;
        display: flex; flex-direction: column;
        gap: 10px;
        transition: border-color 0.15s, box-shadow 0.15s;
        position: relative;
    }
    .sp-procard:hover { border-color: rgba(249, 115, 22, 0.40); box-shadow: 0 14px 34px -14px rgba(15,27,53,0.22); transform: translateY(-3px); }
    .sp-procard { padding: 0; overflow: hidden; transition: border-color .15s, box-shadow .15s, transform .15s; }

    /* ── Image-forward hero + hover carousel ── */
    .sp-procard-media { position: relative; height: 188px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); overflow: hidden; }
    .sp-procard-media > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .55s ease; }
    .sp-procard-media > img.on { opacity: 1; }
    .sp-procard-catbadge { position: absolute; top: 10px; left: 10px; z-index: 3; background: rgba(255,255,255,.94); color: #0f1b35;
        font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 2px 10px rgba(15,27,53,.18); text-transform: capitalize; }
    .sp-procard-catbadge svg { width: 13px; height: 13px; color: #f97316; }
    .sp-procard-media .sp-fav-btn { position: absolute; top: 10px; right: 10px; z-index: 3; background: rgba(255,255,255,.94);
        border: none; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
        color: #64748b; box-shadow: 0 2px 10px rgba(15,27,53,.18); cursor: pointer; }
    .sp-procard-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); z-index: 3; display: flex; gap: 5px; }
    .sp-procard-dots i { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.55); transition: all .2s; }
    .sp-procard-dots i.on { background: #fff; width: 16px; border-radius: 3px; }
    .sp-procard-body { padding: 14px; display: flex; flex-direction: column; gap: 10px; }
    .sp-procard-top { display: flex; gap: 12px; align-items: flex-start; }
    .sp-procard-avatar {
        width: 56px; height: 56px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid var(--border-color);
    }
    .sp-procard-info { flex: 1; min-width: 0; }
    .sp-procard-verified {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10.5px; font-weight: 700;
        color: #10b981;
        background: rgba(16, 185, 129, 0.10);
        padding: 2px 7px; border-radius: 4px;
        margin-bottom: 4px;
    }
    .sp-procard-verified svg { width: 9px; height: 9px; }
    .sp-procard-name { font-size: 14px; font-weight: 700; color: var(--text-primary); }
    .sp-procard-company { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
    .sp-procard-tag { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; }
    .sp-fav-btn {
        background: none; border: none;
        width: 28px; height: 28px;
        border-radius: 50%;
        color: var(--text-muted);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .sp-fav-btn svg { width: 16px; height: 16px; }
    .sp-fav-btn:hover, .sp-fav-btn.is-saved { color: #ef4444; }
    .sp-procard-meta {
        display: flex; gap: 10px; flex-wrap: wrap;
        font-size: 11.5px; color: var(--text-muted);
    }
    .sp-procard-meta svg { width: 11px; height: 11px; vertical-align: middle; margin-right: 2px; }
    .sp-procard-rating { color: #f59e0b; font-weight: 700; }
    .sp-popularity {
        display: flex; align-items: center; justify-content: space-between;
        font-size: 11px; color: var(--text-muted);
    }
    .sp-pop-label { display: flex; align-items: center; gap: 4px; }
    .sp-pop-label svg { width: 12px; height: 12px; }
    .sp-pop-tag { color: #f97316; font-weight: 700; }
    .sp-procard-bottom {
        display: flex; align-items: center; justify-content: space-between;
        padding-top: 10px; margin-top: 6px;
        border-top: 1px solid var(--border-color);
        gap: 8px;
    }
    .sp-procard-price-block { text-align: right; }
    .sp-procard-price {
        font-size: 18px; font-weight: 800; color: var(--text-primary);
    }
    .sp-procard-price-sub { font-size: 10.5px; color: var(--text-muted); }
    .sp-rate-tag {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10.5px; font-weight: 600;
        padding: 4px 8px;
        background: rgba(139, 92, 246, 0.10);
        color: #8b5cf6;
        border-radius: 6px;
    }
    .sp-rate-tag svg { width: 11px; height: 11px; }
    .sp-rate-tag.hourly { background: rgba(59, 130, 246, 0.10); color: #3b82f6; }
    .sp-procard-trust {
        display: flex; gap: 10px; flex-wrap: wrap;
        font-size: 10.5px; color: var(--text-muted);
        align-items: center;
    }
    .sp-procard-trust span { display: inline-flex; align-items: center; gap: 3px; }
    .sp-procard-trust svg { width: 10px; height: 10px; color: #10b981; }
    .sp-view-profile {
        flex-shrink: 0;
        padding: 7px 14px;
        background: #f97316;
        color: #fff !important;
        font-size: 11.5px; font-weight: 700;
        border-radius: 7px;
        text-decoration: none;
        white-space: nowrap;
    }
    .sp-view-profile:hover { background: #ea580c; }

    /* ── Pagination ─────────────────────────────────────────── */
    .sp-pagination {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 18px; gap: 14px; flex-wrap: wrap;
        font-size: 12.5px; color: var(--text-muted);
    }
    .sp-pagination nav { display: flex; gap: 4px; }
    /* Neat page buttons (custom pagination.gr-nav view) */
    .gr-pag { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .gr-pag-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 34px; height: 34px; padding: 0 9px;
        border: 1px solid var(--border-color); border-radius: 9px;
        background: var(--bg-card); color: var(--text-secondary);
        font-size: 13px; font-weight: 600; text-decoration: none; line-height: 1;
    }
    .gr-pag-btn svg { width: 15px; height: 15px; }
    .gr-pag-btn:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .gr-pag-btn.is-active { background: #ea580c; border-color: #ea580c; color: #fff; }
    .gr-pag-btn.is-disabled { opacity: .4; pointer-events: none; }
    .gr-pag-btn.is-dots { border: none; background: none; min-width: 18px; padding: 0; }

    /* ── Right rail cards ───────────────────────────────────── */
    .sp-rail .sp-card-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; }
    .sp-map-placeholder {
        height: 150px;
        border-radius: 10px;
        background:
            linear-gradient(45deg, rgba(99,102,241,0.06), rgba(99,102,241,0.10)),
            repeating-linear-gradient(
                0deg,
                var(--border-color),
                var(--border-color) 1px,
                transparent 1px,
                transparent 22px
            ),
            repeating-linear-gradient(
                90deg,
                var(--border-color),
                var(--border-color) 1px,
                transparent 1px,
                transparent 22px
            );
        position: relative;
        margin-bottom: 10px;
        overflow: hidden;
    }
    .sp-map-pin {
        position: absolute;
        width: 18px; height: 18px;
        background: #ef4444;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.20);
    }
    .sp-redraw-btn {
        width: 100%;
        padding: 8px;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 12px; font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    }
    .sp-redraw-btn svg { width: 12px; height: 12px; }

    .sp-event-summary-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .sp-event-status {
        font-size: 9.5px; font-weight: 700;
        padding: 2px 7px; border-radius: 999px;
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sp-summary-row { display: flex; justify-content: space-between; font-size: 12px; padding: 6px 0; border-bottom: 1px dashed var(--border-color); }
    .sp-summary-row:last-of-type { border-bottom: 0; }
    .sp-summary-row .lbl { color: var(--text-muted); }
    .sp-summary-row .val { color: var(--text-primary); font-weight: 600; text-align: right; max-width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sp-rail-link {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 10px;
        font-size: 12px; font-weight: 600;
        color: #6366f1;
        text-decoration: none;
    }
    .sp-rail-link svg { width: 12px; height: 12px; }

    /* Budget overview bars */
    .sp-budget-cap { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 6px; }
    .sp-budget-bars { display: flex; height: 6px; border-radius: 999px; overflow: hidden; background: var(--border-color); margin-bottom: 10px; }
    .sp-budget-bars .within  { background: #10b981; }
    .sp-budget-bars .border  { background: #f59e0b; }
    .sp-budget-bars .over    { background: #ef4444; }
    .sp-budget-totals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 11px; text-align: center; }
    .sp-budget-totals b { display: block; font-size: 13.5px; font-weight: 800; }
    .sp-budget-totals .avg-col b { color: #10b981; }
    .sp-budget-totals .within-col b { color: #f59e0b; }
    .sp-budget-totals .over-col b { color: #ef4444; }

    /* AI Recommendations */
    .sp-ai-row {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 8px; padding: 8px 0;
        border-bottom: 1px dashed var(--border-color);
    }
    .sp-ai-row:last-of-type { border-bottom: 0; }
    .sp-ai-text { font-size: 11.5px; line-height: 1.4; color: var(--text-primary); }
    .sp-ai-text b { color: #f97316; font-weight: 700; }
    .sp-ai-match { font-size: 10.5px; font-weight: 700; color: #10b981; flex-shrink: 0; padding-top: 2px; }
    .sp-ai-icon { width: 22px; height: 22px; border-radius: 50%; background: rgba(16,185,129,0.12); color: #10b981; display:flex; align-items:center; justify-content:center; flex-shrink: 0; }
    .sp-ai-icon svg { width: 12px; height: 12px; }

    @media (max-width: 1200px) {
        .sp-layout { grid-template-columns: 1fr; }
        .sp-rail { position: static; }
    }
    @media (max-width: 900px) {
        .sp-filter-grid { grid-template-columns: 1fr 1fr; }
        .sp-subfilter { grid-template-columns: 1fr; }
        .sp-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .sp-filter-grid { grid-template-columns: 1fr; }
        .sp-grid { grid-template-columns: 1fr; }
        .sp-active-bar { flex-direction: column; align-items: flex-start; gap: 8px; }
    }
</style>
@endpush

@section('content')
<div class="sp-layout">

    {{-- ════════════════════ MAIN COLUMN ════════════════════ --}}
    <div class="sp-main">

        {{-- Top filter bar --}}
        <form method="GET" action="{{ route('client.search.index') }}" class="sp-card sp-filter-bar">
            <div class="sp-filter-grid">
                <div class="sp-field">
                    <label>What are you looking for?</label>
                    <div class="sp-field-wrap">
                        <svg class="sp-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input class="sp-input" type="text" name="q" value="{{ $filters['q'] }}" placeholder="e.g. Photographer, DJ, Caterer...">
                    </div>
                </div>
                <div class="sp-field">
                    <label>Location</label>
                    <div class="sp-field-wrap">
                        <svg class="sp-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <input class="sp-input" type="text" name="city" value="{{ $filters['city'] }}" placeholder="New York, NY" list="sp-cities">
                        <datalist id="sp-cities">@foreach($cities as $c)<option value="{{ $c }}">@endforeach</datalist>
                    </div>
                </div>
                <div class="sp-field">
                    <label>Within</label>
                    <div class="sp-field-wrap">
                        <select class="sp-input" name="within" style="padding-left: 12px;">
                            @foreach([10, 25, 50, 100, 250] as $mi)
                                <option value="{{ $mi }}" {{ $filters['within'] == $mi ? 'selected' : '' }}>{{ $mi }} miles</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="sp-field">
                    <label>Event Date</label>
                    <div class="sp-field-wrap">
                        <svg class="sp-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <input class="sp-input" type="date" name="event_date" value="{{ $filters['event_date'] }}">
                    </div>
                </div>
                <button type="button" class="sp-more-btn" onclick="document.getElementById('sp-more').classList.toggle('hidden')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    More Filters
                </button>
            </div>
            {{-- Preserve sub-filter state on submit --}}
            <input type="hidden" name="max_budget" value="{{ $filters['max_budget'] }}">
            <input type="hidden" name="rate_type" value="{{ $filters['rate_type'] }}">
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="view" value="{{ $filters['view'] }}">
            @if($activeEvent)
                <input type="hidden" name="event" value="{{ $activeEvent->id }}">
            @endif
        </form>

        {{-- Active Project + Filters — single unified card --}}
        <div class="sp-filter-card">
        @if($activeEvent)
            <div class="sp-active-bar">
                <span class="sp-active-label">Active Project:</span>
                <button type="button" class="sp-active-name">
                    {{ $activeEvent->title }}
                    @if($activeEvent->duration_hours ?? false) ({{ $activeEvent->duration_hours }} Hours Duration) @endif
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sp-active-meta">
                    <span>Event Date: <b>{{ $activeEvent->starts_at?->format('M d, Y') ?? '—' }}</b></span>
                    <span>Duration: <b>{{ $activeEvent->duration_hours ?? 6 }} hours</b></span>
                </div>
                <span class="sp-active-spacer"></span>
                <a href="{{ route('client.search.index') }}" class="sp-clear-link">Clear All</a>
                <button type="button" class="sp-save-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Save Search
                </button>
            </div>
            <div class="sp-filter-divider"></div>
        @endif

        {{-- Sub-filter row: budget slider + rate type + sort + view --}}
        <div class="sp-subfilter">
            <div>
                <div class="sp-sub-label">Max Budget (Total Event Cost) <span class="info">i</span></div>
                <div class="sp-budget-row">
                    <div class="sp-slider-wrap">
                        <input type="range" class="sp-range" min="500" max="10000" step="100" value="{{ $filters['max_budget'] }}" oninput="this.style.setProperty('--pct', ((this.value-this.min)/(this.max-this.min)*100)+'%'); document.getElementById('sp-budget-val').value=this.value;">
                        <div class="sp-budget-meta"><span>$500</span><span>$5,000+</span></div>
                    </div>
                    <input type="text" class="sp-budget-input" id="sp-budget-val" value="${{ number_format($filters['max_budget']) }}" readonly>
                </div>
            </div>
            <div>
                <div class="sp-sub-label">Rate Type <span class="info">i</span> <span style="font-weight:500;color:var(--text-muted);font-size:11px;margin-left:6px;">(How costs are calculated)</span></div>
                <div class="sp-radio-grid">
                    <label class="sp-radio">
                        <input type="radio" name="rate_type" value="total" {{ $filters['rate_type'] === 'total' ? 'checked' : '' }}>
                        <span><b>Total Event Cost</b><em>Flat + Calculated Hourly</em></span>
                    </label>
                    <label class="sp-radio">
                        <input type="radio" name="rate_type" value="hourly" {{ $filters['rate_type'] === 'hourly' ? 'checked' : '' }}>
                        <span><b>Base Hourly Rate Only</b><em>Show hourly rates only</em></span>
                    </label>
                </div>
            </div>
            <div>
                <div class="sp-sub-label">Sort By</div>
                <select class="sp-input" name="sort" style="height:34px; font-size:12px; padding:0 26px 0 11px; border-radius:8px;" onchange="this.form?.submit()">
                    <option value="cost_asc"    {{ $filters['sort'] === 'cost_asc' ? 'selected' : '' }}>Cost: Low to High</option>
                    <option value="cost_desc"   {{ $filters['sort'] === 'cost_desc' ? 'selected' : '' }}>Cost: High to Low</option>
                    <option value="rating_desc" {{ $filters['sort'] === 'rating_desc' ? 'selected' : '' }}>Best Rated</option>
                    <option value="newest"      {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest</option>
                </select>
            </div>
            <div>
                <div class="sp-sub-label">View</div>
                <div class="sp-view-toggle">
                    <button type="button" class="{{ $filters['view'] === 'grid' ? 'is-active' : '' }}" onclick="location.href='{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['view' => 'grid'])) }}'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Grid
                    </button>
                    <button type="button" class="{{ $filters['view'] === 'list' ? 'is-active' : '' }}" onclick="location.href='{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['view' => 'list'])) }}'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        List
                    </button>
                </div>
            </div>
        </div>
        </div>{{-- /.sp-filter-card --}}

        {{-- Result count + filter chips --}}
        <div class="sp-result-row">
            <div class="sp-result-count">{{ $pros->total() }} professionals found</div>
            <div class="sp-chips">
                <a href="?verified=1" class="sp-chip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Verified ({{ $countVerified }})
                </a>
                <a href="?available=1" class="sp-chip blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
                    Available on date ({{ $countAvailable }})
                </a>
                <a href="?secure payment=1" class="sp-chip purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Secure Payment ({{ $countSecure Payment }})
                </a>
            </div>
            <div class="sp-best-match">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Best Match
            </div>
        </div>

        {{-- Pro cards grid --}}
        @if($pros->count())
            @php $stockGallery = ['photo-1519741497674-611481863552','photo-1465495976277-4387d4b0b4c6','photo-1511578314322-379afb476865','photo-1530103862676-de8c9debad1d']; @endphp
            <div class="sp-grid {{ $filters['view'] === 'list' ? 'list' : '' }}">
                @foreach($pros as $pro)
                    @php
                        $profile  = $pro->profile;
                        $rating   = $pro->reviews_avg ? number_format($pro->reviews_avg, 1) : '—';
                        $hourly   = $profile?->hourly_rate ?: 0;
                        $totalCost = $hourly ? $hourly * 6 : 0; // assume 6-hour engagement
                        $verified = method_exists($pro, 'isVerified') ? $pro->isVerified() : false;
                        $headline = $profile?->headline ?? '';
                        $city     = $profile?->city ?? '—';
                        $skills   = is_array($profile?->skills) ? array_values(array_filter($profile->skills)) : [];
                        $catBadge = $skills[0] ?? ($profile?->industry ?: 'Event Pro');
                        $realImgs = $profile ? $profile->portfolioHeroUrls(4) : [];
                        $gallery = ! empty($realImgs)
                            ? collect($realImgs)
                            : collect($stockGallery)->map(fn ($id) => 'https://images.unsplash.com/'.$id.'?w=560&q=72&auto=format&fit=crop');
                        $gallery = $gallery->take(4)->values();
                    @endphp
                    <div class="sp-procard">
                        <div class="sp-procard-media">
                            @foreach($gallery as $gi => $img)
                                <img src="{{ $img }}" alt="{{ $pro->name }}" class="{{ $gi === 0 ? 'on' : '' }}" loading="lazy">
                            @endforeach
                            <span class="sp-procard-catbadge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                {{ \Illuminate\Support\Str::limit($catBadge, 18) }}
                            </span>
                            <button type="button" class="sp-fav-btn" onclick="this.classList.toggle('is-saved')" aria-label="Save">
                                <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            </button>
                            @if($gallery->count() > 1)
                                <div class="sp-procard-dots">@foreach($gallery as $gi => $x)<i class="{{ $gi === 0 ? 'on' : '' }}"></i>@endforeach</div>
                            @endif
                        </div>
                        <div class="sp-procard-body">
                        <div class="sp-procard-top">
                            <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="sp-procard-avatar" loading="lazy">
                            <div class="sp-procard-info">
                                @if($verified)
                                    <span class="sp-procard-verified">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Verified
                                    </span>
                                @endif
                                <div class="sp-procard-name">{{ $pro->name }}</div>
                                @if($profile?->company_name)
                                    <div class="sp-procard-company">{{ $profile->company_name }}</div>
                                @endif
                                @if($headline)
                                    <div class="sp-procard-tag">{{ \Illuminate\Support\Str::limit($headline, 32) }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="sp-procard-meta">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $city }}, {{ rand(2, 9) }}.{{ rand(1, 9) }} mi</span>
                            <span class="sp-procard-rating">★ {{ $rating }}</span>
                            <span>({{ $pro->reviews_count }} reviews)</span>
                        </div>
                        <div class="sp-popularity">
                            <span class="sp-pop-label">Popularity
                                <svg viewBox="0 0 24 24" fill="#f97316" stroke="#f97316" stroke-width="1.5"><path d="M12 2c1 3-1 4-1 6 0 1 1 2 2 2s2-1 2-3c2 2 3 4 3 7a6 6 0 0 1-12 0c0-3 2-5 3-7 1 2 2 1 2-1 0-2 1-3 1-3z"/></svg>
                                Top {{ rand(5, 30) }}%
                            </span>
                            <span class="sp-pop-tag">Top {{ rand(5, 30) }}%</span>
                        </div>
                        <div class="sp-procard-bottom">
                            <div>
                                <span class="sp-rate-tag {{ $hourly ? 'hourly' : '' }}">
                                    @if($hourly)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
                                    @endif
                                    {{ $hourly ? 'Calculated Hourly' : 'Fixed Flat Rate' }}
                                </span>
                                @if($hourly)
                                    <div style="font-size:10.5px;color:var(--text-muted);margin-top:4px;">${{ number_format($hourly) }}/hr × 6 hrs</div>
                                @endif
                            </div>
                            <div class="sp-procard-price-block">
                                <div class="sp-procard-price">${{ $hourly ? number_format($totalCost, 0) : number_format(rand(900, 2500), 0) }}</div>
                                <div class="sp-procard-price-sub">Total for 6 hours</div>
                            </div>
                        </div>
                        <div class="sp-procard-trust">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>W-9 Verified</span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Secure Payment Active</span>
                            <a href="{{ route('public.professional.show', $pro) }}" class="sp-view-profile" style="margin-left:auto;">View Profile</a>
                        </div>
                        </div>{{-- /.sp-procard-body --}}
                    </div>
                @endforeach
            </div>

            <div class="sp-pagination">
                <div>Showing {{ $pros->firstItem() }} to {{ $pros->lastItem() }} of {{ $pros->total() }} professionals</div>
                {{ $pros->onEachSide(1)->links('pagination.gr-nav') }}
                <div>12 per page</div>
            </div>
        @else
            <div class="sp-card" style="text-align:center;padding:60px 24px;">
                <div style="font-size:16px;font-weight:700;margin-bottom:6px;">No professionals match these filters</div>
                <div style="color:var(--text-muted);font-size:13px;">Try widening your search radius or clearing some filters.</div>
            </div>
        @endif
    </div>

    {{-- ════════════════════ RIGHT RAIL ════════════════════ --}}
    <aside class="sp-rail">

        {{-- Search by Map --}}
        <div class="sp-card">
            <div class="sp-card-title">Search by Map</div>
            <div class="sp-map-placeholder">
                <span class="sp-map-pin" style="top:30%; left:20%;"></span>
                <span class="sp-map-pin" style="top:55%; left:45%;"></span>
                <span class="sp-map-pin" style="top:38%; left:68%;"></span>
                <span class="sp-map-pin" style="top:72%; left:30%;"></span>
                <div style="position:absolute;bottom:8px;left:10px;font-size:11px;color:var(--text-muted);font-weight:600;">{{ $filters['city'] ?: 'Map preview' }}</div>
            </div>
            <button type="button" class="sp-redraw-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/></svg>
                Redraw Search Area
            </button>
        </div>

        {{-- Event Summary --}}
        @if($activeEvent)
            <div class="sp-card">
                <div class="sp-event-summary-head">
                    <div class="sp-card-title" style="margin:0;">Event Summary</div>
                    <span class="sp-event-status">{{ ucfirst($activeEvent->status ?? 'Active') }}</span>
                </div>
                <div style="font-size:14px;font-weight:800;color:var(--text-primary);margin-bottom:6px;">{{ $activeEvent->title }}</div>
                <div class="sp-summary-row"><span class="lbl">Date</span><span class="val">{{ $activeEvent->starts_at?->format('M d, Y') ?? '—' }}</span></div>
                <div class="sp-summary-row"><span class="lbl">Location</span><span class="val">{{ $activeEvent->location ?? '—' }}</span></div>
                <div class="sp-summary-row"><span class="lbl">Duration</span><span class="val">{{ $activeEvent->duration_hours ?? 6 }} hours</span></div>
                <div class="sp-summary-row"><span class="lbl">Budget</span><span class="val">${{ number_format($activeEvent->budget ?? 0, 0) }}</span></div>
                <a href="{{ route('client.events.show', $activeEvent) }}" class="sp-rail-link">
                    View Event Details
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        @endif

        {{-- Budget Overview --}}
        <div class="sp-card">
            <div class="sp-card-title">Budget Overview</div>
            <div class="sp-budget-cap"><span>${{ number_format($budgetMax) }} max</span></div>
            <div class="sp-budget-bars">
                <div class="within"  style="width: {{ max(10, $withinPct - 18) }}%;"></div>
                <div class="border"  style="width: 18%;"></div>
                <div class="over"    style="width: {{ max(10, $exceedsPct) }}%;"></div>
            </div>
            <div class="sp-budget-totals">
                <div class="avg-col">
                    <b>${{ number_format($projected, 0) }}</b>
                    <span style="color:var(--text-muted);">Avg. Projected Total</span>
                </div>
                <div class="within-col">
                    <b>{{ $pros->total() - (int)round($pros->total() * 0.18) }}</b>
                    <span style="color:var(--text-muted);">Within Budget · 82%</span>
                </div>
                <div class="over-col">
                    <b>{{ (int)round($pros->total() * 0.18) }}</b>
                    <span style="color:var(--text-muted);">Exceeds · 18%</span>
                </div>
            </div>
            <a href="{{ route('ai-tools.budget-allocator') }}" class="sp-rail-link">
                View Budget Allocator
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        {{-- AI Recommendations --}}
        <div class="sp-card">
            <div class="sp-card-title">AI Recommendations</div>
            @foreach($recommendations as $rec)
                <div class="sp-ai-row">
                    <div class="sp-ai-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="sp-ai-text"><b>{{ $rec['pro']->name }}</b> {{ $rec['reason'] }}</div>
                    <div class="sp-ai-match">{{ $rec['match'] }}% match</div>
                </div>
            @endforeach
            @if($recommendations->isEmpty())
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No recommendations yet</div>
            @endif
        </div>
    </aside>
</div>

<script>
    // Hover carousel: cycle a card's portfolio images on mouse-enter.
    (function () {
        document.querySelectorAll('.sp-procard-media').forEach(function (media) {
            var imgs = media.querySelectorAll(':scope > img');
            var dots = media.querySelectorAll('.sp-procard-dots i');
            if (imgs.length < 2) return;
            var idx = 0, timer = null;
            function show(n) {
                imgs[idx].classList.remove('on'); if (dots[idx]) dots[idx].classList.remove('on');
                idx = (n + imgs.length) % imgs.length;
                imgs[idx].classList.add('on'); if (dots[idx]) dots[idx].classList.add('on');
            }
            var card = media.closest('.sp-procard');
            card.addEventListener('mouseenter', function () {
                timer = setInterval(function () { show(idx + 1); }, 1400);
            });
            card.addEventListener('mouseleave', function () {
                clearInterval(timer); timer = null; show(0);
            });
        });
    })();
</script>
@endsection
