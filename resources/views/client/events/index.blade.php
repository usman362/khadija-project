@extends('layouts.client')

@section('title', 'My Events')
@section('page-title', 'My Events')
@section('page-subtitle', 'Your events and who is working on them.')

@push('styles')
<style>
    /* ═══════════════════ My Gigs ═══════════════════
       Matches Khadija's "My Gigs" mockup — 5 stat cards, view tabs,
       master-list table, professional-status bar, recent activity +
       quick actions, and a right rail (Event Overview donut / Pro
       Status / Payment Summary / Upcoming Deadlines). */
    .mg-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .mg-main { min-width: 0; }
    .mg-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    .mg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* View-mode tab pills */
    .mg-viewtabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .mg-viewtab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 9px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        font-size: 12.5px; font-weight: 600; color: var(--text-secondary);
        cursor: pointer; white-space: nowrap;
    }
    .mg-viewtab svg { width: 14px; height: 14px; }
    .mg-viewtab.active { background: rgba(249,115,22,0.10); color: var(--brand-text); border-color: rgba(249,115,22,0.30); }

    /* Stat cards */
    .mg-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .mg-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start; }
    .mg-stat-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-stat-ico svg { width: 18px; height: 18px; }
    .mg-stat-ico.coral  { background: rgba(249,115,22,0.12); color: var(--brand-text); }
    .mg-stat-ico.green  { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .mg-stat-ico.amber  { background: rgba(245,158,11,0.12); color: var(--warn-text); }
    .mg-stat-ico.indigo { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .mg-stat-ico.purple { background: rgba(139,92,246,0.12); color: var(--accent-text); }
    .mg-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .mg-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mg-stat-delta { font-size: 10.5px; color: var(--ok-text); font-weight: 700; margin-top: 2px; }
    .mg-stat-delta.flat { color: var(--text-muted); }

    /* Filter row */
    .mg-filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
    .mg-filter-select, .mg-filter-search {
        height: 40px; border-radius: 9px;
        border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary);
        font-size: 13px; font-family: inherit; outline: none;
    }
    .mg-filter-select { padding: 0 12px; }
    .mg-filter-search-wrap { position: relative; flex: 1; min-width: 220px; }
    .mg-filter-search { width: 100%; padding: 0 14px 0 38px; }
    .mg-filter-search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .mg-filter-btn {
        height: 40px; padding: 0 14px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font-size: 12.5px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
    }
    .mg-filter-btn svg { width: 14px; height: 14px; }
    .mg-filter-btn.coral { background: #c2410c; color: #fff; border-color: #c2410c; }  /* 2.80 -> 5.18 */

    /* Sub-tabs */
    .mg-subtabs { display: flex; gap: 22px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .mg-subtab {
        padding: 10px 2px; font-size: 13px; font-weight: 600;
        color: var(--text-muted); cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .mg-subtab.active { color: var(--brand-text); border-bottom-color: #f97316; }

    /* Master-list table */
    .mg-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .mg-table th {
        text-align: left; padding: 12px 10px;
        font-size: 10.5px; font-weight: 700; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.4px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .mg-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .mg-table tr:hover td { background: var(--bg-card-hover); }
    .mg-table .ev-name { font-weight: 700; color: var(--text-primary); }
    .mg-table .ev-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 1px; }
    .mg-table .num { text-align: center; font-weight: 600; color: var(--text-primary); }
    .mg-status-pill { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 999px; text-transform: capitalize; white-space: nowrap; }
    .mg-status-confirmed   { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-status-pending     { background: rgba(245,158,11,0.18); color: var(--warn-text); }
    .mg-status-published   { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-status-in_progress { background: rgba(99,102,241,0.15); color: var(--accent-text); }
    .mg-status-not_started, .mg-status-not_scheduled { background: var(--border-color); color: var(--text-muted); }
    .mg-status-cancelled   { background: rgba(239,68,68,0.15); color: var(--bad-text); }
    .mg-row-kebab { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 2px 6px; }
    .mg-row-kebab:hover { color: var(--brand-text); }
    /* Row "more actions" menu — the kebab used to be a bare link to the event,
       which looked like a menu and behaved as a redirect. */
    .mg-menu { position: relative; display: inline-block; }
    .mg-menu-pop { display: none; position: absolute; right: 0; top: calc(100% + 5px); z-index: 40; min-width: 180px; background: var(--bg-card,#fff); border: 1px solid var(--border-color,#e5e7eb); border-radius: 10px; box-shadow: 0 10px 28px rgba(15,23,42,.13); padding: 5px; text-align: left; }
    .mg-menu.open .mg-menu-pop { display: block; }
    .mg-menu-pop a, .mg-menu-pop button { display: block; width: 100%; text-align: left; background: none; border: 0; font: inherit; font-size: 12.5px; font-weight: 600; color: var(--text-primary,#111827); text-decoration: none; padding: 8px 10px; border-radius: 7px; cursor: pointer; }
    .mg-menu-pop a:hover, .mg-menu-pop button:hover { background: rgba(249,115,22,.10); color: var(--brand-text); }
    .mg-menu-pop form { margin: 0; }

    /* Professional Status Overview bar */
    .mg-pso { margin-top: 18px; }
    .mg-pso-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .mg-pso-legend { display: flex; gap: 18px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 10px; }
    .mg-pso-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; vertical-align: middle; }
    .mg-pso-legend b { color: var(--text-primary); margin-left: 4px; }
    .mg-pso-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: var(--border-color); }

    /* Recent activity + quick actions */
    .mg-row2 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; margin-top: 18px; }
    .mg-act-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-act-row:last-child { border-bottom: 0; }
    .mg-act-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-act-dot svg { width: 14px; height: 14px; }
    .mg-act-dot.green { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-act-dot.amber { background: rgba(245,158,11,0.15); color: var(--warn-text); }
    .mg-act-dot.indigo{ background: rgba(99,102,241,0.15); color: var(--accent-text); }
    .mg-act-dot.red   { background: rgba(239,68,68,0.15);  color: var(--danger-text, #b91c1c); }
    .mg-act-body { flex: 1; min-width: 0; }
    .mg-act-text { font-size: 12.5px; color: var(--text-primary); }
    .mg-act-time { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }
    .mg-qa { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; padding: 14px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-primary); position: relative; }
    .mg-qa:hover { border-color: rgba(249,115,22,0.30); }
    .mg-qa svg { width: 18px; height: 18px; color: var(--accent-text); }
    .mg-qa span { font-size: 12.5px; font-weight: 600; }
    .mg-qa-badge { position: absolute; top: 8px; right: 8px; background: #ef4444; color: #fff; font-size: 9px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }

    /* Right rail */
    .mg-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .mg-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .mg-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .mg-rail-sel { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; font-size: 10.5px; color: var(--text-muted); cursor: pointer; }
    .mg-donut { position: relative; width: 120px; height: 120px; margin: 4px auto 12px; }
    .mg-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 2; }
    .mg-donut-center .num { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .mg-donut-center .lbl { font-size: 10px; color: var(--text-muted); }
    .mg-legend { display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; }
    .mg-legend .row { display: flex; align-items: center; gap: 8px; }
    .mg-legend .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .mg-legend .lbl { flex: 1; color: var(--text-secondary); }
    .mg-legend .val { font-weight: 700; color: var(--text-primary); }
    .mg-pstat-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed var(--border-color); font-size: 12.5px; }
    .mg-pstat-row:last-of-type { border-bottom: 0; }
    .mg-pstat-row .lbl { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); }
    .mg-pstat-row .lbl svg { width: 13px; height: 13px; }
    .mg-pstat-row .val { font-weight: 700; color: var(--text-primary); }
    .mg-rail-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--brand-text); text-decoration: none; }
    .mg-rail-link svg { width: 12px; height: 12px; }
    .mg-pay-total { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .mg-pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; text-align: center; font-size: 10.5px; }
    .mg-pay-grid b { display: block; font-size: 14px; font-weight: 800; }
    .mg-pay-grid .paid b { color: var(--ok-text); }
    .mg-pay-grid .pend b { color: var(--warn-text); }
    .mg-pay-grid .over b { color: var(--bad-text); }
    .mg-dl-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-dl-row:last-of-type { border-bottom: 0; }
    .mg-dl-bar { width: 3px; align-self: stretch; border-radius: 999px; background: #f59e0b; flex-shrink: 0; }
    .mg-dl-body { flex: 1; min-width: 0; }
    .mg-dl-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .mg-dl-sub { font-size: 10.5px; color: var(--text-muted); }
    .mg-dl-due { font-size: 10.5px; color: var(--warn-text); font-weight: 700; white-space: nowrap; }

    @media (max-width: 1200px) {
        .mg-layout { grid-template-columns: 1fr; }
        .mg-rail { position: static; }
        .mg-stats { grid-template-columns: repeat(3, 1fr); }
        .mg-row2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 700px) {
        .mg-stats { grid-template-columns: repeat(2, 1fr); }
        .mg-table { font-size: 11px; }
    }

    .cl-calendar-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cl-calendar-nav button {
        width: 36px; height: 36px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .cl-calendar-nav button:hover { background: rgba(255,255,255,0.05); }
    .cl-calendar-month {
        font-size: 20px;
        font-weight: 700;
        min-width: 200px;
        text-align: center;
    }
    .cl-calendar-nav .today-btn {
        width: auto;
        padding: 0 16px;
        background: var(--accent-blue);
        color: #fff;
        border-color: var(--accent-blue);
        font-size: 13px;
        font-weight: 600;
    }
    .cl-calendar-nav .today-btn:hover { opacity: 0.9; }

    /* Event card in details view */
    .cl-event-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
        border-radius: var(--radius);
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .cl-event-card:hover { border-color: var(--border-glow); background: rgba(255,255,255,0.04); }

    .cl-event-date-badge {
        width: 52px; flex-shrink: 0;
        text-align: center;
        padding: 8px 0;
        border-radius: var(--radius-sm);
        background: var(--accent-blue-soft);
    }
    .cl-event-date-badge .month { font-size: 10px; text-transform: uppercase; font-weight: 600; color: var(--accent-blue); letter-spacing: 0.5px; }
    .cl-event-date-badge .day { font-size: 22px; font-weight: 800; color: var(--accent-blue); line-height: 1.2; }

    .cl-event-info { flex: 1; min-width: 0; }
    .cl-event-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .cl-event-meta { font-size: 13px; color: var(--text-muted); display: flex; gap: 16px; flex-wrap: wrap; }
    .cl-event-meta span { display: flex; align-items: center; gap: 4px; }

    .cl-event-actions { display: flex; gap: 8px; flex-shrink: 0; }

    /* Two column for view + preview */
    .cl-two-col { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
    @media (max-width: 1024px) { .cl-two-col { grid-template-columns: 1fr; } }

    /* Live Preview */
    .cl-preview-card {
        position: sticky;
        top: calc(var(--navbar-height) + 20px);
    }
    .cl-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: var(--accent-green-soft);
        color: var(--accent-green);
        margin-bottom: 12px;
    }
    .cl-preview-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
    .cl-preview-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
    .cl-preview-meta { display: flex; flex-direction: column; gap: 8px; }
    .cl-preview-meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }

    .cl-tab-content { display: none; }
    .cl-tab-content.active { display: block; }

    /* ── Multi-Select Category Dropdown ── */
    .cl-multiselect-wrap {
        position: relative;
    }
    .cl-multiselect-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: rgba(255,255,255,0.03);
        color: var(--text-primary);
        cursor: pointer;
        font-size: 14px;
        min-height: 44px;
        flex-wrap: wrap;
        gap: 6px;
        transition: var(--transition);
    }
    [data-theme="light"] .cl-multiselect-toggle {
        background: rgba(0,0,0,0.02);
    }
    .cl-multiselect-toggle:hover {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-placeholder {
        color: var(--text-muted);
    }
    .cl-multiselect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        flex: 1;
    }
    .cl-multiselect-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        background: var(--accent-blue-soft);
        color: var(--accent-blue);
        font-size: 12px;
        font-weight: 500;
    }
    .cl-multiselect-tag .tag-remove {
        cursor: pointer;
        opacity: 0.7;
        display: flex;
    }
    .cl-multiselect-tag .tag-remove:hover { opacity: 1; }
    .cl-multiselect-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 100;
        max-height: 280px;
        overflow: hidden;
        display: none;
        flex-direction: column;
    }
    .cl-multiselect-wrap.open .cl-multiselect-dropdown {
        display: flex;
    }
    .cl-multiselect-search {
        padding: 8px;
        border-bottom: 1px solid var(--border-color);
    }
    .cl-multiselect-search input {
        width: 100%;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }
    .cl-multiselect-search input:focus {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-options {
        overflow-y: auto;
        max-height: 220px;
        padding: 4px;
    }
    .cl-multiselect-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 14px;
        color: var(--text-primary);
        transition: background 0.15s;
    }
    .cl-multiselect-option:hover {
        background: rgba(99,102,241,0.08);
    }
    .cl-multiselect-option input[type="checkbox"] {
        display: none;
    }
    .cl-multiselect-check {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.15s;
    }
    .cl-multiselect-check svg {
        display: none;
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check {
        background: var(--accent-blue);
        border-color: var(--accent-blue);
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check svg {
        display: block;
        stroke: #fff;
    }
    .cl-multiselect-option.hidden {
        display: none;
    }
    /* The sub-tabs are buttons now, not decorative spans. */
    /* A button reset that keeps what .mg-subtab set: `border: 0` took the
       active underline with it and `font: inherit` undid the 13px size, which
       is why the live strip had no underline and oversized labels. */
    button.mg-subtab { background: none; border-width: 0 0 2px; border-style: solid; border-color: transparent;
        font-family: inherit; cursor: pointer; }
    .mg-filter-panel { flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;
        padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); }
    .mg-filter-panel[hidden] { display: none !important; }
    .mg-filter-panel label { display: flex; flex-direction: column; gap: 5px; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); }
    .mg-filter-count { display: inline-flex; min-width: 17px; height: 17px; padding: 0 5px; margin-left: 4px; border-radius: 999px;
        background: #f97316; color: #fff; font-size: 10.5px; font-weight: 800; align-items: center; justify-content: center; }
    .mg-filter-clear { font-size: 12.5px; font-weight: 700; color: #ea580c; text-decoration: none; align-self: center; }
    a.mg-filter-btn { text-decoration: none; }
    a.cl-calendar-event { display: block; text-decoration: none; color: inherit; }
    .mg-rail-period { font-size: 11px; font-weight: 700; color: var(--text-muted); }

    /* Calendar */
    .ec-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .ec-title { display: flex; align-items: center; gap: 8px; }
    .ec-title .cl-calendar-month { margin: 0 0 0 6px; }
    .ec-nav { width: 34px; height: 34px; border-radius: 9px; border: 1px solid var(--border-color); display: inline-flex;
        align-items: center; justify-content: center; font-size: 18px; line-height: 1; color: var(--text-secondary); text-decoration: none; }
    .ec-nav:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .ec-views { display: inline-flex; padding: 3px; gap: 3px; border: 1px solid var(--border-color); border-radius: 10px; }
    .ec-view { padding: 6px 13px; border-radius: 7px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .ec-view:hover { color: var(--text-primary); }
    .ec-view.is-active { background: #c2410c; color: #fff; }
    .ec-view.lv-pending { background: rgba(249,115,22,.12); color: #c2410c; opacity: 1; }
    a.ec-daylink { text-decoration: none; color: inherit; border-radius: 50%; }
    a.ec-daylink:hover { text-decoration: underline; }
    .ec-muted { opacity: .45; }
    .ec-ev { display: block; margin-top: 3px; padding: 2px 6px; border-radius: 5px; font-size: 11px; font-weight: 700;
        text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ec-more { display: block; margin-top: 3px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-decoration: none; }
    .ec-more:hover { color: #c2410c; }
    /* Seven equal columns. With the table's default layout a column widened
       to fit its longest entry, so the one day with an event pushed the rest
       of the week into slivers. */
    .ec-grid { table-layout: fixed; }
    .ec-grid .cl-calendar-day { min-width: 0; overflow: hidden; }
    /* Day and week — the time grid */
    .tg { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: var(--bg-card); }
    .tg-head { display: grid; border-bottom: 1px solid var(--border-color); background: var(--bg-card); }
    .tg-dayhead { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 8px 0 7px;
        text-decoration: none; color: var(--text-secondary); border-left: 1px solid var(--border-color); }
    .tg-dayhead small { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
    .tg-dayhead b { font-size: 17px; font-weight: 800; width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: var(--text-primary); }
    .tg-dayhead:hover b { background: var(--bg-card-hover); }
    .tg-dayhead.is-today small { color: #c2410c; }
    .tg-dayhead.is-today b { background: #c2410c; color: #fff; }
    .tg-day .tg-dayhead { align-items: flex-start; padding-left: 14px; flex-direction: row; gap: 8px; }
    .tg-body { max-height: 576px; overflow-y: auto; }
    .tg-inner { display: grid; position: relative;
        /* One line per hour, and a fainter one at the half. */
        background-image: linear-gradient(to bottom, var(--border-color) 1px, transparent 1px),
                          linear-gradient(to bottom, color-mix(in srgb, var(--border-color) 45%, transparent) 1px, transparent 1px);
        background-size: 100% 48px, 100% 24px; }
    .tg-gutter { position: relative; background: var(--bg-card); }
    .tg-gutter span { position: absolute; right: 8px; transform: translateY(-50%); font-size: 11px; font-weight: 600;
        color: var(--text-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tg-gutter span:first-child { transform: none; top: 3px !important; }
    .tg-col { position: relative; border-left: 1px solid var(--border-color); }
    .tg-col.is-today { background: rgba(249,115,22,.04); }
    .tg-ev { position: absolute; box-sizing: border-box; border-left: 3px solid; border-radius: 6px; padding: 4px 7px;
        text-decoration: none; overflow: hidden; display: flex; flex-direction: column; gap: 1px; z-index: 1;
        box-shadow: 0 1px 2px rgba(15,23,42,.06); }
    .tg-ev:hover { z-index: 2; box-shadow: 0 4px 12px rgba(15,23,42,.14); }
    .tg-ev b { font-size: 12.5px; font-weight: 800; line-height: 1.25; color: var(--text-primary);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tg-ev small { font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tg-ev.is-short { flex-direction: row; align-items: center; gap: 6px; padding-top: 2px; padding-bottom: 2px; }
    .tg-day .tg-ev b { font-size: 14px; }
    .tg-now { position: absolute; left: 0; right: 0; height: 2px; background: #ef4444; z-index: 3; pointer-events: none; }
    .tg-now::before { content: ''; position: absolute; left: -5px; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; }
    .ec-agenda { display: flex; flex-direction: column; }
    .ec-agenda-row { display: flex; align-items: center; gap: 12px; padding: 13px 6px; border-bottom: 1px solid var(--border-color);
        text-decoration: none; color: inherit; }
    .ec-agenda-row:last-child { border-bottom: 0; }
    .ec-agenda-row:hover { background: var(--bg-card-hover); }
    .ec-agenda-time { flex: none; width: 92px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); font-variant-numeric: tabular-nums; }
    .ec-agenda-time small { display: block; font-weight: 600; color: var(--text-muted); }
    .ec-dot { flex: none; width: 9px; height: 9px; border-radius: 50%; }
    .ec-agenda-body b { display: block; font-size: 14px; color: var(--text-primary); }
    .ec-agenda-body small { font-size: 12px; font-weight: 700; }
    .ec-empty { font-size: 13px; color: var(--text-muted); margin: 14px 2px 0; }
    .ec-empty a { font-weight: 700; color: #c2410c; }
    .ec-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; font-size: 12px; color: var(--text-secondary); }
    .ec-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .ec-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
</style>
@endpush

@section('content')
<div class="mg-layout" data-live-scope>
<div class="mg-main">


    {{-- View-mode tabs --}}
    <div class="mg-viewtabs" id="viewTabs">
        <button class="cl-tab mg-viewtab active" data-tab="list">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Events List
        </button>
        <button class="cl-tab mg-viewtab" data-tab="calendar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendar View
        </button>
        <button class="cl-tab mg-viewtab" data-tab="details">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            Details View
        </button>
    </div>

    {{-- Stat cards --}}
    <div class="mg-stats">
        <div class="mg-stat">
            <div class="mg-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            {{-- Rows 86/101/125: every tile counts EVENTS, and each is a subset of
     this one, so they add up. "This month" was wrong as well — the figure
     was never date-scoped. --}}
                            <div><div class="mg-stat-label">Total Events</div><div class="mg-stat-value">{{ $stats['total'] }}</div><div class="mg-stat-delta flat">All time</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div><div class="mg-stat-label">Booked</div><div class="mg-stat-value">{{ $stats['confirmed'] }}</div><div class="mg-stat-delta flat">Events with a pro hired</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="mg-stat-label">Open</div><div class="mg-stat-value">{{ $stats['open'] }}</div><div class="mg-stat-delta flat">Taking proposals</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/></svg></div>
            <div><div class="mg-stat-label">Completed</div><div class="mg-stat-value">{{ $stats['completed'] }}</div><div class="mg-stat-delta flat">Events finished</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div><div class="mg-stat-label">Total Spent</div><div class="mg-stat-value">${{ number_format($totalSpent, 0) }}</div><div class="mg-stat-delta flat">On completed events</div></div>
        </div>
    </div>

    {{-- Filter row --}}
    {{-- Filtering redraws the list and the details in place; this row
         stays bright so the box being typed in is never dimmed. --}}
    <form method="GET" action="{{ route('client.events.index') }}" class="mg-filter-row"
          id="mgFilters" data-live-region data-live-busy="mgListCard mgDetails">
        <input type="hidden" name="tab" value="list">
        @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
        {{-- requestSubmit, not submit(): submit() skips the submit event,
             so nothing listening could keep this on the page. --}}
        <select name="status" class="mg-filter-select" onchange="this.form.requestSubmit()" aria-label="All Events">
            <option value="">All Events</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="mg-filter-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="mg-filter-search" placeholder="Search events, professionals..." value="{{ request('search') }}" data-live-search autocomplete="off">
        </div>
        @php $moreFilters = (int) request()->filled('category') + (int) request()->filled('when'); @endphp
        {{-- Was a second submit button with nothing behind it. It opens the
             filters the row has no room for: service and when. --}}
        <button type="button" class="mg-filter-btn" data-filter-toggle aria-expanded="{{ $moreFilters ? 'true' : 'false' }}" aria-controls="mgFilterPanel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters @if($moreFilters)<span class="mg-filter-count">{{ $moreFilters }}</span>@endif</button>
        {{-- Was a button with no handler. Downloads exactly what is listed. --}}
        <a href="{{ route('client.events.export', request()->only(['search', 'status', 'category', 'when'])) }}" class="mg-filter-btn" download><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export</a>
        {{-- The only Post an Event on the page. It said "Create Master List",
             a thing this product has never had. --}}
        <a href="{{ route('client.post-event.choose') }}" class="mg-filter-btn coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Post an Event</a>

        <div class="mg-filter-panel" id="mgFilterPanel" @unless($moreFilters) hidden @endunless>
            <label>Service
                <select name="category" class="mg-filter-select" aria-label="Service">
                    <option value="">Any service</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>When
                <select name="when" class="mg-filter-select" aria-label="When">
                    <option value="">Any time</option>
                    <option value="upcoming" @selected(request('when') === 'upcoming')>Upcoming</option>
                    <option value="past" @selected(request('when') === 'past')>Already happened</option>
                    <option value="undated" @selected(request('when') === 'undated')>No date yet</option>
                </select>
            </label>
            <button type="submit" class="mg-filter-btn coral">Apply</button>
            @if(request()->hasAny(['search', 'status', 'category', 'when']))
                <a href="{{ route('client.events.index') }}" class="mg-filter-clear">Clear all</a>
            @endif
        </div>
    </form>

    {{-- ════════════ EVENTS LIST (default) ════════════ --}}
    <div class="cl-tab-content active" id="tab-list">
        <div class="mg-card" style="padding:0;overflow:hidden;" id="mgListCard" data-live-region>
            {{-- These were three spans marked "(visual)" — two of them did
                 nothing when clicked. They are three real views now: the
                 events, who is booked for when, and what each booking costs. --}}
            <div class="mg-subtabs" style="padding:0 18px;" role="tablist">
                <button type="button" class="mg-subtab active" data-subtab="events" role="tab" aria-selected="true">Events List</button>
                <button type="button" class="mg-subtab" data-subtab="schedule" role="tab" aria-selected="false">Professional Schedule</button>
                <button type="button" class="mg-subtab" data-subtab="payments" role="tab" aria-selected="false">Payment Tracker</button>
            </div>
            <div data-subpane="events">
            <div style="overflow-x:auto;">
                <table class="mg-table">
                    <thead>
                        <tr>
                            <th style="padding-left:18px;">Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pros Needed</th>
                            <th>Confirmed</th>
                            <th>Pending</th>
                            <th>Status</th>
                            <th>Budget / Spent</th>
                            <th style="padding-right:18px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            @php
                                $bk = $event->bookings ?? collect();
                                // One professional per service asked for. It was
                                // the number of bookings — i.e. how many had
                                // already answered, not how many were needed.
                                $needed    = $event->categories->count() ?: '—';
                                $confirmed = $bk->where('status', 'confirmed')->count();
                                $pending   = $bk->where('status', 'requested')->count();
                                $budget    = $event->budget ?? 0;
                                // bookings.price — total_amount and agreed_price
                                // never existed, which is why this read $0.
                                $spent     = $bk->where('status', 'completed')->sum('price');
                            @endphp
                            <tr>
                                <td style="padding-left:18px;">
                                    <div class="ev-name">{{ $event->title }}</div>
                                    <div class="ev-sub">{{ $event->starts_at?->format('M d, Y') ?? 'No date' }}@if($event->starts_at) · {{ $event->starts_at->format('g:i A') }}@endif</div>
                                </td>
                                <td>{{ $event->starts_at?->format('M d, Y') ?? '—' }}</td>
                                <td>{{ $event->starts_at?->format('g:i A') ?? '—' }}@if($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif</td>
                                <td class="num">{{ $needed }}</td>
                                <td class="num" style="color:var(--ok-text);">{{ $confirmed }}</td>
                                <td class="num" style="color:var(--warn-text);">{{ $pending }}</td>
                                <td><span class="mg-status-pill mg-status-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span></td>
                                <td style="white-space:nowrap;font-weight:600;color:var(--text-primary);">${{ number_format($budget, 0) }} / ${{ number_format($spent, 0) }}</td>
                                <td style="padding-right:18px;text-align:right;">
                                    <div class="mg-menu" data-row-menu>
                                        <button type="button" class="mg-row-kebab" aria-haspopup="true" aria-expanded="false" title="More actions">⋯</button>
                                        <div class="mg-menu-pop" data-row-menu-pop>
                                            <a href="{{ route('client.events.show', $event) }}">View event</a>
                                            <a href="{{ route('client.events.show', $event) }}#proposals">View proposals</a>
                                            @unless($event->is_published)
                                                <form method="POST" action="{{ route('client.events.publish', $event) }}">
                                                    @csrf
                                                    <button type="submit">Publish event</button>
                                                </form>
                                            @endunless
                                            <a href="{{ route('client.chat.index') }}">Message professionals</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">@if(request()->hasAny(['search', 'status', 'category', 'when']))No events match these filters. <a href="{{ route('client.events.index') }}">Clear filters</a>@else No events yet. Click <b>Post an Event</b> to get started.@endif</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())
                <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;">
                    <span>Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} matching this filter</span>
                    {{ $events->onEachSide(1)->links() }}
                </div>
            @endif
            </div>{{-- /events subpane --}}

            {{-- Who is booked, for which event, when. --}}
            <div data-subpane="schedule" hidden>
                <div style="overflow-x:auto;">
                    <table class="mg-table">
                        <thead><tr><th style="padding-left:18px;">Professional</th><th>Event</th><th>Service</th><th>Date</th><th>Time</th><th style="padding-right:18px;">Status</th></tr></thead>
                        <tbody>
                            @forelse($bookings->whereNotIn('status', \App\Domain\Finance\ClientTotals::VOID_STATUSES) as $b)
                                <tr>
                                    <td style="padding-left:18px;"><div class="ev-name">{{ $b->supplier?->name ?? 'Professional' }}</div></td>
                                    <td>@if($b->event)<a href="{{ route('client.events.show', $b->event_id) }}">{{ $b->event->title }}</a>@else — @endif</td>
                                    <td>{{ $b->category?->name ?? '—' }}</td>
                                    <td>{{ $b->event?->starts_at?->format('M d, Y') ?? 'Not scheduled' }}</td>
                                    <td>{{ $b->event?->starts_at?->format('g:i A') ?? '—' }}@if($b->event?->ends_at) – {{ $b->event->ends_at->format('g:i A') }}@endif</td>
                                    <td style="padding-right:18px;"><span class="mg-status-pill mg-status-{{ $b->status }}">{{ $b->status === 'requested' ? 'Awaiting reply' : ucfirst($b->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No professionals booked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- What each booking costs and where the money stands. --}}
            <div data-subpane="payments" hidden>
                <div style="overflow-x:auto;">
                    <table class="mg-table">
                        <thead><tr><th style="padding-left:18px;">Professional</th><th>Event</th><th>Amount</th><th style="padding-right:18px;">Payment</th></tr></thead>
                        <tbody>
                            @forelse($bookings as $b)
                                @php
                                    $pay = match (true) {
                                        $b->status === 'completed' => ['Paid', 'completed'],
                                        in_array($b->status, \App\Domain\Finance\ClientTotals::VOID_STATUSES, true) => ['Cancelled — nothing owed', 'cancelled'],
                                        $b->event?->starts_at && $b->event->starts_at->isPast() => ['Overdue', 'cancelled'],
                                        $b->status === 'confirmed' => ['Agreed, not yet paid', 'pending'],
                                        default => ['Awaiting professional', 'pending'],
                                    };
                                @endphp
                                <tr>
                                    <td style="padding-left:18px;"><div class="ev-name">{{ $b->supplier?->name ?? 'Professional' }}</div></td>
                                    <td>{{ $b->event?->title ?? '—' }}</td>
                                    <td style="font-weight:600;color:var(--text-primary);">{{ $b->price !== null ? '$' . number_format((float) $b->price, 0) : '—' }}</td>
                                    <td style="padding-right:18px;"><span class="mg-status-pill mg-status-{{ $pay[1] }}">{{ $pay[0] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-muted);">Nothing to pay yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Professional Status Overview bar --}}
        <div class="mg-pso">
            <div class="mg-pso-title">Professional Status Overview</div>
            <div class="mg-pso-legend">
                <span><span class="dot" style="background:#10b981;"></span>Confirmed<b>{{ $proStatus['confirmed'] }}</b></span>
                <span><span class="dot" style="background:#f59e0b;"></span>Pending<b>{{ $proStatus['pending'] }}</b></span>
                <span><span class="dot" style="background:#94a3b8;"></span>Not Scheduled<b>{{ $proStatus['not_scheduled'] }}</b></span>
                <span><span class="dot" style="background:#ef4444;"></span>Cancelled<b>{{ $proStatus['cancelled'] }}</b></span>
            </div>
            @php
                $psTotal = max(1, array_sum($proStatus));
                $psColors = ['confirmed'=>'#10b981','pending'=>'#f59e0b','not_scheduled'=>'#94a3b8','cancelled'=>'#ef4444'];
            @endphp
            <div class="mg-pso-bar">
                @foreach($psColors as $k => $c)
                    @php $w = ($proStatus[$k] / $psTotal) * 100; @endphp
                    @if($w > 0)<div style="width:{{ $w }}%;background:{{ $c }};"></div>@endif
                @endforeach
            </div>
        </div>

        {{-- Recent Activity, full width. Quick Actions beside it was removed
             on Ali's call, 2026-09-10 — every one of its links is already in
             the sidebar. --}}
        <div class="mg-row2">
            <div class="mg-card">
                <div class="mg-rail-head"><div class="mg-rail-title">Recent Professional Activity</div></div>
                {{-- Every row is one record that exists, timestamped when that
                     record was written — not "Activity on X" over an updated_at
                     that moves whenever anything at all is saved. --}}
                @forelse($activity as $a)
                    <div class="mg-act-row">
                        <div class="mg-act-dot {{ $a['kind'] === 'cancelled' ? 'red' : 'green' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <div class="mg-act-body"><div class="mg-act-text">{{ $a['text'] }} <b>{{ \Illuminate\Support\Str::limit($a['about'], 24) }}</b></div></div>
                        <div class="mg-act-time">{{ $a['when']->humanAgo() }}</div>
                    </div>
                @empty
                    <div style="font-size:12px;color:var(--text-muted);padding:12px 0;text-align:center;">No recent activity</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════════ CALENDAR VIEW ════════════ --}}
    {{-- A day, a week or a month, all in the address. Every control is a link
         to this page, so it changes in place (partials/_live_regions) and
         still works as a plain link. --}}
    <div class="cl-tab-content" id="tab-calendar">
        @php
            $c = $calendar;
            $calLink = fn (array $over) => route('client.events.index', array_merge(
                request()->except(['page', 'month', 'year']), ['tab' => 'calendar'], $over
            ));
            $calStages = \App\Support\ClientCalendar::STAGES;
            $unit = ['day' => 'day', 'week' => 'week', 'month' => 'month'][$c['view']];
            $perDay = $c['view'] === 'week' ? 6 : 3;
        @endphp
        <div class="cl-card ec-card" id="mgCal" data-live-region>
            <div class="ec-head">
                <div class="ec-title">
                    <a class="ec-nav" href="{{ $calLink(['calview' => $c['view'], 'cal' => $c['prev']->format('Y-m-d')]) }}" aria-label="Previous {{ $unit }}">‹</a>
                    <a class="ec-nav" href="{{ $calLink(['calview' => $c['view'], 'cal' => $c['next']->format('Y-m-d')]) }}" aria-label="Next {{ $unit }}">›</a>
                    <h3 class="cl-calendar-month">{{ $c['title'] }}</h3>
                </div>
                <div class="ec-views" role="group" aria-label="Calendar view">
                    <a class="ec-view {{ $c['view'] === 'day' && $c['anchor']->isToday() ? 'is-active' : '' }}"
                       href="{{ $calLink(['calview' => 'day', 'cal' => now()->format('Y-m-d')]) }}">Today</a>
                    <a class="ec-view {{ $c['view'] === 'week' ? 'is-active' : '' }}"
                       href="{{ $calLink(['calview' => 'week', 'cal' => $c['anchor']->format('Y-m-d')]) }}">Week</a>
                    <a class="ec-view {{ $c['view'] === 'month' ? 'is-active' : '' }}"
                       href="{{ $calLink(['calview' => 'month', 'cal' => $c['anchor']->format('Y-m-d')]) }}">Month</a>
                </div>
            </div>

            @if($c['view'] !== 'month')
                {{-- Day and week: a time grid. Hours down the side; each event
                     at its start, as tall as it lasts; overlaps side by side.
                     They used to be month-style boxes with the event as a pill
                     at the top, and no time anywhere on screen. --}}
                @php $g = \App\Support\ClientCalendar::timeGrid($c); @endphp
                @if($c['view'] === 'day' && empty($g['cols'][0]['items']))
                    <p class="ec-empty" style="margin:0 0 12px;">
                        Nothing on {{ $c['anchor']->isToday() ? 'today' : $c['anchor']->format('l, M j') }}.
                        <a href="{{ $calLink(['calview' => 'week', 'cal' => $c['anchor']->format('Y-m-d')]) }}">See the week</a>
                    </p>
                @endif
                <div class="tg tg-{{ $c['view'] }}">
                    <div class="tg-head" style="grid-template-columns: 58px repeat({{ count($g['cols']) }}, minmax(0, 1fr));">
                        <span></span>
                        @foreach($g['cols'] as $col)
                            @php $d = $col['date']; @endphp
                            {{-- The day's name opens that day. --}}
                            <a class="tg-dayhead {{ $d->isToday() ? 'is-today' : '' }}"
                               href="{{ $calLink(['calview' => 'day', 'cal' => $d->format('Y-m-d')]) }}"
                               aria-label="{{ $d->format('l, F j') }}">
                                <small>{{ $d->format('D') }}</small><b>{{ $d->day }}</b>
                            </a>
                        @endforeach
                    </div>
                    <div class="tg-body" data-scroll-to="{{ $g['scrollTo'] }}">
                        <div class="tg-inner" style="height: {{ $g['height'] }}px; grid-template-columns: 58px repeat({{ count($g['cols']) }}, minmax(0, 1fr));">
                            <div class="tg-gutter">
                                @for($h = $g['from']; $h < $g['to']; $h++)
                                    <span style="top: {{ ($h - $g['from']) * \App\Support\ClientCalendar::HOUR_PX }}px;">{{ \Carbon\Carbon::createFromTime($h % 24)->format('g A') }}</span>
                                @endfor
                            </div>
                            @foreach($g['cols'] as $col)
                                <div class="tg-col {{ $col['date']->isToday() ? 'is-today' : '' }}">
                                    @foreach($col['items'] as $it)
                                        @php
                                            $ev = $it['event'];
                                            [$stLabel, $stColour] = $calStages[$ev->stage()] ?? ['Event', '#f97316'];
                                            $w = 100 / $col['lanes'];
                                            $tall = ($it['stop'] - $it['start']) * $g['px'];
                                        @endphp
                                        <a class="tg-ev {{ $tall < 40 ? 'is-short' : '' }}" href="{{ route('client.events.show', $ev) }}" data-no-live
                                           style="top: {{ round($it['start'] * $g['px']) }}px; height: {{ round($tall) }}px;
                                                  left: calc({{ $it['lane'] * $w }}% + 2px); width: calc({{ $w }}% - 4px);
                                                  background: {{ $stColour }}1f; border-left-color: {{ $stColour }}; color: {{ $stColour }};"
                                           title="{{ $ev->title }} · {{ $ev->starts_at->format('g:i A') }} – {{ $it['ends']->format('g:i A') }} · {{ $stLabel }}">
                                            <b>{{ $ev->title }}</b>
                                            <small>{{ $ev->starts_at->format('g:i A') }} – {{ $it['ends']->format('g:i A') }}@if($c['view'] === 'day') · {{ $stLabel }}@endif</small>
                                        </a>
                                    @endforeach
                                    @if($col['date']->isToday() && $g['nowTop'] !== null)
                                        <div class="tg-now" style="top: {{ $g['nowTop'] }}px;" aria-hidden="true"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <table class="cl-calendar ec-grid ec-{{ $c['view'] }}">
                    <thead><tr>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)<th>{{ $dow }}</th>@endforeach</tr></thead>
                    <tbody>
                        @php $cursor = $c['first']->copy(); @endphp
                        @while($cursor->lte($c['last']))
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $key    = $cursor->format('Y-m-d');
                                        $dayEvs = $c['byDate']->get($key, collect());
                                        $muted  = $c['view'] === 'month' && $cursor->month !== $c['anchor']->month;
                                    @endphp
                                    <td>
                                        <div class="cl-calendar-day {{ $cursor->isToday() ? 'today' : '' }} {{ $muted ? 'ec-muted' : '' }}">
                                            {{-- The number opens that day's list. --}}
                                            <a class="day-num ec-daylink" href="{{ $calLink(['calview' => 'day', 'cal' => $key]) }}"
                                               aria-label="{{ $cursor->format('l, F j') }}{{ $dayEvs->count() ? ' — ' . $dayEvs->count() . ' event' . ($dayEvs->count() === 1 ? '' : 's') : '' }}">{{ $cursor->day }}</a>
                                            @foreach($dayEvs->take($perDay) as $ev)
                                                @php [$stLabel, $stColour] = $calStages[$ev->stage()] ?? ['Event', '#f97316']; @endphp
                                                {{-- Coloured by the stage every other screen reports. --}}
                                                <a href="{{ route('client.events.show', $ev) }}" class="cl-calendar-event ec-ev" data-no-live
                                                   style="background:{{ $stColour }}1f;color:{{ $stColour }};border-left:3px solid {{ $stColour }};"
                                                   title="{{ $ev->title }} — {{ $stLabel }}">{{ \Illuminate\Support\Str::limit($ev->title, $c['view'] === 'week' ? 22 : 14) }}</a>
                                            @endforeach
                                            @if($dayEvs->count() > $perDay)
                                                <a class="ec-more" href="{{ $calLink(['calview' => 'day', 'cal' => $key]) }}">+{{ $dayEvs->count() - $perDay }} more</a>
                                            @endif
                                        </div>
                                    </td>
                                    @php $cursor->addDay(); @endphp
                                @endfor
                            </tr>
                        @endwhile
                    </tbody>
                </table>
                @if($c['byDate']->isEmpty())
                    <p class="ec-empty">Nothing scheduled this {{ $unit }}. <a href="{{ route('client.post-event.choose') }}" data-no-live>Post an event</a> to see it here.</p>
                @endif
            @endif

            @if($c['stagesShown']->isNotEmpty() && $c['view'] !== 'day')
                {{-- Only the stages on screen. Not on the day view, where each
                     event already names its stage. --}}
                <div class="ec-legend">
                    @foreach($c['stagesShown'] as $st)
                        <span><i style="background:{{ $calStages[$st][1] }};"></i>{{ $calStages[$st][0] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ════════════ DETAILS VIEW ════════════ --}}
    <div class="cl-tab-content" id="tab-details">
    <div id="mgDetails" data-live-region>
        {{-- Stats Row --}}
        <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Events</div>
                        <div class="cl-stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Open Events</div>
                        <div class="cl-stat-value">{{ $stats['open'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon yellow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Upcoming</div>
                        <div class="cl-stat-value">{{ $stats['upcoming'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon pink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Budget</div>
                        <div class="cl-stat-value">${{ number_format($stats['total_budget'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search + Filter --}}
        <div class="cl-card" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('client.events.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="details">
                <div style="flex: 1; min-width: 200px;">
                    <div class="cl-search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search events..." value="{{ request('search') }}">
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <select name="status" class="cl-form-select" style="padding: 10px 14px;" aria-label="All Status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select name="category" class="cl-form-select" style="padding: 10px 14px;" aria-label="All Categories">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </form>
        </div>

        {{-- Events List --}}
        @if($events->count())
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($events as $event)
                    <div class="cl-event-card">
                        <div class="cl-event-date-badge">
                            @if($event->starts_at)
                                <div class="month">{{ $event->starts_at->format('M') }}</div>
                                <div class="day">{{ $event->starts_at->format('d') }}</div>
                            @else
                                <div class="month">No</div>
                                <div class="day">—</div>
                            @endif
                        </div>
                        <div class="cl-event-info">
                            <div class="cl-event-title">{{ $event->title }}</div>
                            <div class="cl-event-meta">
                                @if($event->categories->count())
                                    @foreach($event->categories as $cat)
                                        <span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                            {{ $cat->name }}
                                        </span>
                                    @endforeach
                                @endif
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $event->created_at->humanAgo() }}
                                </span>
                                <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                            </div>
                        </div>
                        {{-- Checklist rows 85 and 102 — the action belongs to
                             the card's own status, not to a template.

                             Both rows are the same fault seen twice: a draft
                             lost "Continue Draft" and a request in negotiation
                             lost its way back in, because every card got the
                             same generic pair. "Compare Proposals" on a card
                             with nought proposals is the tell. The client is
                             left without the one action that actually moves
                             that particular request forward. --}}
                        @php
                            $proposalCount = $event->bookings->where('status', 'requested')->count();
                            $negotiating   = \App\Domain\Requests\RequestLifecycle::inExclusiveNegotiation($event);
                        @endphp
                        <div class="cl-event-actions">
                            {{-- OA-140: this branched on the is_published flag, not the
                                 status. A Confirmed event with the flag unset offered
                                 "Continue Draft" and "Publish" — you cannot go back and
                                 finish writing something professionals have already
                                 answered. It asks the event's own stage now, which
                                 resolves the two columns into one answer. --}}
                            @if($event->isDraft())
                                {{-- A draft's one job is to be finished. --}}
                                <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-primary cl-btn-sm"
                                   style="background:#c2410c;border-color:#c2410c;">Continue Draft</a>
                                <form method="POST" action="{{ route('client.events.publish', $event) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm">Publish</button>
                                </form>
                            @elseif($negotiating)
                                <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-primary cl-btn-sm">Open Negotiation</a>
                            @elseif($proposalCount > 1)
                                <a href="{{ route('client.proposals.compare', $event) }}" class="cl-btn cl-btn-primary cl-btn-sm">
                                    Compare Proposals ({{ $proposalCount }})
                                </a>
                            @elseif($proposalCount === 1)
                                <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-primary cl-btn-sm">Review Proposal</a>
                            @endif

                            <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-ghost cl-btn-sm">View</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($events->hasPages())
                <div class="cl-pagination">
                    @if($events->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $events->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($events->getUrlRange(1, $events->lastPage()) as $page => $url)
                        @if($page == $events->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </div>
            @endif
        @else
            <div class="cl-card">
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="16" x2="15" y2="16"/></svg>
                    </div>
                    <div class="cl-empty-title">No events found yet</div>
                    <div class="cl-empty-text">Create your first event to get started with hiring professionals.</div>
                    <button class="cl-btn cl-btn-primary" onclick="window.location.href='{{ route('client.post-event.choose') }}'">Create Your First Event</button>
                </div>
            </div>
        @endif
    </div>{{-- /#mgDetails --}}
    </div>

</div>{{-- /.mg-main --}}

    {{-- ════════════ RIGHT RAIL ════════════ --}}
    <aside class="mg-rail" id="mgRail" data-live-region>

        {{-- Event Overview donut --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Event Overview</div>@include('client.events._period_select')</div>
            @php
                // Events by stage — every slice is an event, so they add up to
                // the number in the middle. It mixed booking counts in before.
                $evTotal = max(1, $overview['total']);
                $evPie = array_values(array_filter($overview['stages'], fn ($p) => $p['val'] > 0)) ?: [['lbl' => 'No events', 'val' => 0, 'color' => '#e5e7eb']];
                $cur = 0; $segs = [];
                foreach ($evPie as $p) { $deg = ($p['val'] / $evTotal) * 360; $segs[] = "{$p['color']} {$cur}deg ".($cur+$deg)."deg"; $cur += $deg; }
                $evConic = 'conic-gradient('.implode(', ', $segs).')';
            @endphp
            <div class="mg-donut" style="background:{{ $evConic }};border-radius:50%;">
                <div style="position:absolute;inset:13px;background:var(--bg-card);border-radius:50%;z-index:1;"></div>
                <div class="mg-donut-center"><span class="num">{{ $overview['total'] }}</span><span class="lbl">{{ $period === 'all' ? 'Total Events' : 'Posted ' . strtolower($periods[$period]) }}</span></div>
            </div>
            <div class="mg-legend">
                @foreach($evPie as $p)
                    @php $pp = $overview['total'] > 0 ? round(($p['val']/$overview['total'])*100) : 0; @endphp
                    <div class="row"><span class="dot" style="background:{{ $p['color'] }};"></span><span class="lbl">{{ $p['lbl'] }}</span><span class="val">{{ $p['val'] }} ({{ $pp }}%)</span></div>
                @endforeach
            </div>
        </div>

        {{-- Professional Status --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Professional Status</div>@if($period !== 'all')<span class="mg-rail-period">{{ $periods[$period] }}</span>@endif</div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Confirmed</span><span class="val">{{ $proStatus['confirmed'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pending</span><span class="val">{{ $proStatus['pending'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Not Scheduled</span><span class="val">{{ $proStatus['not_scheduled'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>Cancelled</span><span class="val">{{ $proStatus['cancelled'] }}</span></div>
        </div>

        {{-- Payment Summary --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Payment Summary</div>@include('client.events._period_select')</div>
            {{-- It said "Total Spent" over a figure that included money not yet
                 paid. It is everything booked; "Paid" below is what was spent. --}}
            <div style="font-size:11px;color:var(--text-muted);">Total booked</div>
            <div class="mg-pay-total">${{ number_format($payment['total'], 0) }}</div>
            <div class="mg-pay-grid">
                <div class="paid"><b>${{ number_format($payment['paid'], 0) }}</b><span style="color:var(--text-muted);">Paid</span></div>
                <div class="pend"><b>${{ number_format($payment['pending'], 0) }}</b><span style="color:var(--text-muted);">Pending</span></div>
                <div class="over"><b>${{ number_format($payment['overdue'], 0) }}</b><span style="color:var(--text-muted);">Overdue</span></div>
            </div>
            <a href="{{ route('app.payments.history') }}" class="mg-rail-link">View Payment Tracker <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Upcoming Deadlines --}}
        <div class="mg-rail-card">
            {{-- These are event dates, not deadlines, and every row said
                 "Finalize event details" whatever state the event was in. --}}
            <div class="mg-rail-head"><div class="mg-rail-title">Coming Up</div></div>
            @forelse($deadlines as $dl)
                @php $daysLeft = (int) ceil(now()->diffInHours($dl->starts_at, false) / 24); @endphp
                <div class="mg-dl-row">
                    <span class="mg-dl-bar"></span>
                    <div class="mg-dl-body">
                        <div class="mg-dl-title">{{ \Illuminate\Support\Str::limit($dl->title, 22) }}</div>
                        <div class="mg-dl-sub">{{ $dl->starts_at->format('D, M j · g:i A') }}</div>
                    </div>
                    <span class="mg-dl-due">{{ $daysLeft <= 0 ? 'Today' : 'In ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') }}</span>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">Nothing in the next two weeks</div>
            @endforelse
        </div>
    </aside>

</div>{{-- /.mg-layout --}}

@endsection

@include('partials._row-menu-script')
@include('partials._live_regions')
@push('scripts')
<script>

    // Tab switching
    document.querySelectorAll('#viewTabs .cl-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#viewTabs .cl-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.cl-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');

            // The address names the view on screen. Switching tabs left it
            // alone, so it could say calview=month over the events list.
            var u = new URL(location.href);
            u.searchParams.set('tab', this.dataset.tab);
            history.replaceState({ lv: u.toString() }, '', u.toString());
        });
    });

    // Open modal if ?create=1
    if (new URLSearchParams(window.location.search).get('create') === '1') {
        window.location.href='{{ route('client.post-event.choose') }}';
    }

    // Open whichever view the URL names — the calendar's month arrows reload
    // the page, and used to drop the client back on the list every time.
    (function () {
        var want = new URLSearchParams(window.location.search).get('tab');
        var tab = want && document.querySelector('#viewTabs [data-tab="' + want + '"]');
        if (tab) tab.click();
    })();

    // Events List / Professional Schedule / Payment Tracker.
    // Delegated: the list card is swapped for a fresh copy on every filter,
    // and a listener bound to the old buttons would die with them.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-subtab]') : null;
        if (!btn) return;
        document.querySelectorAll('[data-subtab]').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        document.querySelectorAll('[data-subpane]').forEach(function (pane) {
            pane.hidden = pane.dataset.subpane !== btn.dataset.subtab;
        });
    });

    // Filters opens the service / when panel. Delegated for the same reason.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-filter-toggle]') : null;
        if (!btn) return;
        var panel = document.getElementById('mgFilterPanel');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        btn.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    });

    // The time grid opens at its first event rather than at 7 AM.
    function scrollGrid() {
        var body = document.querySelector('#mgCal .tg-body[data-scroll-to]');
        if (body) body.scrollTop = parseInt(body.getAttribute('data-scroll-to'), 10) || 0;
    }
    scrollGrid();
    document.addEventListener('live:swapped', scrollGrid);
    // The calendar tab starts hidden, and a hidden box cannot be scrolled.
    document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('#viewTabs [data-tab="calendar"]')) setTimeout(scrollGrid, 0);
    });

    // Close any open multi-select dropdowns on Escape.
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.cl-multiselect-wrap.open').forEach(el => el.classList.remove('open'));
        }
    });

    // ── Multi-Select Category Logic ──
    function updateMultiselectDisplay() {
        const wrap = document.getElementById('categoryMultiselect');
        const toggle = wrap.querySelector('.cl-multiselect-toggle');
        const checked = wrap.querySelectorAll('input[type="checkbox"]:checked');
        const placeholder = toggle.querySelector('.cl-multiselect-placeholder');

        // Remove existing tags
        toggle.querySelectorAll('.cl-multiselect-tags').forEach(el => el.remove());

        if (checked.length === 0) {
            if (placeholder) placeholder.style.display = '';
        } else {
            if (placeholder) placeholder.style.display = 'none';
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'cl-multiselect-tags';
            checked.forEach(cb => {
                const name = cb.closest('.cl-multiselect-option').querySelector('span:last-child').textContent;
                const tag = document.createElement('span');
                tag.className = 'cl-multiselect-tag';
                tag.innerHTML = name + ' <span class="tag-remove" data-id="' + cb.value + '"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>';
                tagsContainer.appendChild(tag);
            });
            toggle.insertBefore(tagsContainer, toggle.querySelector('svg:last-child'));
        }
    }

    // Checkbox change handler
    document.querySelectorAll('#categoryMultiselect input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateMultiselectDisplay);
    });

    // Tag remove handler (delegated)
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.tag-remove');
        if (removeBtn) {
            e.stopPropagation();
            const id = removeBtn.dataset.id;
            const cb = document.querySelector('#categoryMultiselect input[value="' + id + '"]');
            if (cb) { cb.checked = false; updateMultiselectDisplay(); }
        }
    });

    // Search/filter categories
    function filterCategories(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('#categoryMultiselect .cl-multiselect-option').forEach(opt => {
            const name = opt.dataset.name;
            opt.classList.toggle('hidden', q && !name.includes(q));
        });
    }

    // Close multiselect on outside click
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.cl-multiselect-wrap.open').forEach(wrap => {
            if (!wrap.contains(e.target)) wrap.classList.remove('open');
        });
    });
</script>
@endpush
